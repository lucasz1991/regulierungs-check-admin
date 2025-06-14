<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\MediaController;

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
    ];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('cover_image') && $post->getOriginal('cover_image')) {
                self::deleteImageViaMediaController($post->getOriginal('cover_image'));
            }
            $post->slug = Str::slug($post->title);
        });

        static::deleting(function ($post) {
            if ($post->cover_image) {
                self::deleteImageViaMediaController($post->cover_image);
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

    //  Vorschau aus dem Body generieren
    public function getExcerptPreviewAttribute()
    {
        return $this->excerpt ?? Str::limit(strip_tags($this->body), 150);
    }
}
