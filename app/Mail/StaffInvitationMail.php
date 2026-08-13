<?php

namespace App\Mail;

use App\Models\StaffInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly StaffInvitation $invitation,
        public readonly string $acceptUrl,
    ) {}

    public function build(): self
    {
        return $this->subject('Einladung zum Promotion-Team')
            ->view('mail.staff-invitation');
    }
}
