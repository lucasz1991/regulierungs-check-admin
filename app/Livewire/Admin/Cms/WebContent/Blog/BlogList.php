<?php

namespace App\Livewire\Admin\Cms\WebContent\Blog;

use App\Models\Post;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BlogList extends Component
{
    public $posts;

    public bool $blogEnabled = false;

    protected $listeners = [
        'refresh' => 'loadPosts',
    ];

    public function mount(): void
    {
        $this->loadVisibilitySetting();
        $this->loadPosts();
    }

    public function loadVisibilitySetting(): void
    {
        $this->blogEnabled = Setting::enabled('webcontent', 'blog_enabled', false);
    }

    public function loadPosts(): void
    {
        $this->posts = Post::where('type', 'blog')->latest()->get();
    }

    public function updatedBlogEnabled($value): void
    {
        DB::table('settings')->updateOrInsert(
            ['type' => 'webcontent', 'key' => 'blog_enabled'],
            [
                'value' => $value ? '1' : '0',
                'updated_at' => now(),
            ]
        );

        $this->blogEnabled = (bool) $value;
        $message = $this->blogEnabled
            ? 'Blog ist jetzt im User-Bereich sichtbar.'
            : 'Blog ist jetzt im User-Bereich ausgeblendet.';

        $this->dispatch('showAlert', $message, 'success');
    }

    public function delete($id): void
    {
        Post::where('type', 'blog')->findOrFail($id)->delete();
        $this->loadPosts();
    }

    public function render()
    {
        return view('livewire.admin.cms.web-content.blog.blog-list');
    }
}
