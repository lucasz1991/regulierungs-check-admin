<?php

namespace App\Notifications;

use App\Services\Promotion\PromotionSettingsService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $baseUrl = app(PromotionSettingsService::class)->redemptionBaseUrl();

        URL::forceRootUrl($baseUrl);
        try {
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );
        } finally {
            URL::forceRootUrl(null);
        }

        return (new MailMessage)
            ->subject('Bestaetige deine E-Mail-Adresse')
            ->greeting('Willkommen bei Regulierungs-CHECK!')
            ->line('Bitte bestaetige deine E-Mail-Adresse, bevor ein Gewinn ausgegeben werden darf.')
            ->action('E-Mail bestaetigen', $verificationUrl)
            ->line('Falls du diese Aktion nicht angefordert hast, ignoriere diese Nachricht.');
    }
}
