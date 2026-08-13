<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\User;
use App\Models\Mail;
use App\Services\Admin\UserStatusService;

class Users extends Component
{
    use RequiresRbacPermission;
    use WithPagination, WithoutUrlPagination; 

    protected function requiredRbacPermission(): string
    {
        return 'users.manage';
    }

    public $search = '';
    public $sortBy = 'name'; 
    public $sortDirection = 'asc'; 
    public $openUserId = null;
    public $usersList;
    public $selectedUsers = [];
    public $selectAll = false;
    public $action = null; 
    public $hasUsers;

    public $showMailModal = false; 
    public $mailUserId = null;
    public $mailSubject = ''; 
    public $mailHeader = '';
    public $mailBody = '';
    public $mailLink = '';

    protected $queryString = ['search', 'sortBy', 'sortDirection'];

    public function updatingSearch()
    {
        $this->resetPage();
    }


    public function sortByField($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function activateUsers(UserStatusService $statuses)
    {
        $result = $this->mutateUserStatuses($statuses, $this->selectedUsers, true);

        if ($result['changed'] === 0) {
            $this->dispatch('showAlert', 'Alle ausgewählten Benutzer sind bereits aktiv.', 'info');
        } else {
            $this->dispatch('showAlert', 'Benutzer erfolgreich aktiviert und verarbeitet.', 'success');
        }
        $this->progress = 0; // Fortschrittsanzeige zurücksetzen
    }
    
    public function deactivateUsers(UserStatusService $statuses)
    {
        $result = $this->mutateUserStatuses($statuses, $this->selectedUsers, false);

        if ($result['changed'] === 0) {
            $this->dispatch('showAlert', 'Alle ausgewählten Benutzer sind bereits inaktiv.', 'info');
        } else {
            $this->dispatch('showAlert', 'Benutzer erfolgreich deaktiviert und verarbeitet.', 'success');
        }
        $this->progress = 0; // Fortschrittsanzeige zurücksetzen
    }

    public function activateUser($userId, UserStatusService $statuses)
    {
        $result = $this->mutateUserStatuses($statuses, [$userId], true);

        if ($result['changed'] === 1) {
            $this->dispatch('showAlert', 'Benutzer erfolgreich aktiviert.', 'success');
        } else {
            $this->dispatch('showAlert', 'Benutzer ist bereits aktiv.', 'info');
        }
    }

    public function deactivateUser($userId, UserStatusService $statuses)
    {
        $result = $this->mutateUserStatuses($statuses, [$userId], false);

        if ($result['changed'] === 1) {
            $this->dispatch('showAlert', 'Benutzer erfolgreich deaktiviert.', 'success');
        } else {
            $this->dispatch('showAlert', 'Benutzer ist bereits inaktiv.', 'info');
        }
    }

    /**
     * @param  iterable<mixed>  $userIds
     * @return array{changed: int, total: int}
     */
    private function mutateUserStatuses(UserStatusService $statuses, iterable $userIds, bool $status): array
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        return $statuses->setActive($actor, $userIds, $status);
    }

    protected function updateHasUsers()
    {
        $this->hasUsers = User::query()
            ->where('role', 'guest')
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('created_at', 'like', '%' . $this->search . '%');
            })
            ->exists();
    }



    public function openMailModal($userId = null)
    {
        if ($userId) {
            // Öffne das Modal für einen einzelnen Benutzer
            $this->mailUserId = $userId;
        } else {
            // Prüfe, ob Benutzer für die Massenverarbeitung ausgewählt wurden
            if (count($this->selectedUsers) === 0) {
                $this->dispatch('showAlert', 'Bitte wähle mindestens einen Benutzer aus, um eine Mail zu senden.', 'info');
                return;
            }
        }
    
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
    
        if ($this->mailUserId) {
            // Einzelner Benutzer
            $user = User::find($this->mailUserId);
    
            if ($user) {
                // Mail speichern
                Mail::create([
                    'status' => false,
                    'content' => $content,
                    'recipients' => [
                        [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'status' => false, // Status für den Empfänger
                        ],
                    ],
                ]);
    
                $this->dispatch('showAlert', 'E-Mail wurde zur Verarbeitung an ' . $user->email . ' vorbereitet.', 'success');
            } else {
                $this->dispatch('showAlert', 'Benutzer nicht gefunden.', 'error');
            }
        } else {
            // Massenverarbeitung
            $recipients = [];
    
            foreach ($this->selectedUsers as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $recipients[] = [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'status' => false, // Status für jeden Empfänger
                    ];
                }
            }
    
            // Mail speichern
            Mail::create([
                'status' => false,
                'content' => $content,
                'recipients' => $recipients,
            ]);
    
            $this->dispatch('showAlert', 'E-Mail wurde zur Verarbeitung für ' . count($recipients) . ' Benutzer vorbereitet.', 'success');
        }
    
        // Modal zurücksetzen
        $this->resetMailModal();
    }
    
    public function toggleSelectAll()
    {
        $this->selectAll = !$this->selectAll;
    
        if ($this->selectAll) {
            // Alle Benutzer laden und IDs zur `selectedUsers`-Liste hinzufügen
            $this->selectedUsers = User::query()
                ->where('role', 'guest')
                ->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%')
                          ->orWhere('created_at', 'like', '%' . $this->search . '%');
                })
                ->pluck('id')
                ->toArray();
        } else {
            // Auswahl aufheben
            $this->selectedUsers = [];
        }
    }

    public function toggleUserSelection($userId)
    {
        if (in_array($userId, $this->selectedUsers)) {
            $this->selectedUsers = array_filter($this->selectedUsers, fn($id) => $id != $userId);
        } else {
            $this->selectedUsers[] = $userId;
        }
    }

    public function render()
    {
        $usersList = User::query()
        ->where('role', 'guest')
        ->where(function($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('created_at', 'like', '%' . $this->search . '%');
        })
        ->orderBy($this->sortBy, $this->sortDirection)
        ->paginate(10)
        ->withQueryString()
        ->setPath(url('/admin/users'));

        $this->updateHasUsers();

        return view('livewire.admin.users', [
            'users' => $usersList,
        ])->layout('layouts.master');
    }
}
