<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\MediaController;

class Post extends Model
{
    protected $fillable = [
        'type',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image',
        'user_id',
        'category_id',
        'published',
        'published_at',
        'layout',
        'images',
    ];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
        'images' => 'array',
    ];

    public const NEWS_LAYOUTS = [
        'image_top',
        'image_bottom',
        'image_left',
        'image_right',
    ];

    protected static function booted()
    {
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });

        static::updating(function ($post) {
            if ($post->type !== 'news' && $post->isDirty('cover_image') && $post->getOriginal('cover_image')) {
                self::deleteImageViaMediaController($post->getOriginal('cover_image'));
            }
            $cleanTitle = str_replace(['&shy;', "\u{00AD}"], '', $post->title);
            $post->slug = Str::slug($cleanTitle);
        });

        static::deleting(function ($post) {
            if ($post->cover_image) {
                self::deleteImageViaMediaController($post->cover_image);
            }

            foreach ($post->newsImages() as $image) {
                if (($image['path'] ?? null) && $image['path'] !== $post->cover_image) {
                    self::deleteImageViaMediaController($image['path']);
                }
            }
        });
    }

    protected static function deleteImageViaMediaController($path)
    {
        if (!$path) {
            return;
        }

        try {
            // Temporären Request mit POST-Daten (obwohl eigentlich DELETE, das ist okay für internen Aufruf)
            $request = Request::create('/admin/media/delete', 'POST', ['path' => $path]);

            // MediaController aufrufen
            $controller = new MediaController();
            $response = $controller->destroy($request);

            if (method_exists($response, 'getData')) {
                $result = $response->getData(true);
                if (!($result['success'] ?? false)) {
                    \Log::warning('Löschen nicht erfolgreich: ' . ($result['message'] ?? 'unbekannt'));
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Bild konnte nicht über MediaController gelöscht werden: ' . $e->getMessage());
        }
    }

    //  Autor (z. B. Admin)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }


    //  Kommentare
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    //  Nur veröffentlichte Beiträge
    public function scopePublished($query)
    {
        return $query->where('published', true)->whereNotNull('published_at');
    }

    //  URL zum Bild (oder Fallback)
    public function getCoverImageUrlAttribute()
    {
        $apiUrl = Setting::where('key', 'base_api_url')->value('value');
        return $this->cover_image
            ? $apiUrl . '/storage/' . $this->cover_image : null;
    }

    public function newsImages(): array
    {
        $apiUrl = rtrim((string) Setting::where('key', 'base_api_url')->value('value'), '/');

        $images = collect($this->images ?? [])
            ->filter(fn ($image) => is_array($image) && !empty($image['path']))
            ->sortBy(fn ($image) => (int) ($image['sort'] ?? 0))
            ->values()
            ->map(function ($image) use ($apiUrl) {
                $path = ltrim((string) $image['path'], '/');

                return [
                    'path' => $path,
                    'url' => $apiUrl ? $apiUrl . '/storage/' . $path : null,
                    'alt' => $image['alt'] ?? $this->title,
                    'caption' => $image['caption'] ?? null,
                    'sort' => (int) ($image['sort'] ?? 0),
                ];
            })
            ->all();

        if ($images === [] && $this->cover_image) {
            $path = ltrim($this->cover_image, '/');

            return [[
                'path' => $path,
                'url' => $apiUrl ? $apiUrl . '/storage/' . $path : null,
                'alt' => $this->title,
                'caption' => null,
                'sort' => 0,
            ]];
        }

        return $images;
    }

    public function firstNewsImage(): ?array
    {
        return $this->newsImages()[0] ?? null;
    }

    //  Vorschau aus dem Body generieren
    public function getExcerptPreviewAttribute()
    {
        return $this->excerpt ?? Str::limit(strip_tags($this->body), 150);
    }
}
