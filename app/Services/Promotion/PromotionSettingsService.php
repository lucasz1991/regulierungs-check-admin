<?php

namespace App\Services\Promotion;

use App\Models\PromotionSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class PromotionSettingsService
{
    public const QR_TTL_MINUTES_MIN = 5;

    public const QR_TTL_MINUTES_MAX = 120;

    /** @return array<string, bool|int|string|null> */
    public function get(): array
    {
        if (! $this->tableExists()) {
            return $this->disabledSnapshot('Die Datenbankmigration fuer die Promotion-Einstellungen fehlt.');
        }

        try {
            $setting = PromotionSetting::query()->find(1);
        } catch (Throwable) {
            return $this->disabledSnapshot('Die Promotion-Einstellungen konnten nicht sicher gelesen werden.');
        }

        if (! $setting) {
            return $this->disabledSnapshot('Der Datensatz fuer die Promotion-Einstellungen fehlt.');
        }

        return $this->snapshot($setting);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, bool|int|string|null>
     */
    public function save(array $values): array
    {
        $this->assertTableExists();
        $validated = $this->validate($values);

        DB::transaction(function () use ($validated): void {
            $setting = PromotionSetting::query()->whereKey(1)->lockForUpdate()->first();
            $supportsPublicCampaign = Schema::hasColumn('promotion_settings', 'public_campaign_id');

            if (! $setting) {
                $setting = new PromotionSetting(['id' => 1]);
            }

            $encryptedSecret = trim((string) $setting->getRawOriginal('audit_secret_encrypted'));
            if ($encryptedSecret === '') {
                if ($this->auditEventsExist()) {
                    throw new RuntimeException('Der Promotion-Auditschluessel fehlt. Bei vorhandenen Auditereignissen darf kein neuer Schluessel erzeugt werden.');
                }

                $encryptedSecret = $this->newEncryptedSecret();
            } else {
                $existingSecret = $this->decryptSecret($encryptedSecret);
                $this->assertConfigurationMac($setting, $existingSecret);
            }

            $secret = $this->decryptSecret($encryptedSecret);
            $publicCampaignId = $supportsPublicCampaign && $validated['public_campaign_id_present']
                ? $validated['public_campaign_id']
                : ($supportsPublicCampaign && $setting->public_campaign_id !== null ? (int) $setting->public_campaign_id : null);

            $updates = [
                'enabled' => $validated['enabled'],
                'redemption_base_url' => $validated['redemption_base_url'],
                'qr_ttl_minutes' => $validated['qr_ttl_minutes'],
                'audit_secret_encrypted' => $encryptedSecret,
                'configuration_mac' => $this->configurationMac([
                    'enabled' => $validated['enabled'],
                    'public_campaign_id' => $publicCampaignId,
                    'redemption_base_url' => $validated['redemption_base_url'],
                    'qr_ttl_minutes' => $validated['qr_ttl_minutes'],
                ], $secret),
            ];

            if ($supportsPublicCampaign) {
                $updates['public_campaign_id'] = $publicCampaignId;
            }

            $setting->forceFill($updates)->save();
        }, 3);

        return $this->get();
    }

    public function isEnabled(): bool
    {
        return $this->get()['enabled'] === true;
    }

    public function isLegacyWinFlowEnabled(): bool
    {
        $snapshot = $this->get();

        return $snapshot['requested_enabled'] === true && $snapshot['base_configured'] === true;
    }

    public function auditKey(): string
    {
        $setting = $this->requireSetting();

        return $this->decryptSecret((string) $setting->getRawOriginal('audit_secret_encrypted'));
    }

    public function redemptionBaseUrl(): string
    {
        $url = rtrim(trim((string) $this->requireSetting()->redemption_base_url), '/');
        $error = $this->redemptionUrlError($url);

        if ($error !== null) {
            throw new RuntimeException($error);
        }

        return $url;
    }

    public function qrTtlMinutes(): int
    {
        $value = (int) $this->requireSetting()->qr_ttl_minutes;

        if ($value < self::QR_TTL_MINUTES_MIN || $value > self::QR_TTL_MINUTES_MAX) {
            throw new RuntimeException('Die QR-Gueltigkeitsdauer der Promotion-Einstellungen ist ungueltig.');
        }

        return $value;
    }

    public function publicCampaignId(): ?int
    {
        if (! Schema::hasColumn('promotion_settings', 'public_campaign_id')) {
            return null;
        }

        $value = $this->requireSetting()->public_campaign_id;

        return $value === null ? null : (int) $value;
    }

    /**
     * Updates the singleton selection under a row lock. When called from an
     * outer transaction the lock remains held until the complete publish flow
     * (settings, campaign flags and audit events) commits.
     */
    public function setPublicCampaignId(?int $campaignId): void
    {
        if (! Schema::hasColumn('promotion_settings', 'public_campaign_id')) {
            throw new RuntimeException('Die Datenbankmigration fuer die oeffentliche Promotion-Kampagne fehlt.');
        }

        if ($campaignId !== null && ($campaignId < 1 || ! DB::table('campaigns')->where('id', $campaignId)->exists())) {
            throw new RuntimeException('Die ausgewaehlte oeffentliche Kampagne existiert nicht.');
        }

        DB::transaction(function () use ($campaignId): void {
            $setting = PromotionSetting::query()->whereKey(1)->lockForUpdate()->firstOrFail();
            $secret = $this->decryptSecret((string) $setting->getRawOriginal('audit_secret_encrypted'));
            $this->assertConfigurationMac($setting, $secret);

            $setting->forceFill([
                'public_campaign_id' => $campaignId,
                'configuration_mac' => $this->configurationMac([
                    'enabled' => (bool) $setting->enabled,
                    'public_campaign_id' => $campaignId,
                    'qr_ttl_minutes' => (int) $setting->qr_ttl_minutes,
                    'redemption_base_url' => (string) $setting->redemption_base_url,
                ], $secret),
            ])->save();
        }, 3);
    }

    /** @return array<string, bool|int|string|null> */
    private function snapshot(PromotionSetting $setting): array
    {
        $error = null;

        try {
            $secret = $this->decryptSecret((string) $setting->getRawOriginal('audit_secret_encrypted'));
            $this->assertConfigurationMac($setting, $secret);
            $auditKeyConfigured = true;
        } catch (RuntimeException $exception) {
            $auditKeyConfigured = false;
            $error = $exception->getMessage();
        }

        $redemptionBaseUrl = rtrim(trim((string) $setting->redemption_base_url), '/');
        $error ??= $this->redemptionUrlError($redemptionBaseUrl);

        $qrTtl = (int) $setting->qr_ttl_minutes;
        if ($error === null && ($qrTtl < self::QR_TTL_MINUTES_MIN || $qrTtl > self::QR_TTL_MINUTES_MAX)) {
            $error = 'Die QR-Gueltigkeitsdauer der Promotion-Einstellungen ist ungueltig.';
        }

        $baseConfigured = $error === null;
        $isConfigured = $baseConfigured;
        $requestedEnabled = (bool) $setting->enabled;
        $supportsPublicCampaign = Schema::hasColumn('promotion_settings', 'public_campaign_id')
            && Schema::hasColumn('campaigns', 'is_public')
            && Schema::hasColumn('campaigns', 'public_slot');
        if ($supportsPublicCampaign && $error === null && $requestedEnabled) {
            $publicCampaignId = $setting->public_campaign_id === null ? null : (int) $setting->public_campaign_id;
            $campaign = $publicCampaignId === null ? null : DB::table('campaigns')->where('id', $publicCampaignId)->first();
            $publicCount = DB::table('campaigns')->where('is_public', true)->where('public_slot', 1)->count();
            $now = now();
            $campaignOpen = $campaign
                && (bool) $campaign->is_active
                && (bool) $campaign->is_public
                && (int) $campaign->public_slot === 1
                && ($campaign->starts_at === null || $now->gte($campaign->starts_at))
                && ($campaign->ends_at === null || $now->lte($campaign->ends_at));
            if (! $campaignOpen || $publicCount !== 1) {
                $error = 'Es ist keine eindeutig ausgewaehlte, oeffentliche und aktuell laufende Kampagne vorhanden.';
                $isConfigured = false;
            }
        }

        return [
            'enabled' => $requestedEnabled && $isConfigured,
            'requested_enabled' => $requestedEnabled,
            'public_campaign_id' => $supportsPublicCampaign && $setting->public_campaign_id !== null
                ? (int) $setting->public_campaign_id
                : null,
            'redemption_base_url' => $redemptionBaseUrl,
            'qr_ttl_minutes' => $qrTtl,
            'audit_key_configured' => $auditKeyConfigured,
            'base_configured' => $baseConfigured,
            'is_configured' => $isConfigured,
            'configuration_error' => $error,
        ];
    }

    /** @return array<string, bool|int|string|null> */
    private function disabledSnapshot(string $error): array
    {
        return [
            'enabled' => false,
            'requested_enabled' => false,
            'public_campaign_id' => null,
            'redemption_base_url' => '',
            'qr_ttl_minutes' => 30,
            'audit_key_configured' => false,
            'base_configured' => false,
            'is_configured' => false,
            'configuration_error' => $error,
        ];
    }

    /** @return array{enabled: bool, public_campaign_id: ?int, public_campaign_id_present: bool, redemption_base_url: string, qr_ttl_minutes: int} */
    private function validate(array $values): array
    {
        $url = rtrim(trim((string) ($values['redemption_base_url'] ?? '')), '/');
        $ttl = filter_var($values['qr_ttl_minutes'] ?? null, FILTER_VALIDATE_INT);
        $errors = [];
        $publicCampaignIdPresent = array_key_exists('public_campaign_id', $values);
        $publicCampaignId = null;
        if ($publicCampaignIdPresent && $values['public_campaign_id'] !== null && $values['public_campaign_id'] !== '') {
            $publicCampaignId = filter_var($values['public_campaign_id'], FILTER_VALIDATE_INT);
            if ($publicCampaignId === false || $publicCampaignId < 1 || ! DB::table('campaigns')->where('id', $publicCampaignId)->exists()) {
                $errors['public_campaign_id'] = 'Die ausgewaehlte oeffentliche Kampagne ist ungueltig.';
            }
        }

        if (($urlError = $this->redemptionUrlError($url)) !== null) {
            $errors['redemption_base_url'] = $urlError;
        }

        if ($ttl === false || $ttl < self::QR_TTL_MINUTES_MIN || $ttl > self::QR_TTL_MINUTES_MAX) {
            $errors['qr_ttl_minutes'] = 'Die QR-Gueltigkeit muss zwischen 5 und 120 Minuten liegen.';
        }

        $enabled = filter_var($values['enabled'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($enabled === null) {
            $errors['enabled'] = 'Der Aktivierungsstatus ist ungueltig.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'enabled' => (bool) $enabled,
            'public_campaign_id' => $publicCampaignId === false ? null : $publicCampaignId,
            'public_campaign_id_present' => $publicCampaignIdPresent,
            'redemption_base_url' => $url,
            'qr_ttl_minutes' => (int) $ttl,
        ];
    }

    private function requireSetting(): PromotionSetting
    {
        $this->assertTableExists();

        try {
            $setting = PromotionSetting::query()->find(1);
        } catch (Throwable $exception) {
            throw new RuntimeException('Die Promotion-Einstellungen konnten nicht sicher gelesen werden.', 0, $exception);
        }

        if (! $setting) {
            throw new RuntimeException('Der Datensatz fuer die Promotion-Einstellungen fehlt.');
        }

        $secret = $this->decryptSecret((string) $setting->getRawOriginal('audit_secret_encrypted'));
        $this->assertConfigurationMac($setting, $secret);

        return $setting;
    }

    private function decryptSecret(string $encrypted): string
    {
        if (trim($encrypted) === '') {
            throw new RuntimeException('Der Promotion-Auditschluessel fehlt.');
        }

        try {
            $encoded = Crypt::decryptString($encrypted);
        } catch (Throwable $exception) {
            throw new RuntimeException('Der Promotion-Auditschluessel kann nicht entschluesselt werden. Stimmen APP_KEY und Datenbank beider Anwendungen ueberein?', 0, $exception);
        }

        $secret = base64_decode($encoded, true);
        if ($secret === false || strlen($secret) !== 32) {
            throw new RuntimeException('Der entschluesselte Promotion-Auditschluessel ist ungueltig.');
        }

        return $secret;
    }

    private function newEncryptedSecret(): string
    {
        return Crypt::encryptString(base64_encode(random_bytes(32)));
    }

    private function assertConfigurationMac(PromotionSetting $setting, string $secret): void
    {
        $publicCampaignId = Schema::hasColumn('promotion_settings', 'public_campaign_id') && $setting->public_campaign_id !== null
            ? (int) $setting->public_campaign_id
            : null;
        $stored = (string) $setting->getRawOriginal('configuration_mac');
        $expected = $this->configurationMac([
            'enabled' => (bool) $setting->enabled,
            'public_campaign_id' => $publicCampaignId,
            'redemption_base_url' => rtrim(trim((string) $setting->redemption_base_url), '/'),
            'qr_ttl_minutes' => (int) $setting->qr_ttl_minutes,
        ], $secret);

        $legacyExpected = $this->legacyConfigurationMac($setting, $secret);
        $matchesLegacy = $publicCampaignId === null && hash_equals($stored, $legacyExpected);

        if (preg_match('/\A[a-f0-9]{64}\z/', $stored) !== 1
            || (! hash_equals($stored, $expected) && ! $matchesLegacy)) {
            throw new RuntimeException('Die Promotion-Einstellungen wurden ausserhalb des geschuetzten Admin-Ablaufs veraendert.');
        }
    }

    /** @param array<string, mixed> $values */
    private function configurationMac(array $values, string $secret): string
    {
        $material = [
            'enabled' => (bool) $values['enabled'],
            'public_campaign_id' => $values['public_campaign_id'] === null ? null : (int) $values['public_campaign_id'],
            'qr_ttl_minutes' => (int) $values['qr_ttl_minutes'],
            'redemption_base_url' => rtrim(trim((string) $values['redemption_base_url']), '/'),
        ];

        return hash_hmac(
            'sha256',
            json_encode($material, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $secret,
        );
    }

    private function legacyConfigurationMac(PromotionSetting $setting, string $secret): string
    {
        return hash_hmac('sha256', json_encode([
            'enabled' => (bool) $setting->enabled,
            'qr_ttl_minutes' => (int) $setting->qr_ttl_minutes,
            'redemption_base_url' => rtrim(trim((string) $setting->redemption_base_url), '/'),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $secret);
    }

    private function redemptionUrlError(string $url): ?string
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return 'Die oeffentliche Einloese-URL fehlt oder ist ungueltig.';
        }

        $parts = parse_url($url);
        if (! is_array($parts)
            || isset($parts['user'], $parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            return 'Die oeffentliche Einloese-URL darf keine Zugangsdaten, Query oder Fragment enthalten.';
        }

        $scheme = mb_strtolower((string) ($parts['scheme'] ?? ''));
        $host = trim(mb_strtolower((string) ($parts['host'] ?? '')), '[]');
        if ($scheme === 'https' && $host !== '') {
            return null;
        }

        $localHosts = ['localhost', '127.0.0.1', '::1'];
        if ($scheme === 'http' && in_array($host, $localHosts, true) && ! app()->environment('production')) {
            return null;
        }

        return app()->environment('production')
            ? 'Die oeffentliche Einloese-URL muss in Produktion HTTPS verwenden.'
            : 'HTTP ist nur fuer localhost, 127.0.0.1 oder ::1 erlaubt; andernfalls ist HTTPS erforderlich.';
    }

    private function auditEventsExist(): bool
    {
        return Schema::hasTable('win_events') && DB::table('win_events')->exists();
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('promotion_settings');
        } catch (Throwable) {
            return false;
        }
    }

    private function assertTableExists(): void
    {
        if (! $this->tableExists()) {
            throw new RuntimeException('Die Datenbankmigration fuer die Promotion-Einstellungen fehlt. Die Promotion bleibt deaktiviert.');
        }
    }
}
