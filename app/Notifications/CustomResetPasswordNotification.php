<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends ResetPassword
{
    public function __construct(mixed $user, string $token)
    {
        parent::__construct($token);
    }

    public function toMail($notifiable): MailMessage
    {
        $url = rtrim((string) config('app.url'), '/').route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);

        return (new MailMessage)
            ->from(config('mail.from.address'), 'Regulierungs-CHECK')
            ->subject('Passwort zurücksetzen | Regulierungs-CHECK Mitarbeiterzugang')
            ->view('emails.auth.reset-password', [
                'name' => $notifiable->name,
                'resetUrl' => $url,
                'expiresIn' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                'accountLabel' => 'Mitarbeiterzugang zum Admin-System',
                'appUrl' => rtrim((string) config('app.url'), '/'),
            ]);
    }
}
