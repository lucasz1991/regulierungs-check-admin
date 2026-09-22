<?php

namespace App\Livewire\Admin;

class StaffProfile extends UserProfile
{
    public function mount($userId): void
    {
        parent::mount($userId);
        abort_unless(in_array($this->user->role, ['admin', 'staff'], true), 404);
    }

    protected function requiredRbacPermission(): string
    {
        return 'staff.manage';
    }
}
