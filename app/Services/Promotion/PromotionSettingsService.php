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

            $setting->forceFill([
                'enabled' => $validated['enabled'],
                'redemption_base_url' => $validated['redemption_base_url'],
                'qr_ttl_minutes' => $validated['qr_ttl_minutes'],
                'audit_secret_encrypted' => $encryptedSecret,
                'configuration_mac' => $this->configurationMac([
                    'enabled' => $validated['enabled'],
                    'redemption_base_url' => $validated['redemption_base_url'],
                    'qr_ttl_minutes' => $validated['qr_ttl_minutes'],
                ], $secret),
            ])->save();
        }, 3);

        return $this->get();
    }

    public function isEnabled(): bool
    {
        return $this->get()['enabled'] === true;
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

        $isConfigured = $error === null;
        $requestedEnabled = (bool) $setting->enabled;

        return [
            'enabled' => $requestedEnabled && $isConfigured,
            'requested_enabled' => $requestedEnabled,
            'redemption_base_url' => $redemptionBaseUrl,
            'qr_ttl_minutes' => $qrTtl,
            'audit_key_configured' => $auditKeyConfigured,
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
            'redemption_base_url' => '',
            'qr_ttl_minutes' => 30,
            'audit_key_configured' => false,
            'is_configured' => false,
            'configuration_error' => $error,
        ];
    }

    /** @return array{enabled: bool, redemption_base_url: string, qr_ttl_minutes: int} */
    private function validate(array $values): array
    {
        $url = rtrim(trim((string) ($values['redemption_base_url'] ?? '')), '/');
        $ttl = filter_var($values['qr_ttl_minutes'] ?? null, FILTER_VALIDATE_INT);
        $errors = [];

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
        $stored = (string) $setting->getRawOriginal('configuration_mac');
        $expected = $this->configurationMac([
            'enabled' => (bool) $setting->enabled,
            'redemption_base_url' => rtrim(trim((string) $setting->redemption_base_url), '/'),
            'qr_ttl_minutes' => (int) $setting->qr_ttl_minutes,
        ], $secret);

        if (preg_match('/\A[a-f0-9]{64}\z/', $stored) !== 1 || ! hash_equals($stored, $expected)) {
            throw new RuntimeException('Die Promotion-Einstellungen wurden ausserhalb des geschuetzten Admin-Ablaufs veraendert.');
        }
    }

    /** @param array<string, mixed> $values */
    private function configurationMac(array $values, string $secret): string
    {
        $material = [
            'enabled' => (bool) $values['enabled'],
            'qr_ttl_minutes' => (int) $values['qr_ttl_minutes'],
            'redemption_base_url' => rtrim(trim((string) $values['redemption_base_url']), '/'),
        ];

        return hash_hmac(
            'sha256',
            json_encode($material, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $secret,
        );
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
