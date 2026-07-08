<?php

namespace App\Livewire\Admin\Cms\WebContent\News;

use App\Http\Controllers\MediaController;
use App\Models\Post;
use Illuminate\Http\Request;
use Livewire\Component;
use Livewire\WithFileUploads;

class NewsEditCreate extends Component
{
    use WithFileUploads;

    public bool $show = false;
    public ?int $postId = null;
    public ?Post $post = null;

    public string $title = '';
    public string $type = 'news';
    public string $body = '';
    public ?string $excerpt = null;
    public bool $published = false;
    public ?string $published_at = null;
    public string $layout = 'image_top';
    public array $images = [];
    public array $imageFiles = [];

    protected $listeners = [
        'open-news-modal' => 'loadPost',
    ];

    public function loadPost($postId = null): void
    {
        $this->resetForm();

        if ($postId) {
            $this->postId = (int) $postId;
            $this->post = Post::where('type', 'news')->findOrFail($this->postId);
            $this->title = (string) $this->post->title;
            $this->body = (string) $this->post->body;
            $this->excerpt = $this->post->excerpt;
            $this->published = (bool) $this->post->published;
            $this->published_at = optional($this->post->published_at)->format('Y-m-d\TH:i');
            $this->layout = in_array($this->post->layout, Post::NEWS_LAYOUTS, true) ? $this->post->layout : 'image_top';
            $this->images = $this->post->newsImages();
        } else {
            $this->published_at = now()->format('Y-m-d\TH:i');
        }

        $this->show = true;
    }

    public function resetForm(): void
    {
        $this->reset([
            'postId',
            'post',
            'title',
            'body',
            'excerpt',
            'published',
            'published_at',
            'layout',
            'images',
            'imageFiles',
        ]);

        $this->type = 'news';
        $this->layout = 'image_top';
        $this->published = false;
    }

    public function removeImage(int $index): void
    {
        if (!isset($this->images[$index])) {
            return;
        }

        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    public function moveImageUp(int $index): void
    {
        if ($index <= 0 || !isset($this->images[$index], $this->images[$index - 1])) {
            return;
        }

        [$this->images[$index - 1], $this->images[$index]] = [$this->images[$index], $this->images[$index - 1]];
    }

    public function moveImageDown(int $index): void
    {
        if (!isset($this->images[$index], $this->images[$index + 1])) {
            return;
        }

        [$this->images[$index + 1], $this->images[$index]] = [$this->images[$index], $this->images[$index + 1]];
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|min:3',
            'body' => 'required',
            'excerpt' => 'nullable|string',
            'published' => 'boolean',
            'published_at' => 'nullable|date',
            'layout' => 'required|in:' . implode(',', Post::NEWS_LAYOUTS),
            'images.*.alt' => 'nullable|string|max:255',
            'images.*.caption' => 'nullable|string|max:255',
            'imageFiles.*' => 'nullable|image|max:4096',
        ]);

        foreach ($this->imageFiles as $file) {
            $this->images[] = [
                'path' => $this->uploadImageViaMediaController($file),
                'alt' => $this->title,
                'caption' => null,
                'sort' => count($this->images),
            ];
        }

        $images = collect($this->images)
            ->filter(fn ($image) => is_array($image) && !empty($image['path']))
            ->values()
            ->map(fn ($image, $index) => [
                'path' => ltrim((string) $image['path'], '/'),
                'alt' => $image['alt'] ?? null,
                'caption' => $image['caption'] ?? null,
                'sort' => $index,
            ])
            ->all();

        $data = [
            'type' => 'news',
            'title' => $this->title,
            'body' => $this->body,
            'excerpt' => $this->excerpt,
            'published' => $this->published,
            'published_at' => $this->published_at,
            'layout' => $this->layout,
            'images' => $images,
            'cover_image' => $images[0]['path'] ?? null,
            'user_id' => auth()->id(),
        ];

        if ($this->postId) {
            $post = Post::where('type', 'news')->findOrFail($this->postId);
            $oldPaths = collect($post->images ?? [])->pluck('path')->filter()->map(fn ($path) => ltrim((string) $path, '/'));
            $newPaths = collect($images)->pluck('path')->filter()->map(fn ($path) => ltrim((string) $path, '/'));

            $post->update($data);

            $oldPaths->diff($newPaths)->each(fn ($path) => $this->deleteImageViaMediaController($path));
        } else {
            Post::create($data);
        }

        $this->resetForm();
        $this->show = false;
        $this->dispatch('refresh-news')->to('admin.cms.web-content.news.news-list');
    }

    protected function uploadImageViaMediaController($file): string
    {
        $request = Request::create('/admin/media/upload', 'POST', [
            'visibility' => 'public',
            'folder' => 'uploads/news',
        ], [], ['file' => $file]);

        $response = (new MediaController())->store($request);

        if (method_exists($response, 'getData')) {
            return $response->getData(true)['path'] ?? '';
        }

        throw new \RuntimeException('Upload fehlgeschlagen.');
    }

    protected function deleteImageViaMediaController(string $path): void
    {
        try {
            $request = Request::create('/admin/media/delete', 'POST', [
                'path' => $path,
                'visibility' => 'public',
            ]);

            (new MediaController())->destroy($request);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function render()
    {
        return view('livewire.admin.cms.web-content.news.news-edit-create');
    }
}
