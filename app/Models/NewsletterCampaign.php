<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    protected $guarded = ['id'];

    public const STATUSES = ['draft' => 'Entwurf', 'scheduled' => 'Geplant', 'sending' => 'Im Versand', 'sent' => 'Abgeschlossen', 'blocked' => 'Angehalten'];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function scheduledLocal(): ?string
    {
        return $this->scheduled_at ? CarbonImmutable::parse($this->scheduled_at, 'UTC')->setTimezone('Europe/Berlin')->format('d.m.Y · H:i') : null;
    }

    protected $casts = [
        'include_image' => 'boolean',
        'image_width' => 'integer',
        'image_height' => 'integer',
        'focus_x' => 'integer',
        'focus_y' => 'integer',
        'revision' => 'integer',
    ];

    public static function newsValues(Post $post): array
    {
        $image = $post->firstNewsImage();

        return [
            'post_id' => $post->getKey(),
            'article_title' => $post->title,
            'name' => $post->title,
            'subject' => $post->title,
            'body' => (string) $post->excerpt,
            'link' => static::publicUrl('/news/lesen/'.$post->getKey()),
            'include_image' => (bool) $image,
            'image_source' => $image['path'] ?? null,
            'image_alt' => trim((string) ($image['alt'] ?? '')) ?: $post->title,
            'image_url' => null, 'image_width' => null, 'image_height' => null,
            'focus_x' => 50, 'focus_y' => 50,
        ];
    }

    public static function publicUrl(string $path): string
    {
        $url = config('services.newsletter.base_url') ?: config('news-preview.base_url');

        return rtrim((string) $url, '/').'/'.ltrim($path, '/');
    }

    public function content(string $exampleGreeting = 'Hallo Alex,'): array
    {
        $testUrl = static::publicUrl('/newsletter/testansicht');

        return [
            'subject' => $this->subject,
            'header' => filled($this->greeting) ? $this->greeting : $exampleGreeting,
            'body' => $this->body,
            'link' => $this->link,
            'button_label' => filled($this->button_label) ? $this->button_label : 'Weiter',
            'newsletter' => [
                'kind' => $this->post_id ? 'news' : 'free',
                'preheader' => $this->preheader,
                'title' => $this->post_id ? $this->article_title : '',
                'image' => $this->include_image && $this->image_url ? [
                    'url' => $this->image_url, 'alt' => $this->image_alt,
                    'width' => $this->image_width, 'height' => $this->image_height,
                ] : null,
                'settings_url' => $testUrl, 'unsubscribe_url' => $testUrl,
            ],
        ];
    }
}
