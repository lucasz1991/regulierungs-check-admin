<?php

namespace App\Mail;

use App\Models\PromotionSpinResult;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PromotionDigitalDeliveryMail extends Mailable
{
    public function __construct(public readonly PromotionSpinResult $result, public readonly string $type, public readonly string $participantUrl, public readonly ?string $code = null) {}
    public function envelope(): Envelope { return new Envelope(subject: match ($this->type) { 'approved' => 'Dein Gewinn wurde bestätigt', 'profile_required' => 'Bitte vervollständige dein Profil für deinen Gewinn', 'code' => 'Dein Amazon-Gutscheincode', default => 'Information zu deinem Glücksrad-Gewinn', }); }
    public function content(): Content { return new Content(view: 'emails.promotion.digital-delivery'); }
}
