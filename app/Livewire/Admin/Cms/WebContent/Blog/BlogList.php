<?php

namespace App\Livewire\Admin\Cms\WebContent\Blog;

use Livewire\Component;
use App\Models\Post;

class BlogList extends Component
{
    public $posts;

    protected $listeners = [
        'refresh' => 'loadPosts',
    ];
    public function mount()
    {
        $this->loadPosts();
    }

    public function loadPosts()
    {
        $this->posts = Post::where('type', 'blog')->latest()->get();
    }

    public function delete($id)
    {
        Post::findOrFail($id)->delete();
        $this->loadPosts();
        $this->dispatch('notify', 'Beitrag gelöscht.');
    }

    public function render()
    {
        return view('livewire.admin.cms.web-content.blog.blog-list');
    }
}
