<?php

namespace App\Livewire\Admin\Config;

use App\Models\User;
use App\Services\Promotion\PromotionSettingsService;
use Closure;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PromotionSettings extends Component
{
    public bool $enabled = false;

    public string $redemptionBaseUrl = '';

    public int $qrTtlMinutes = 30;

    #[Locked]
    public bool $auditKeyConfigured = false;

    #[Locked]
    public bool $isConfigured = false;

    #[Locked]
    public bool $effectiveEnabled = false;

    public function boot(): void
    {
        $this->authorizeGlobalAdmin();
    }

    public function mount(PromotionSettingsService $settings): void
    {
        $this->authorizeGlobalAdmin();
        $this->fillFromSettings($settings->get());
    }

    public function hydrate(): void
    {
        $this->authorizeGlobalAdmin();
    }

    public function save(PromotionSettingsService $settings): void
    {
        $this->authorizeGlobalAdmin();

        $validated = $this->validate([
            'enabled' => ['required', 'boolean'],
            'redemptionBaseUrl' => ['required', 'string', 'max:2048', 'url', $this->redemptionUrlRule()],
            'qrTtlMinutes' => ['required', 'integer', 'min:5', 'max:120'],
        ], [
            'redemptionBaseUrl.required' => 'Bitte die öffentliche Einlöseadresse angeben.',
            'redemptionBaseUrl.url' => 'Bitte eine vollständige URL inklusive https:// angeben.',
            'qrTtlMinutes.min' => 'Ein QR-Code muss mindestens 5 Minuten gültig sein.',
            'qrTtlMinutes.max' => 'Ein QR-Code darf höchstens 120 Minuten gültig sein.',
        ]);

        $saved = $settings->save([
            'enabled' => (bool) $validated['enabled'],
            'redemption_base_url' => rtrim(trim((string) $validated['redemptionBaseUrl']), '/'),
            'qr_ttl_minutes' => (int) $validated['qrTtlMinutes'],
        ]);

        $this->fillFromSettings($saved);
        $this->dispatch('showAlert', 'Promotion-Einstellungen wurden sicher gespeichert.', 'success');
    }

    public function render()
    {
        return view('livewire.admin.config.promotion-settings');
    }

    private function authorizeGlobalAdmin(): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->isAdmin() && $user->isActive(), 403);
    }

    /** @param array<string, mixed> $settings */
    private function fillFromSettings(array $settings): void
    {
        $this->enabled = (bool) ($settings['requested_enabled'] ?? false);
        $this->redemptionBaseUrl = (string) ($settings['redemption_base_url'] ?? '');
        $this->qrTtlMinutes = (int) ($settings['qr_ttl_minutes'] ?? 30);
        $this->auditKeyConfigured = (bool) ($settings['audit_key_configured'] ?? false);
        $this->isConfigured = (bool) ($settings['is_configured'] ?? false);
        $this->effectiveEnabled = (bool) ($settings['enabled'] ?? false);
    }

    private function redemptionUrlRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $scheme = strtolower((string) parse_url((string) $value, PHP_URL_SCHEME));
            $host = trim(strtolower((string) parse_url((string) $value, PHP_URL_HOST)), '[]');
            $parts = parse_url((string) $value);

            if (! is_array($parts)
                || isset($parts['user'], $parts['pass'])
                || isset($parts['query'])
                || isset($parts['fragment'])) {
                $fail('Die Einlöseadresse darf keine Zugangsdaten, Query oder Fragment enthalten.');

                return;
            }

            if (app()->environment('production') && $scheme !== 'https') {
                $fail('In Produktion muss die Einlöseadresse HTTPS verwenden.');

                return;
            }

            if ($scheme === 'http' && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                $fail('HTTP ist nur lokal für localhost, 127.0.0.1 oder ::1 erlaubt.');
            }
        };
    }
}
