<?php

namespace App\Livewire;

use Livewire\Component;


class WebContentManager extends Component
{
    public string $selectedTab = 'webpages';

    public function mount()
    {
        if (request()->routeIs('admin.webcontent.news')) {
            $this->selectedTab = 'news';
        }
    }

    public function render()
    {
        return view('livewire.web-content-manager')->layout('layouts.master');
    }
}
