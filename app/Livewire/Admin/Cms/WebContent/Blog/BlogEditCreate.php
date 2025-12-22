<?php

namespace App\Livewire\Admin\Cms\WebContent\Blog;

use App\Models\Post;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Http\Request;
use App\Http\Controllers\MediaController;
use App\Models\BlogCategory;

class BlogEditCreate extends Component
{
    use WithFileUploads;

    public $show = false;
    public $postId = null;
    public $post = null;

    public $title;
    public $type = 'blog';
    public $body;
    public $excerpt;
    public $category_id;
    public $published = false;
    public $published_at;
    public $cover_image;
    public $cover_image_file;

    public $categories = [];
    public $newCategoryName;
    public $newCategorySlug;

    protected $listeners = ['open-blog-modal' => 'loadPost'];

    public function mount()
    {
        $this->categories = BlogCategory::all();
    }
    public function loadPost($postId = null)
    {
        $this->resetForm();

        if ($postId) {
            $this->postId = $postId;
            $this->post = Post::findOrFail($postId);
            $this->fill($this->post->toArray());
        } else {
            $this->published_at = now()->toDateTimeString();
        }
        $this->show = true;
    }

    public function resetForm()
    {
        $this->reset([
            'title', 'body', 'excerpt', 'category_id',
            'published', 'published_at', 'cover_image',
            'cover_image_file', 'postId', 'post', 'type'
        ]);
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|min:3',
            'type' => 'required|in:blog,news,info',
            'body' => 'required',
            'excerpt' => 'nullable|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'published' => 'boolean',
            'published_at' => 'nullable|date',
            'cover_image_file' => 'nullable|image|max:4096',
        ]);

        if ($this->cover_image_file) {
            $this->cover_image = $this->uploadImageViaMediaController($this->cover_image_file);
        }

        $data = $this->only([
            'title',
            'body',
            'excerpt',
            'category_id',
            'published',
            'published_at',
            'cover_image',
            'type',
        ]);

        $data['user_id'] = auth()->id();

        if ($this->postId) {
            Post::findOrFail($this->postId)->update($data);
        } else {
            Post::create($data);
        }
        $this->resetForm();
        $this->dispatch('refresh')->to('admin.cms.web-content.blog.blog-list');
        $this->show = false;
    }

    protected function uploadImageViaMediaController($file)
    {
        // Temporäres Request-Objekt mit dem File als 'file'
        $request = Request::create('/admin/media/upload', 'POST', ['disk' => $disk], [], ['file' => $file]);

        // MediaController manuell instanziieren und aufrufen
        $controller = new MediaController();
        $response = $controller->store($request);

        if (method_exists($response, 'getData')) {
            return $response->getData(true)['path'] ?? '';
        }
        throw new \Exception('Upload fehlgeschlagen.');
    }



    public function createNewCategory()
    {
        $this->validate([
            'newCategoryName' => 'required|min:2',
        ]);

        $category = BlogCategory::create([
            'name' => $this->newCategoryName,
            'slug' => $this->newCategorySlug ?: \Str::slug($this->newCategoryName),
        ]);

        $this->category_id = $category->id;
        $this->reset(['newCategoryName', 'newCategorySlug']);

        // Kategorie-Liste neu laden (falls du sie im Mount geladen hast)
        $this->categories = BlogCategory::all();

        $this->dispatch('close-new-category');
    }


    public function render()
    {
        return view('livewire.admin.cms.web-content.blog.blog-edit-create');
    }
}
