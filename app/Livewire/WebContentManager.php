<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;


class WebContentManager extends Component
{
    public string $selectedTab = 'webpages';

    public function mount()
    {
        if (request()->routeIs('admin.webcontent.news')) {
            $this->selectedTab = 'news';
        }

        Gate::authorize($this->permissionForTab($this->selectedTab));
    }

    /**
     * This component is route- and tab-dynamic. A trait boot hook would run
     * before Livewire hydrates selectedTab and could therefore check the
     * default webpages permission for an existing news-only snapshot.
     */
    public function hydrate(): void
    {
        Gate::authorize($this->permissionForTab($this->selectedTab));
    }

    public function updatedSelectedTab(string $tab): void
    {
        Gate::authorize($this->permissionForTab($tab));
    }

    public function render()
    {
        Gate::authorize($this->permissionForTab($this->selectedTab));

        return view('livewire.web-content-manager')->layout('layouts.master');
    }

    private function permissionForTab(string $tab): string
    {
        return match ($tab) {
            'webpages', 'faq', 'blog' => 'content.web.manage',
            'module' => 'content.pagebuilder.manage',
            'news' => 'content.news.manage',
            'tools' => 'settings.manage',
            default => abort(404),
        };
    }
}
