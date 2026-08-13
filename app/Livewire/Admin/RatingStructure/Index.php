<?php

namespace App\Livewire\Admin\RatingStructure;

use App\Livewire\Concerns\RequiresRbacPermission;
use Livewire\Component;

class Index extends Component
{
    use RequiresRbacPermission;

    protected function requiredRbacPermission(): string
    {
        return 'ratings.structure.manage';
    }

    public function render()
    {
        return view('livewire.admin.rating-structure.index')->layout('layouts.master');
    }
}
