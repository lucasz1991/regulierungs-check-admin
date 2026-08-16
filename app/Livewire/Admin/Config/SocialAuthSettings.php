<?php

namespace App\Livewire\Admin\Config;

use App\Models\User;
use App\Services\Promotion\PromotionSettingsService;
use App\Services\Promotion\SocialAuthProviderSettingsService;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class SocialAuthSettings extends Component
{
    #[Locked]
    public bool $schemaReady = false;

    #[Locked]
    public array $google = [];

    #[Locked]
    public array $apple = [];

    #[Locked]
    public ?string $configurationError = null;

    public function boot(): void
    {
        $this->authorizeGlobalAdmin();
    }

    public function mount(SocialAuthProviderSettingsService $social, PromotionSettingsService $promotion): void
    {
        $this->authorizeGlobalAdmin();
        $this->schemaReady = Schema::hasTable('social_auth_provider_settings');

        $baseUrl = rtrim((string) ($promotion->get()['redemption_base_url'] ?? ''), '/');
        $fallback = static fn (string $provider): string => $baseUrl !== '' ? $baseUrl.'/auth/'.$provider.'/callback' : '';

        if (! $this->schemaReady) {
            $this->google = $this->emptyStatus($fallback('google'));
            $this->apple = $this->emptyStatus($fallback('apple'));

            return;
        }

        try {
            $this->google = $this->normalizeStatus($social->get('google'));
            $this->apple = $this->normalizeStatus($social->get('apple'));
            if ($this->google['redirect_uri'] === '') {
                $this->google['redirect_uri'] = $fallback('google');
            }
            if ($this->apple['redirect_uri'] === '') {
                $this->apple['redirect_uri'] = $fallback('apple');
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->configurationError = 'Die Social-Login-Einstellungen konnten nicht sicher geprüft werden. Änderungen bleiben gesperrt.';
            $this->google = $this->emptyStatus($fallback('google'));
            $this->apple = $this->emptyStatus($fallback('apple'));
        }
    }

    public function hydrate(): void
    {
        $this->authorizeGlobalAdmin();
    }

    public function render()
    {
        return view('livewire.admin.config.social-auth-settings');
    }

    private function authorizeGlobalAdmin(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->isAdmin() && $user->isActive(), 403);
    }

    private function emptyStatus(string $redirectUri): array
    {
        return [
            'configured' => false,
            'enabled' => false,
            'client_id' => '',
            'redirect_uri' => $redirectUri,
            'apple_team_id' => '',
            'apple_key_id' => '',
            'expires_at' => null,
            'expired' => false,
        ];
    }

    /** @param array<string, mixed> $status */
    private function normalizeStatus(array $status): array
    {
        $expiresAt = $status['client_secret_expires_at'] ?? null;

        return [
            'configured' => (bool) ($status['has_client_secret'] ?? false),
            'enabled' => (bool) ($status['requested_enabled'] ?? false),
            'client_id' => (string) ($status['client_id'] ?? ''),
            'redirect_uri' => (string) ($status['redirect_uri'] ?? ''),
            'apple_team_id' => (string) ($status['apple_team_id'] ?? ''),
            'apple_key_id' => (string) ($status['apple_key_id'] ?? ''),
            'expires_at' => $expiresAt,
            'expired' => $expiresAt?->isPast() ?? false,
            'configuration_error' => $status['configuration_error'] ?? null,
        ];
    }
}
