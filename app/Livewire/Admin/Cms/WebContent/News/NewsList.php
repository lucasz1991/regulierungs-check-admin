<?php

namespace App\Livewire\Admin\Cms\WebContent\News;

use App\Models\Post;
use App\Models\Setting;
use App\Support\NewsCacheVersion;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class NewsList extends Component
{
    use WithPagination;

    public bool $newsEnabled = false;
    public int $perPage = 10;

    protected $listeners = [
        'refresh-news' => '$refresh',
    ];

    public function mount(): void
    {
        $this->loadVisibilitySetting();
    }

    public function loadVisibilitySetting(): void
    {
        $this->newsEnabled = Setting::enabled('webcontent', 'news_enabled', false);
    }

    public function delete(int $id): void
    {
        Post::where('type', 'news')->findOrFail($id)->delete();
        NewsCacheVersion::bump();
        $this->resetPage();
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
        NewsCacheVersion::bump();

        $this->newsEnabled = (bool) $value;
        $this->dispatch('showAlert', $this->newsEnabled ? 'News-Bereich ist jetzt sichtbar.' : 'News-Bereich ist jetzt ausgeblendet.', 'success');
    }

    public function render()
    {
        return view('livewire.admin.cms.web-content.news.news-list', [
            'posts' => Post::where('type', 'news')
                ->with('newsCategory')
                ->latest('published_at')
                ->latest()
                ->paginate($this->perPage),
        ]);
    }
}
