<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Models\Mail;
use App\Models\User;
use App\Services\Admin\UserStatusService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UserProfile extends Component
{
    use RequiresRbacPermission;

    protected function requiredRbacPermission(): string
    {
        return 'users.manage';
    }

    #[Locked]
    public $userId;
    public $user;


    public $showMailModal = false; 
    public $mailUserId = null;
    public $mailSubject = ''; 
    public $mailHeader = '';
    public $mailBody = '';
    public $mailLink = '';

    public function mount($userId)
    {
        $this->userId = $userId;
        $this->loadUser();
    }

    public function loadUser()
    {
        $this->user = User::findOrFail($this->userId);
    }

    public function activateUser(UserStatusService $statuses)
    {
        $result = $this->mutateUserStatus($statuses, true);

        if ($result['changed'] === 1) {
            $this->dispatch('showAlert', 'Benutzer erfolgreich aktiviert.', 'success');
        } else {
            $this->dispatch('showAlert', 'Benutzer ist bereits aktiv.', 'info');
        }

        $this->loadUser();
    }

    public function deactivateUser(UserStatusService $statuses)
    {
        $result = $this->mutateUserStatus($statuses, false);

        if ($result['changed'] === 1) {
            $this->dispatch('showAlert', 'Benutzer erfolgreich deaktiviert.', 'success');
        } else {
            $this->dispatch('showAlert', 'Benutzer ist bereits inaktiv.', 'info');
        }

        $this->loadUser();
    }

    /** @return array{changed: int, total: int} */
    private function mutateUserStatus(UserStatusService $statuses, bool $active): array
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        return $statuses->setActive($actor, [$this->userId], $active);
    }

    public function openMailModal()
    {
        // Prüfen, ob der Benutzer vorhanden ist
        if (!$this->user) {
            $this->dispatch('showAlert', 'Benutzer nicht gefunden.', 'error');
            return;
        }
    
        $this->mailUserId = $this->user->id;
        $this->showMailModal = true;
    }
    
    public function resetMailModal()
    {
        $this->showMailModal = false;
        $this->mailUserId = null;
        $this->mailSubject = '';
        $this->mailHeader = '';
        $this->mailBody = '';
        $this->mailLink = '';
    }
    
    public function sendMail()
    {
        // Validierung mit individuellen Fehlermeldungen
        $this->validate([
            'mailSubject' => 'required|string|max:255',
            'mailHeader' => 'required|string|max:255',
            'mailBody' => 'required|string',
        ], [
            'mailSubject.required' => 'Bitte geben Sie einen Betreff ein.',
            'mailSubject.max' => 'Der Betreff darf maximal 255 Zeichen lang sein.',
            'mailHeader.required' => 'Bitte geben Sie eine Überschrift ein.',
            'mailHeader.max' => 'Die Überschrift darf maximal 255 Zeichen lang sein.',
            'mailBody.required' => 'Bitte geben Sie eine Nachricht ein.',
        ]);
    
        // Inhalte für die Datenbank vorbereiten
        $content = [
            'subject' => $this->mailSubject,
            'header' => $this->mailHeader,
            'body' => $this->mailBody,
            'link' => $this->mailLink, // Link kann optional leer sein
        ];
    
        // Mail an den gespeicherten Benutzer senden
        if ($this->user) {
            Mail::create([
                'status' => false,
                'content' => $content,
                'recipients' => [
                    [
                        'user_id' => $this->user->id,
                        'email' => $this->user->email,
                        'status' => false, // Status für den Empfänger
                    ],
                ],
            ]);
    
            $this->dispatch('showAlert', 'E-Mail wurde zur Verarbeitung an ' . $this->user->email . ' vorbereitet.', 'success');
        } else {
            $this->dispatch('showAlert', 'Benutzer nicht gefunden.', 'error');
        }
    
        // Modal zurücksetzen
        $this->resetMailModal();
    }
    

    public function render()
    {
        return view('livewire.admin.user-profile', [
            'user' => $this->user,
        ])->layout('layouts.master');
    }
}
