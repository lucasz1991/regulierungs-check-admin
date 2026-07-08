<?php

namespace App\Livewire;

use Livewire\Component;

class Welcome extends Component
{
    public function render()
    {
        if (auth()->user() && auth()->user()->role === 'admin') {
            $this->redirect(route('admin.index'), navigate: true);
        }

        return view('livewire.welcome')->layout('layouts.app');
    }
}
