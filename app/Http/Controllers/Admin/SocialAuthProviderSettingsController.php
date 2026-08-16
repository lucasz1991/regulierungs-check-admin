<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Promotion\PromotionSettingsService;
use App\Services\Promotion\SocialAuthProviderSettingsService;
use App\Support\Promotion\AppleClientSecretFactory;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use RuntimeException;

class SocialAuthProviderSettingsController extends Controller
{
    public function saveGoogle(Request $request, SocialAuthProviderSettingsService $settings): RedirectResponse
    {
        $actor = $this->globalAdmin($request);
        $validated = $request->validateWithBag('googleSocial', [
            'google.enabled' => ['nullable', 'boolean'],
            'google.client_id' => ['nullable', 'string', 'max:255'],
            'google.client_secret' => ['nullable', 'string', 'max:4096'],
            'google.redirect_uri' => ['nullable', 'string', 'max:2048', $this->redirectUriRule('google')],
        ])['google'] ?? [];

        $settings->save('google', [
            'enabled' => $request->boolean('google.enabled'),
            'client_id' => $validated['client_id'] ?? '',
            'client_secret' => $validated['client_secret'] ?? '',
            'redirect_uri' => $validated['redirect_uri'] ?? '',
        ], $actor);

        return $this->success('Google-Anmeldung wurde sicher gespeichert.');
    }

    public function saveApple(
        Request $request,
        SocialAuthProviderSettingsService $settings,
        AppleClientSecretFactory $secrets,
    ): RedirectResponse {
        $actor = $this->globalAdmin($request);
        $requiresSigningClaims = $request->hasFile('apple.private_key');
        $validated = $request->validateWithBag('appleSocial', [
            'apple.enabled' => ['nullable', 'boolean'],
            'apple.client_id' => [Rule::requiredIf($requiresSigningClaims), 'nullable', 'string', 'max:255'],
            'apple.apple_team_id' => [Rule::requiredIf($requiresSigningClaims), 'nullable', 'string', 'max:32', 'regex:/\A[A-Za-z0-9]+\z/'],
            'apple.apple_key_id' => [Rule::requiredIf($requiresSigningClaims), 'nullable', 'string', 'max:32', 'regex:/\A[A-Za-z0-9]+\z/'],
            'apple.redirect_uri' => ['nullable', 'string', 'max:2048', $this->redirectUriRule('apple')],
            'apple.private_key' => ['nullable', 'file', 'max:64'],
        ], [
            'apple.private_key.max' => 'Die Apple-.p8-Datei ist unerwartet groß.',
            'apple.client_id.required' => 'Für die .p8-Datei wird die Apple Services ID benötigt.',
            'apple.apple_team_id.required' => 'Für die .p8-Datei wird die Apple Team ID benötigt.',
            'apple.apple_key_id.required' => 'Für die .p8-Datei wird die Apple Key ID benötigt.',
            'apple.apple_team_id.regex' => 'Die Apple Team ID darf nur Buchstaben und Zahlen enthalten.',
            'apple.apple_key_id.regex' => 'Die Apple Key ID darf nur Buchstaben und Zahlen enthalten.',
        ])['apple'] ?? [];

        $generatedSecret = null;
        $expiresAt = null;
        $privateKeyUpload = $request->file('apple.private_key');

        if ($privateKeyUpload instanceof UploadedFile) {
            if (mb_strtolower($privateKeyUpload->getClientOriginalExtension()) !== 'p8') {
                return back()->withErrors(['apple.private_key' => 'Bitte ausschließlich die originale Apple-.p8-Datei hochladen.'], 'appleSocial')
                    ->withInput($request->except(['google.client_secret', 'apple.private_key']));
            }

            $privateKey = file_get_contents($privateKeyUpload->getRealPath());
            if (! is_string($privateKey) || trim($privateKey) === '') {
                return back()->withErrors(['apple.private_key' => 'Die Apple-.p8-Datei konnte nicht gelesen werden.'], 'appleSocial')
                    ->withInput($request->except(['google.client_secret', 'apple.private_key']));
            }

            try {
                try {
                    $generated = $secrets->make(
                        (string) ($validated['client_id'] ?? ''),
                        (string) ($validated['apple_team_id'] ?? ''),
                        (string) ($validated['apple_key_id'] ?? ''),
                        $privateKey,
                    );
                    $generatedSecret = $generated['secret'];
                    $expiresAt = $generated['expires_at'];
                } catch (RuntimeException $exception) {
                    return back()->withErrors(['apple.private_key' => $exception->getMessage()], 'appleSocial')
                        ->withInput($request->except(['google.client_secret', 'apple.private_key']));
                }
            } finally {
                if (function_exists('sodium_memzero')) {
                    sodium_memzero($privateKey);
                } else {
                    $privateKey = str_repeat("\0", strlen($privateKey));
                }

                unset($privateKey);
            }
        }

        $values = [
            'enabled' => $request->boolean('apple.enabled'),
            'client_id' => $validated['client_id'] ?? '',
            'redirect_uri' => $validated['redirect_uri'] ?? '',
            'apple_team_id' => $validated['apple_team_id'] ?? '',
            'apple_key_id' => $validated['apple_key_id'] ?? '',
        ];
        if ($generatedSecret !== null && $expiresAt !== null) {
            $values['client_secret'] = $generatedSecret;
            $values['client_secret_expires_at'] = $expiresAt;
        }

        $settings->save('apple', $values, $actor);

        return $this->success('Apple-Anmeldung wurde sicher gespeichert. Die .p8-Datei wurde nicht gespeichert.');
    }

