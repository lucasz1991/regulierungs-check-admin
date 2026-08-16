<?php

namespace App\Services\Promotion;

use App\Models\SocialAuthProviderSetting;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class SocialAuthProviderSettingsService
{
    public const PROVIDERS = ['google', 'apple'];

    public function __construct(private readonly PromotionSettingsService $promotionSettings) {}

    /** @return array<string, mixed> */
    public function get(string $provider): array
    {
        $provider = $this->provider($provider);
        if (! $this->tableExists()) {
            return $this->disabled($provider, 'Die Social-Login-Migration fehlt.');
        }

        $setting = SocialAuthProviderSetting::query()->where('provider', $provider)->first();
        if (! $setting) {
            return $this->disabled($provider, null);
        }

        try {
            $secret = $this->decryptNullable((string) $setting->getRawOriginal('client_secret_encrypted'));
            $this->assertMac($setting, $secret);
            $error = $this->configurationError($setting, $secret);
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }

        return [
            'provider' => $provider,
            'enabled' => (bool) $setting->enabled && $error === null,
            'requested_enabled' => (bool) $setting->enabled,
            'client_id' => (string) $setting->client_id,
            'has_client_secret' => trim((string) $setting->getRawOriginal('client_secret_encrypted')) !== '',
            'redirect_uri' => (string) $setting->redirect_uri,
            'apple_team_id' => (string) $setting->apple_team_id,
            'apple_key_id' => (string) $setting->apple_key_id,
            'client_secret_expires_at' => $this->expiryUtc($setting),
            'is_configured' => $error === null,
            'configuration_error' => $error,
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return collect(self::PROVIDERS)->mapWithKeys(fn (string $provider): array => [$provider => $this->get($provider)])->all();
    }

    /** @return array<string, mixed> */
    public function save(string $provider, array $values, User $actor): array
    {
        $provider = $this->provider($provider);
        $this->assertGlobalAdmin($actor);
        if (! $this->tableExists()) {
            throw new RuntimeException('Die Social-Login-Migration fehlt.');
        }

        DB::transaction(function () use ($provider, $values, $actor): void {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->getKey());
            $this->assertGlobalAdmin($actor);
            $setting = SocialAuthProviderSetting::query()->where('provider', $provider)->lockForUpdate()->first();
            $existingSecret = $setting
                ? $this->decryptNullable((string) $setting->getRawOriginal('client_secret_encrypted'))
                : null;
            if ($setting) {
                $this->assertMac($setting, $existingSecret);
            }

            $validated = $this->validate($provider, $values, $existingSecret, $setting);
            $plainSecret = $validated['client_secret'];
            $encryptedSecret = $plainSecret === null ? null : Crypt::encryptString($plainSecret);
            $setting ??= new SocialAuthProviderSetting(['provider' => $provider]);
            $material = [
                'provider' => $provider,
                'enabled' => $validated['enabled'],
                'client_id' => $validated['client_id'],
                'secret_digest' => $plainSecret === null ? null : hash('sha256', $plainSecret),
                'redirect_uri' => $validated['redirect_uri'],
                'apple_team_id' => $validated['apple_team_id'],
                'apple_key_id' => $validated['apple_key_id'],
                'client_secret_expires_at' => $validated['client_secret_expires_at'],
            ];

            $setting->forceFill([
                'enabled' => $validated['enabled'],
                'client_id' => $validated['client_id'],
                'client_secret_encrypted' => $encryptedSecret,
                'redirect_uri' => $validated['redirect_uri'],
                'apple_team_id' => $validated['apple_team_id'],
                'apple_key_id' => $validated['apple_key_id'],
                'client_secret_expires_at' => $validated['client_secret_expires_at'],
                'configuration_mac' => $this->mac($material),
            ])->save();
        }, 3);

        return $this->get($provider);
    }

    /** @return array{client_id: string, client_secret: string, redirect_uri: string} */
    public function credentials(string $provider): array
    {
        $provider = $this->provider($provider);
        $snapshot = $this->get($provider);
        if ($snapshot['enabled'] !== true) {
            throw new RuntimeException('Der Social-Login-Anbieter ist nicht vollstaendig eingerichtet oder deaktiviert.');
        }

        $setting = SocialAuthProviderSetting::query()->where('provider', $provider)->firstOrFail();
        $secret = $this->decryptNullable((string) $setting->getRawOriginal('client_secret_encrypted'));
        $this->assertMac($setting, $secret);

        return [
            'client_id' => (string) $setting->client_id,
            'client_secret' => (string) $secret,
            'redirect_uri' => (string) $setting->redirect_uri,
        ];
    }

    /** @return array<string, mixed> */
    private function validate(
        string $provider,
        array $values,
        ?string $existingSecret,
        ?SocialAuthProviderSetting $existing,
    ): array {
        $enabled = filter_var($values['enabled'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $clientId = array_key_exists('client_id', $values)
            ? trim((string) $values['client_id'])
            : trim((string) $existing?->client_id);
        $redirectUri = array_key_exists('redirect_uri', $values)
            ? trim((string) $values['redirect_uri'])
            : trim((string) $existing?->redirect_uri);
        $secretInput = array_key_exists('client_secret', $values) ? trim((string) $values['client_secret']) : '';
        $clientSecret = $secretInput === '' ? $existingSecret : $secretInput;
        $teamId = $provider === 'apple'
            ? (array_key_exists('apple_team_id', $values) ? trim((string) $values['apple_team_id']) : trim((string) $existing?->apple_team_id))
            : null;
        $keyId = $provider === 'apple'
            ? (array_key_exists('apple_key_id', $values) ? trim((string) $values['apple_key_id']) : trim((string) $existing?->apple_key_id))
            : null;
        $expiresAt = null;
        if ($provider === 'apple') {
            $hasNewExpiry = array_key_exists('client_secret_expires_at', $values);
            $expiryInput = $hasNewExpiry ? $values['client_secret_expires_at'] : $existing?->getRawOriginal('client_secret_expires_at');
            if ($expiryInput !== null && trim((string) $expiryInput) !== '') {
                try {
                    $expiresAt = $hasNewExpiry
                        ? \Illuminate\Support\Carbon::parse($expiryInput)->utc()->format('Y-m-d H:i:s')
                        : \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s', (string) $expiryInput, 'UTC')->format('Y-m-d H:i:s');
                } catch (Throwable) {
                    $expiresAt = null;
                }
            }
        }
        $errors = [];

        if ($enabled === null) {
            $errors['enabled'] = 'Der Aktivierungsstatus ist ungueltig.';
        }
        if ($enabled === true) {
            if ($clientId === '') {
                $errors['client_id'] = 'Die Client-ID fehlt.';
            }
            if ($clientSecret === null || $clientSecret === '') {
                $errors['client_secret'] = 'Das Client-Secret fehlt.';
            }
            if ($this->redirectError($redirectUri) !== null) {
                $errors['redirect_uri'] = $this->redirectError($redirectUri);
            }
            if ($provider === 'apple' && $teamId === '') {
                $errors['apple_team_id'] = 'Die Apple Team-ID fehlt.';
            }
            if ($provider === 'apple' && $keyId === '') {
                $errors['apple_key_id'] = 'Die Apple Key-ID fehlt.';
            }
            if ($provider === 'apple' && ($expiresAt === null || now()->utc()->gte($expiresAt))) {
                $errors['client_secret_expires_at'] = 'Das Apple Client-Secret muss noch gueltig sein.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'enabled' => (bool) $enabled,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'apple_team_id' => $teamId,
            'apple_key_id' => $keyId,
            'client_secret_expires_at' => $expiresAt,
        ];
    }

    private function configurationError(SocialAuthProviderSetting $setting, ?string $secret): ?string
    {
        if (trim((string) $setting->client_id) === '' || $secret === null || $secret === '') {
            return 'Client-ID oder Client-Secret fehlt.';
        }
        if (($error = $this->redirectError((string) $setting->redirect_uri)) !== null) {
            return $error;
        }
        if ($setting->provider === 'apple') {
            if (trim((string) $setting->apple_team_id) === '' || trim((string) $setting->apple_key_id) === '') {
                return 'Apple Team-ID oder Key-ID fehlt.';
            }
            $expiresAt = $this->expiryUtc($setting);
            if (! $expiresAt || now('UTC')->gte($expiresAt)) {
                return 'Das Apple Client-Secret ist abgelaufen und muss erneuert werden.';
            }
        }

        return null;
    }

    private function assertMac(SocialAuthProviderSetting $setting, ?string $secret): void
    {
        $expected = $this->mac([
            'provider' => (string) $setting->provider,
            'enabled' => (bool) $setting->enabled,
            'client_id' => trim((string) $setting->client_id),
            'secret_digest' => $secret === null ? null : hash('sha256', $secret),
            'redirect_uri' => trim((string) $setting->redirect_uri),
            'apple_team_id' => $setting->apple_team_id === null ? null : trim((string) $setting->apple_team_id),
            'apple_key_id' => $setting->apple_key_id === null ? null : trim((string) $setting->apple_key_id),
            'client_secret_expires_at' => $setting->getRawOriginal('client_secret_expires_at') === null ? null : (string) $setting->getRawOriginal('client_secret_expires_at'),
        ]);
        $stored = (string) $setting->getRawOriginal('configuration_mac');
        if (preg_match('/\A[a-f0-9]{64}\z/', $stored) !== 1 || ! hash_equals($stored, $expected)) {
            throw new RuntimeException('Die Social-Login-Einstellungen wurden ausserhalb des geschuetzten Admin-Ablaufs veraendert.');
        }
    }

    private function mac(array $material): string
    {
        ksort($material, SORT_STRING);

        return hash_hmac('sha256', json_encode($material, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $this->promotionSettings->auditKey());
    }

    private function decryptNullable(string $encrypted): ?string
    {
        if (trim($encrypted) === '') {
            return null;
        }
        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable $exception) {
            throw new RuntimeException('Das Social-Login-Client-Secret kann nicht entschluesselt werden.', 0, $exception);
        }
    }

    private function expiryUtc(SocialAuthProviderSetting $setting): ?\Illuminate\Support\Carbon
    {
        $raw = $setting->getRawOriginal('client_secret_expires_at');
        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }

        try {
            $expiresAt = \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s', (string) $raw, 'UTC');

            return $expiresAt === false ? null : $expiresAt;
        } catch (Throwable) {
            return null;
        }
    }

    private function provider(string $provider): string
    {
        $provider = mb_strtolower(trim($provider));
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new DomainException('Unbekannter Social-Login-Anbieter.');
        }

        return $provider;
    }

    private function assertGlobalAdmin(User $actor): void
    {
        if ($actor->role !== 'admin' || ! (bool) $actor->status) {
            throw new DomainException('Nur ein aktiver Volladmin darf Social-Login konfigurieren.');
        }
    }

    private function redirectError(string $url): ?string
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return 'Die Ruecksprungadresse fehlt oder ist ungueltig.';
        }
        $parts = parse_url($url);
        if (! is_array($parts) || isset($parts['user'], $parts['pass'], $parts['fragment'])) {
            return 'Die Ruecksprungadresse ist ungueltig.';
        }
        $scheme = mb_strtolower((string) ($parts['scheme'] ?? ''));
        $host = trim(mb_strtolower((string) ($parts['host'] ?? '')), '[]');
        if ($scheme === 'https' && $host !== '') {
            return null;
        }
        if ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '::1'], true) && ! app()->environment('production')) {
            return null;
        }

        return 'Die Ruecksprungadresse muss HTTPS verwenden.';
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('social_auth_provider_settings');
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array<string, mixed> */
    private function disabled(string $provider, ?string $error): array
    {
        return [
            'provider' => $provider, 'enabled' => false, 'requested_enabled' => false,
            'client_id' => '', 'has_client_secret' => false, 'redirect_uri' => '',
            'apple_team_id' => '', 'apple_key_id' => '', 'client_secret_expires_at' => null,
            'is_configured' => false, 'configuration_error' => $error,
        ];
    }
}
