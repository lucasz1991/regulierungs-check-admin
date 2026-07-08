<?php

namespace App\Livewire\Admin\Cms\WebContent\News;

use App\Models\Post;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NewsList extends Component
{
    public $posts;
    public bool $newsEnabled = false;

    protected $listeners = [
        'refresh-news' => 'loadPosts',
    ];

    public function mount(): void
    {
        $this->loadVisibilitySetting();
        $this->loadPosts();
    }

    public function loadVisibilitySetting(): void
    {
        $this->newsEnabled = Setting::enabled('webcontent', 'news_enabled', false);
    }

    public function loadPosts(): void
    {
        $this->posts = Post::where('type', 'news')
            ->latest('published_at')
            ->latest()
            ->get();
    }

    public function delete(int $id): void
    {
        Post::where('type', 'news')->findOrFail($id)->delete();
        $this->loadPosts();
    }

    public function updatedNewsEnabled($value): void
    {
        DB::table('settings')->updateOrInsert(
            ['type' => 'webcontent', 'key' => 'news_enabled'],
            [
                'value' => $value ? '1' : '0',
                'updated_at' => now(),
            ]
        );

        $this->newsEnabled = (bool) $value;
        $this->dispatch('showAlert', $this->newsEnabled ? 'News-Bereich ist jetzt sichtbar.' : 'News-Bereich ist jetzt ausgeblendet.', 'success');
    }

    public function render()
    {
        return view('livewire.admin.cms.web-content.news.news-list');
    }
}
