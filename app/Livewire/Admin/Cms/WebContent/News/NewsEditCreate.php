<?php

namespace App\Livewire\Admin\Cms\WebContent\News;

use App\Http\Controllers\MediaController;
use App\Models\NewsCategory;
use App\Models\PagebuilderProject;
use App\Models\Post;
use App\Support\NewsCacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

class NewsEditCreate extends Component
{
    use WithFileUploads;

    public bool $show = false;

    public ?int $postId = null;

    public ?Post $post = null;

    public bool $isDirty = false;

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
            $this->news_category_id = $this->post->news_category_id ?: '';
            $this->published = (bool) $this->post->published;
            $this->published_at = optional($this->post->published_at)->format('Y-m-d\TH:i');
            $this->images = $this->post->newsImages();
        } else {
            $this->news_category_id = '';
            $this->published_at = now('Europe/Berlin')->format('Y-m-d\TH:i');
        }

        $this->isDirty = false;
        $this->show = true;
    }

    public function resetForm(): void
    {
        $this->reset([
            'postId',
            'post',
            'isDirty',
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
        $this->resetValidation();
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    public function removeImage(int $index): void
    {
        if (! isset($this->images[$index])) {
            return;
        }

        unset($this->images[$index]);
        $this->images = array_values($this->images);
        $this->isDirty = true;
        $this->resetValidation();
    }

    public function removeImageFile(int $index): void
    {
        if (! isset($this->imageFiles[$index])) {
            return;
        }

        unset($this->imageFiles[$index]);
        $this->imageFiles = array_values($this->imageFiles);
        $this->isDirty = true;
        $this->resetValidation();
    }

    public function moveImageUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->images[$index], $this->images[$index - 1])) {
            return;
        }

        [$this->images[$index - 1], $this->images[$index]] = [$this->images[$index], $this->images[$index - 1]];
        $this->isDirty = true;
    }

    public function moveImageDown(int $index): void
    {
        if (! isset($this->images[$index], $this->images[$index + 1])) {
            return;
        }

        [$this->images[$index + 1], $this->images[$index]] = [$this->images[$index], $this->images[$index + 1]];
        $this->isDirty = true;
    }

    protected function rules(): array
    {
        $allowedImagePaths = $this->postId
            ? collect(
                Post::query()
                    ->where('type', 'news')
                    ->find($this->postId)
                    ?->newsImages() ?? []
            )->pluck('path')->all()
            : [];

        return [
            'title' => [
                'bail',
                'required',
                'string',
                'min:3',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $slug = Str::slug(str_replace(
                        ['&shy;', "\u{00AD}"],
                        '',
                        trim((string) $value)
                    ));

                    if ($slug === '') {
                        $fail('Der Titel muss mindestens einen Buchstaben oder eine Zahl enthalten.');

                        return;
                    }

                    $query = Post::query()->where('slug', $slug);

                    if ($this->postId !== null) {
                        $query->where('id', '!=', $this->postId);
                    }

                    if ($query->exists()) {
                        $fail('Eine Seite mit derselben URL existiert bereits. Bitte ändere den Titel.');
                    }
                },
            ],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'news_category_id' => [
                'nullable',
                'integer',
                Rule::exists('news_categories', 'id'),
            ],
            'published' => ['required', 'boolean'],
            'published_at' => [
                'bail',
                Rule::requiredIf(fn (): bool => $this->published),
                'nullable',
                'date_format:Y-m-d\TH:i',
            ],
            'images' => ['array'],
            'images.*' => ['array'],
            'images.*.path' => [
                'bail',
                'required',
                'string',
                'max:255',
                Rule::in($allowedImagePaths),
            ],
            'images.*.alt' => ['nullable', 'string', 'max:255'],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'images.*.sort' => ['nullable', 'integer', 'min:0'],
            'imageFiles' => ['array'],
            'imageFiles.*' => [
                'bail',
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'extensions:jpg,jpeg,png,gif,webp',
                'max:4096',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Bitte gib einen Titel ein.',
            'title.string' => 'Der Titel muss aus Text bestehen.',
            'title.min' => 'Der Titel muss mindestens :min Zeichen lang sein.',
            'title.max' => 'Der Titel darf höchstens :max Zeichen lang sein.',
            'excerpt.string' => 'Der Kurztext muss aus Text bestehen.',
            'excerpt.max' => 'Der Kurztext darf höchstens :max Zeichen lang sein.',
            'news_category_id.integer' => 'Bitte wähle eine gültige News-Kategorie aus.',
            'news_category_id.exists' => 'Die ausgewählte News-Kategorie ist nicht mehr vorhanden.',
            'published.required' => 'Bitte lege den Veröffentlichungsstatus fest.',
            'published.boolean' => 'Der Veröffentlichungsstatus ist ungültig.',
            'published_at.required' => 'Bitte gib ein Veröffentlichungsdatum an, wenn die News veröffentlicht werden soll.',
            'published_at.date_format' => 'Bitte gib ein gültiges Veröffentlichungsdatum mit Uhrzeit an.',
            'images.array' => 'Die gespeicherten Bilddaten sind ungültig.',
            'images.*.array' => 'Die Daten für Bild :position sind ungültig.',
            'images.*.path.required' => 'Für Bild :position fehlt der Speicherpfad.',
            'images.*.path.string' => 'Der Speicherpfad von Bild :position ist ungültig.',
            'images.*.path.max' => 'Der Speicherpfad von Bild :position ist zu lang.',
            'images.*.path.in' => 'Ein gespeichertes Bild wurde verändert. Bitte lade das Formular neu.',
            'images.*.alt.string' => 'Der Alternativtext von Bild :position muss aus Text bestehen.',
            'images.*.alt.max' => 'Der Alternativtext von Bild :position darf höchstens :max Zeichen lang sein.',
            'images.*.caption.string' => 'Die Bildunterschrift von Bild :position muss aus Text bestehen.',
            'images.*.caption.max' => 'Die Bildunterschrift von Bild :position darf höchstens :max Zeichen lang sein.',
            'images.*.sort.integer' => 'Die Position von Bild :position ist ungültig.',
            'images.*.sort.min' => 'Die Position von Bild :position darf nicht negativ sein.',
            'imageFiles.array' => 'Die ausgewählten Bilddateien sind ungültig.',
            'imageFiles.*.uploaded' => 'Bild :position konnte nicht hochgeladen werden.',
            'imageFiles.*.required' => 'Bitte wähle für Bild :position eine Datei aus.',
            'imageFiles.*.image' => 'Datei :position muss ein gültiges Bild sein.',
            'imageFiles.*.mimes' => 'Bild :position muss eine JPG-, PNG-, GIF- oder WebP-Datei sein.',
            'imageFiles.*.extensions' => 'Bild :position muss die Dateiendung JPG, JPEG, PNG, GIF oder WebP haben.',
            'imageFiles.*.max' => 'Bild :position darf höchstens 4 MB groß sein.',
        ];
    }

    public function updatedTitle(): void
    {
        $this->isDirty = true;
        $this->validateOnly('title');
    }

    public function updatedExcerpt(): void
    {
        $this->isDirty = true;
        $this->validateOnly('excerpt');
    }

    public function updatedNewsCategoryId(): void
    {
        $this->isDirty = true;

        if ($this->news_category_id === '' || $this->news_category_id === null) {
            $this->resetValidation('news_category_id');

            return;
        }

        $this->validateOnly('news_category_id');
    }

    public function updatedPublished(): void
    {
        $this->isDirty = true;
        $this->validateOnly('published');

        if ($this->published) {
            $this->validateOnly('published_at');
        } else {
            $this->resetValidation('published_at');
        }
    }

    public function updatedPublishedAt(): void
    {
        $this->isDirty = true;
        $this->validateOnly('published_at');
    }

    public function updatedImages(mixed $value, ?string $key = null): void
    {
        $this->isDirty = true;

        if ($key !== null) {
            $this->validateOnly("images.{$key}");
        }
    }

    public function updatedImageFiles(mixed $value, ?string $key = null): void
    {
        $this->isDirty = true;

        if ($key !== null) {
            $this->validateOnly("imageFiles.{$key}");
        }
    }

    public function save(): void
    {
        $this->isDirty = true;
        $this->normalizeFormValues();
        $this->news_category_id = $this->news_category_id ?: null;

        $this->validate();

        $existingImages = $this->images;
        $uploadedImages = [];

        try {
            foreach ($this->imageFiles as $file) {
                $uploadedImages[] = [
                    'path' => $this->uploadImageViaMediaController($file),
                    'alt' => $this->title,
                    'caption' => null,
                    'sort' => count($this->images) + count($uploadedImages),
                ];
            }
        } catch (Throwable $exception) {
            collect($uploadedImages)
                ->pluck('path')
                ->filter()
                ->each(fn (string $path) => $this->deleteImageViaMediaController($path));

            report($exception);
            $this->addError(
                'imageFiles',
                'Die ausgewählten Bilder konnten nicht hochgeladen werden. Bitte versuche es erneut.'
            );

            return;
        }

        $this->images = [...$this->images, ...$uploadedImages];

        $images = collect($this->images)
            ->filter(fn ($image) => is_array($image) && ! empty($image['path']))
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

        try {
            [$post, $removedImagePaths] = DB::transaction(function () use ($data, $images): array {
                $removedImagePaths = collect();

                if ($this->postId) {
                    $post = Post::where('type', 'news')->findOrFail($this->postId);
                    $oldPaths = collect($post->images ?? [])
                        ->pluck('path')
                        ->filter()
                        ->map(fn ($path) => ltrim((string) $path, '/'));
                    $newPaths = collect($images)
                        ->pluck('path')
                        ->filter()
                        ->map(fn ($path) => ltrim((string) $path, '/'));

                    $post->update($data);
                    $removedImagePaths = $oldPaths->diff($newPaths);
                } else {
                    $data['body'] = '';
                    $post = Post::create($data);
                }

                $this->ensurePagebuilderProject($post);
                NewsCacheVersion::bump();

                return [$post, $removedImagePaths];
            });
        } catch (Throwable $exception) {
            collect($uploadedImages)
                ->pluck('path')
                ->filter()
                ->each(fn (string $path) => $this->deleteImageViaMediaController($path));

            $this->images = $existingImages;
            report($exception);
            $this->addError(
                'save',
                'Die News konnte nicht gespeichert werden. Bitte versuche es erneut.'
            );

            return;
        }

        $removedImagePaths->each(
            fn (string $path) => $this->deleteImageViaMediaController($path)
        );

        $this->resetForm();
        $this->show = false;
        $this->dispatch('refresh-news')->to('admin.cms.web-content.news.news-list');
    }

    protected function normalizeFormValues(): void
    {
        $this->title = trim($this->title);

        $excerpt = trim((string) $this->excerpt);
        $this->excerpt = $excerpt !== '' ? $excerpt : null;

        $publishedAt = trim((string) $this->published_at);
        $this->published_at = $publishedAt !== '' ? $publishedAt : null;

        $this->images = collect($this->images)
            ->map(function (mixed $image): mixed {
                if (! is_array($image)) {
                    return $image;
                }

                foreach (['alt', 'caption'] as $field) {
                    if (! array_key_exists($field, $image) || ! is_string($image[$field])) {
                        continue;
                    }

                    $value = trim($image[$field]);
                    $image[$field] = $value !== '' ? $value : null;
                }

                return $image;
            })
            ->all();
    }

    public function openPagebuilder(): void
    {
        if (! $this->postId || $this->isDirty) {
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

        $response = (new MediaController)->store($request);

        if (method_exists($response, 'getData')) {
            $payload = $response->getData(true);
            $path = $payload['path'] ?? null;

            try {
                return $this->normalizeUploadedImagePath($path);
            } catch (Throwable $exception) {
                $deletablePath = is_string($path)
                    ? ltrim(str_replace('\\', '/', trim($path)), '/')
                    : '';

                if ($this->isDeletableNewsUploadPath($deletablePath)) {
                    $this->deleteImageViaMediaController($deletablePath);
                }

                throw $exception;
            }
        }

        throw new RuntimeException('Das Bild konnte nicht hochgeladen werden.');
    }

    protected function normalizeUploadedImagePath(mixed $path): string
    {
        if (! is_string($path) || trim($path) === '') {
            throw new RuntimeException('Die Upload-Antwort enthält keinen gültigen Bildpfad.');
        }

        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        if (! $this->isDeletableNewsUploadPath($path)) {
            throw new RuntimeException('Die Upload-Antwort enthält einen ungültigen Bildpfad.');
        }

        if (mb_strlen($path) > 255) {
            throw new RuntimeException('Der hochgeladene Bildpfad ist zu lang.');
        }

        return $path;
    }

    protected function isDeletableNewsUploadPath(string $path): bool
    {
        return preg_match(
            '/\Auploads\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp)\z/',
            $path
        ) === 1;
    }

    protected function deleteImageViaMediaController(string $path): void
    {
        try {
            $request = Request::create('/admin/media/delete', 'POST', [
                'path' => $path,
                'visibility' => 'public',
            ]);

            (new MediaController)->destroy($request);
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
