<?php

namespace App\Services;

use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;

class NewsletterMailPreview
{
    public function render(array $content): string
    {
        return view('livewire.admin.customer-mail-preview', $this->viewData($content))->render();
    }

    public function renderText(array $content): string
    {
        return (string) app(Markdown::class)->renderText('mail::notification', $this->viewData($content));
    }

    private function viewData(array $content): array
    {
        $greeting = $content['header'] === 'default' ? __('Hello!') : $content['header'];
        $message = (new MailMessage)->subject($content['subject'])->greeting($greeting)
            ->lines(preg_split('/\R\h*\R/u', trim($content['body'])));
        if (filled($content['link'] ?? null)) {
            $label = trim($content['button_label'] ?? '');
            $message->action($label === '' ? 'Weiter' : $label, $content['link']);
        }

        return $message->data() + [
            'signoffLines' => ['Viele Grüße'],
            'newsletter' => $content['newsletter'],
        ];
    }
}
