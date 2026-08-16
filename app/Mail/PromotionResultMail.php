<?php

namespace App\Mail;

use App\Enums\PromotionOutcomeType;
use App\Models\PromotionSpinResult;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PromotionResultMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly PromotionSpinResult $result,
        public readonly bool $correction = false,
        public readonly string $participantUrl = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.promotion.result');
    }

    private function subjectLine(): string
    {
        $prefix = $this->correction ? 'Korrektur: ' : '';
        $outcome = $this->result->outcome_type_snapshot;
        $isNoWin = $outcome === PromotionOutcomeType::NoWin
            || ($outcome instanceof \BackedEnum && $outcome->value === PromotionOutcomeType::NoWin->value);

        return $prefix.($isNoWin
            ? 'Dein Ergebnis beim Regulierungs-CHECK Glücksrad'
            : 'Dein Gewinn beim Regulierungs-CHECK Glücksrad');
    }
}
