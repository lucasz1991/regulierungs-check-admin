<?php

namespace App\Livewire\Admin\Cms\WebContent\News;

use App\Http\Controllers\MediaController;
use App\Models\NewsCategory;
use App\Models\PagebuilderProject;
use App\Models\Post;
use App\Support\NewsCacheVersion;
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
    public ?string $excerpt = null;
    public $news_category_id = null;
    public bool $published = false;
    public ?string $published_at = null;
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
            $this->excerpt = $this->post->excerpt;
            $this->news_category_id = $this->post->news_category_id;
            $this->published = (bool) $this->post->published;
            $this->published_at = optional($this->post->published_at)->format('Y-m-d\TH:i');
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
            'excerpt',
            'news_category_id',
            'published',
            'published_at',
            'images',
            'imageFiles',
        ]);

        $this->type = 'news';
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
        $this->news_category_id = $this->news_category_id ?: null;

        $this->validate([
            'title' => 'required|min:3',
            'excerpt' => 'nullable|string',
            'news_category_id' => 'nullable|exists:news_categories,id',
            'published' => 'boolean',
            'published_at' => 'nullable|date',
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
            'excerpt' => $this->excerpt,
            'news_category_id' => $this->news_category_id ?: null,
            'published' => $this->published,
            'published_at' => $this->published_at,
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
            $data['body'] = '';
            $post = Post::create($data);
        }

        $this->ensurePagebuilderProject($post);
        NewsCacheVersion::bump();

        $this->resetForm();
        $this->show = false;
        $this->dispatch('refresh-news')->to('admin.cms.web-content.news.news-list');
    }

    public function openPagebuilder(): void
    {
        if (!$this->postId) {
            return;
        }

        $post = Post::where('type', 'news')->findOrFail($this->postId);
        $project = $this->ensurePagebuilderProject($post);

        $this->redirectRoute('admin.cms.edit-project', ['projectId' => $project->id]);
    }

    protected function ensurePagebuilderProject(Post $post): PagebuilderProject
    {
        if ($post->pagebuilder_project_id && $post->pagebuilderProject) {
            return $post->pagebuilderProject;
        }

        $projectData = '{"assets":[],"styles":[],"pages":[{"frames":[{"component":{"type":"wrapper","attributes":{"id":"itix"},"components":[{"tagName":"section","classes":["text-gray-600","body-font","relative"],"attributes":{"id":"iyduu"},"components":[{"classes":["container","px-5","py-12","mx-auto"],"attributes":{"id":"i91ng"},"components":[{"tagName":"p","type":"text","classes":["leading-relaxed","text-base"],"attributes":{"id":"i0w6e"},"components":[{"type":"textnode","content":"Hier den News-Inhalt im Page Builder gestalten."}]}]}]}],"doctype":"<!DOCTYPE html>","head":{"type":"head","components":[{"tagName":"meta","void":true,"attributes":{"charset":"utf-8"}},{"tagName":"meta","void":true,"attributes":{"name":"viewport","content":"width=device-width,initial-scale=1"}},{"tagName":"link","type":"link","attributes":{"href":"https://admin850.regulierungs-check.de/adminresources/css/tailwind.min.css","rel":"stylesheet"}}]},"docEl":{"tagName":"html"}},"id":"8uKM3pEMmO8ZbWvN"}],"type":"main","id":"BGeRYNcKhJpNIMjw"}],"symbols":[],"dataSources":[],"custom":{"projectType":"web","id":""}}';

        $maxOrderId = PagebuilderProject::max('order_id') ?? 0;

        $project = PagebuilderProject::create([
            'name' => "{$post->title} News",
            'data' => $projectData,
            'status' => 3,
            'page' => [$post->slug],
            'position' => ['news'],
            'order_id' => $maxOrderId + 1,
            'type' => 'news',
        ]);

        $post->pagebuilder_project_id = $project->id;
        $post->saveQuietly();

        return $project;
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
        return view('livewire.admin.cms.web-content.news.news-edit-create', [
            'categories' => NewsCategory::ordered()->get(),
        ]);
    }
}
