<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Models\User;
use App\Livewire\Concerns\ComposesCustomerMail;
use Illuminate\Support\Collection;
use App\Services\Admin\UserStatusService;

class Users extends Component
{
    use RequiresRbacPermission;
    use ComposesCustomerMail;
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


    protected $queryString = ['search', 'sortBy', 'sortDirection'];

    public function updatingSearch()
    {
        $this->resetPage();
        $this->selectedUsers = [];
        $this->selectAll = false;
    }


    public function sortByField($field)
    {
        if (! in_array($field, ['name', 'email', 'created_at', 'status'], true)) {
            return;
        }
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

    protected function filteredUsers()
    {
        return User::query()->where('role', 'guest')->where(function ($query) {
            $query->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')
                ->orWhere('created_at', 'like', '%'.$this->search.'%');
        })->orderBy(in_array($this->sortBy, ['name','email','created_at','status'], true) ? $this->sortBy : 'name',
            $this->sortDirection === 'desc' ? 'desc' : 'asc')->orderBy('id');
    }

    protected function selectedMailIds(?int $id): array
    {
        return $id ? [$id] : $this->selectedUsers;
    }

    protected function mailAudience(array $ids): Collection
    {
        return User::query()->where('role', 'guest')->whereIntegerInRaw('id', $ids)->get(['id', 'email']);
    }

    public function toggleSelectAll()
    {
        $this->selectedUsers = $this->filteredUsers()->pluck('id')->all();
    }

    public function selectPage(): void
    {
        $ids = $this->filteredUsers()->forPage($this->getPage(), 10)->pluck('id')->all();
        $this->selectedUsers = array_values(array_unique(array_merge($this->selectedUsers, $ids)));
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
        $usersList = $this->filteredUsers()->paginate(10)->withQueryString()->setPath(url('/admin/users'));
        $this->hasUsers = $usersList->total() > 0;

        return view('livewire.admin.users', [
            'users' => $usersList,
        ])->layout('layouts.master');
    }
}