    private function globalAdmin(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->isAdmin() && $actor->isActive(), 403);

        return $actor;
    }

    private function success(string $message): RedirectResponse
    {
        return redirect()->to(route('admin.config').'#promotion')->with('status', $message);
    }

    private function redirectUriRule(string $provider): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($provider): void {
            $value = trim((string) $value);
            if ($value === '') {
                return;
            }

            $parts = parse_url($value);
            if (! is_array($parts) || filter_var($value, FILTER_VALIDATE_URL) === false || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])) {
                $fail('Bitte eine vollständige Rücksprungadresse ohne Zugangsdaten, Query oder Fragment angeben.');

                return;
            }

            $scheme = mb_strtolower((string) ($parts['scheme'] ?? ''));
            $host = trim(mb_strtolower((string) ($parts['host'] ?? '')), '[]');
            $local = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
            if ($scheme !== 'https' && ! ($scheme === 'http' && $local && ! app()->environment('production'))) {
                $fail('Die Rücksprungadresse muss HTTPS verwenden; HTTP ist nur lokal erlaubt.');

                return;
            }

            $baseUrl = (string) (app(PromotionSettingsService::class)->get()['redemption_base_url'] ?? '');
            $baseParts = parse_url($baseUrl);
            $baseScheme = mb_strtolower((string) (is_array($baseParts) ? ($baseParts['scheme'] ?? '') : ''));
            $baseHost = trim(mb_strtolower((string) (is_array($baseParts) ? ($baseParts['host'] ?? '') : '')), '[]');
            $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
            $basePort = (int) (is_array($baseParts)
                ? ($baseParts['port'] ?? ($baseScheme === 'https' ? 443 : 80))
                : 0);
            if ($baseHost !== '' && ($scheme !== $baseScheme || $host !== $baseHost || $port !== $basePort)) {
                $fail('Die RÃ¼cksprungadresse muss dieselbe Origin wie die Teilnehmerseite verwenden.');

                return;
            }

            $expectedPath = '/auth/'.$provider.'/callback';
            if (rtrim((string) ($parts['path'] ?? ''), '/') !== $expectedPath) {
                $fail('Die Rücksprungadresse muss auf '.$expectedPath.' enden.');
            }
        };
    }
}
