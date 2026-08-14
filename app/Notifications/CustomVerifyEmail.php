<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        return (new MailMessage)
            ->subject('E-Mail-Adresse für Regulierungs-CHECK bestätigen')
            ->greeting('Hallo '.$notifiable->name.',')
            ->line('bitte bestätige deine E-Mail-Adresse für deinen Regulierungs-CHECK Zugang.')
            ->action('E-Mail-Adresse bestätigen', $verificationUrl)
            ->line('Der Link ist zeitlich begrenzt. Falls du diese Aktion nicht angefordert hast, kannst du die Nachricht ignorieren.')
            ->salutation('Viele Grüße, dein Regulierungs-CHECK Team');
    }
}
