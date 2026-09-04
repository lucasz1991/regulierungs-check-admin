<?php

namespace App\Mail;

use App\Models\StaffInvitation;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly StaffInvitation $invitation,
        public readonly string $acceptUrl,
    ) {}

    public function build(): self
    {
        return $this->subject('Ihr Mitarbeiterzugang für Regulierungs-CHECK')
            ->view('mail.staff-invitation');
    }
}
