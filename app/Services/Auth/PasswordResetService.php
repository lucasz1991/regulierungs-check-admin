<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public const SENT_MESSAGE = 'Falls ein aktives Konto mit dieser E-Mail-Adresse existiert, erhältst du einen Link zum Zurücksetzen deines Passworts.';

    public function send(string $email): string
    {
        $email = Str::lower(trim($email));
        Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']], [
            'email.required' => 'Bitte gib deine E-Mail-Adresse ein.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'email.max' => 'Die E-Mail-Adresse darf höchstens 255 Zeichen lang sein.',
        ])->validate();

        $key = 'password-reset:'.hash('sha256', (string) request()->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Zu viele Anfragen. Bitte versuche es in einer Minute erneut.']);
        }
        RateLimiter::hit($key, 60);

        try {
            return Password::broker()->sendResetLink($this->credentials($email));
        } catch (\Throwable $exception) {
            // Transport errors can contain credentials; log only the exception type.
            logger()->error('Password reset mail delivery failed.', ['exception' => $exception::class]);
            throw ValidationException::withMessages(['email' => 'Die E-Mail konnte gerade nicht versendet werden. Bitte versuche es später erneut.']);
        }
    }

    public function reset(array $input): string
    {
        if (is_string($input['email'] ?? null)) {
            $input['email'] = Str::lower(trim($input['email']));
        }
        Validator::make($input, [
            'email' => ['required', 'string', 'email', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:10', 'regex:/[A-Z]/', 'regex:/[\\W]/', 'confirmed'],
        ], [
            'email.required' => 'Bitte gib deine E-Mail-Adresse ein.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'token.required' => 'Der Reset-Link ist unvollständig. Bitte fordere einen neuen Link an.',
            'password.required' => 'Bitte gib ein neues Passwort ein.',
            'password.min' => 'Das Passwort muss mindestens 10 Zeichen lang sein.',
            'password.regex' => 'Das Passwort muss mindestens einen Großbuchstaben und ein Sonderzeichen enthalten.',
            'password.confirmed' => 'Die Passwort-Bestätigung stimmt nicht überein.',
        ])->validate();

        $key = 'password-reset-submit:'.hash('sha256', (string) request()->ip());
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['email' => 'Zu viele Versuche. Bitte warte eine Minute.']);
        }
        RateLimiter::hit($key, 60);

        return Password::broker()->reset(
            array_merge($this->credentials($input['email']), [
                'token' => $input['token'],
                'password' => $input['password'],
                'password_confirmation' => $input['password_confirmation'],
            ]),
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])
                    ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            },
        );
    }

    private function credentials(string $email): array
    {
        return ['email' => $email, 'role' => ['admin', 'staff'], 'status' => true];
    }
}
