<?php

namespace App\Livewire\Concerns;

use App\Models\Contact;
use App\Models\Mail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

trait ComposesCustomerMail
{
    public bool $showMailModal = false;

    public bool $mailPreview = false;

    public string $mailType = 'mail';

    public string $mailSubject = '';

    public string $mailHeader = '';

    public string $mailBody = '';

    public string $mailLink = '';

    #[Locked]
    public array $mailIds = [];

    #[Locked]
    public string $mailToken = '';

    abstract protected function selectedMailIds(?int $id): array;

    abstract protected function mailAudience(array $ids): Collection;

    protected function externalMailOnly(): bool
    {
        return false;
    }

    public function openMailModal(?int $id = null): void
    {
        Gate::authorize('mails.manage');
        $this->resetMailModal();
        $this->mailIds = array_values(array_unique($this->selectedMailIds($id)));
        if (! $this->mailIds) {
            $this->dispatch('showAlert', 'Bitte mindestens einen Empfänger auswählen.', 'info');

            return;
        }
        $this->mailToken = (string) Str::uuid();
        $this->showMailModal = true;
    }

    public function resetMailModal(): void
    {
        $this->reset('showMailModal', 'mailIds', 'mailToken', 'mailPreview', 'mailType', 'mailSubject', 'mailHeader', 'mailBody', 'mailLink');
        $this->resetValidation();
    }

    protected function mailRecipients(): Collection
    {
        return $this->mailAudience($this->mailIds)
            ->filter(function ($person) {
                if ($person instanceof Contact && $person->mail_permission !== 'allowed') {
                    return false;
                }

                return $this->mailType === 'message' || filter_var(trim($person->email ?? ''), FILTER_VALIDATE_EMAIL);
            })
            ->unique(fn ($p) => $this->mailType === 'mail' ? strtolower(trim($p->email)) : $p->getKey())
            ->map(fn ($p) => [
                ($p instanceof Contact ? 'contact_id' : 'user_id') => $p->getKey(),
                'email' => trim($p->email ?? ''), 'status' => false, 'state' => 'pending',
            ])->values();
    }

    protected function validateMailDraft(): void
    {
        Gate::authorize('mails.manage');
        abort_unless($this->mailToken && $this->mailIds, 422);
        $this->validate([
            'mailType' => ['required', Rule::in($this->externalMailOnly() ? ['mail'] : ['mail', 'message', 'both'])],
            'mailSubject' => 'required|string|max:255',
            'mailHeader' => 'exclude_if:mailType,message|required|string|max:255',
            'mailBody' => 'required|string|max:50000',
            'mailLink' => 'exclude_if:mailType,message|nullable|url:http,https|max:2048',
        ]);
    }

    public function previewMail(): void
    {
        $this->validateMailDraft();
        $this->mailPreview = true;
    }

    protected function customerMailPreviewHtml(): string
    {
        $message = (new MailMessage)->subject($this->mailSubject)->greeting($this->mailHeader)->line($this->mailBody);
        if ($this->mailLink !== '') {
            $message->action('Weiter', $this->mailLink);
        }

        return (string) $message->salutation('Mit freundlichen Grüßen, dein Regulierungs-CHECK Team')->render();
    }

    public function sendMail(): void
    {
        $this->validateMailDraft();
        $recipients = $this->mailRecipients();
        if ($recipients->isEmpty()) {
            $this->addError('mailIds', 'Keine für diesen Kanal erreichbaren Empfänger ausgewählt.');

            return;
        }
        $content = ['subject' => $this->mailSubject, 'header' => $this->mailType === 'message' ? '' : $this->mailHeader, 'body' => $this->mailBody,
            'link' => $this->mailType === 'message' ? '' : $this->mailLink, 'from_user_id' => auth()->id()];

        DB::transaction(function () use ($recipients, $content) {
            foreach ($recipients->chunk(50) as $batch => $items) {
                $mail = Mail::firstOrCreate(['dispatch_key' => $this->mailToken.'-'.$batch], [
                    'type' => $this->mailType, 'content' => $content,
                    'recipients' => $items->values()->all(), 'status' => false,
                ]);
                if (! $mail->wasRecentlyCreated) {
                    // All batches for this form were committed in one transaction.
                    return;
                }
            }
        });
        $this->dispatch('showAlert', 'Versand für '.$recipients->count().' Empfänger eingeplant.', 'success');
        $this->resetMailModal();
    }
}
