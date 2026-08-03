<?php

namespace App\Livewire\Admin\Cms\WebContent\News;

use App\Models\Post;
use App\Support\NewsSocialImage as SocialImageRenderer;
use Livewire\Component;

/**
 * Vorschau und Download des Social-Media-Bildes einer News.
 *
 * Gleiche Mechanik wie NewsEditCreate: Die Komponente wird einmal pro Seite
 * eingebunden und ueber einen Livewire-Dispatch geoeffnet. Dadurch haengt die
 * Sichtbarkeit an einer serverseitigen Eigenschaft und nicht an Alpine-Zustand
 * im DOM - genau das hatte zuvor dazu gefuehrt, dass der Overlay dauerhaft
 * offen stand und sich nicht schliessen liess.
 */
class NewsSocialImage extends Component
{
    public bool $show = false;

    public ?int $postId = null;

    public string $title = '';

    /** Aktiver Zuschnitt; siehe SocialImageRenderer::FORMATS. */
    public string $format = SocialImageRenderer::DEFAULT_FORMAT;

    /** Gewaehlte Logo-Variante; siehe SocialImageRenderer::LOGO_VARIANTS. */
    public string $logoVariant = SocialImageRenderer::DEFAULT_LOGO_VARIANT;

    protected $listeners = [
        'open-news-social-image' => 'openModal',
    ];

    public function openModal($postId = null): void
    {
        // Der Dispatch liefert je nach Aufrufart einen Skalar oder ein Array.
        if (is_array($postId)) {
            $postId = $postId['postId'] ?? null;
        }

        $post = $postId ? Post::where('type', 'news')->find((int) $postId) : null;

        if (! $post) {
            $this->closeModal();

            return;
        }

        $this->postId = $post->id;
        $this->title = (string) $post->title;
        $this->format = SocialImageRenderer::DEFAULT_FORMAT;
        $this->logoVariant = SocialImageRenderer::DEFAULT_LOGO_VARIANT;
        $this->show = true;
    }

    /** Format wechseln; das Bild wird daraufhin neu angefordert. */
    public function setFormat(string $format): void
    {
        if (SocialImageRenderer::isFormat($format)) {
            $this->format = $format;
        }
    }

    /**
     * Kommt ueber wire:model.live vom Selectfeld. Ungueltige Werte fallen auf
     * den Standard zurueck, damit ueber das DOM nichts anderes einschleusbar
     * ist; die Vorschau laedt durch den Re-Render automatisch neu.
     */
    public function updatedLogoVariant(string $value): void
    {
        if (! SocialImageRenderer::isLogoVariant($value)) {
            $this->logoVariant = SocialImageRenderer::DEFAULT_LOGO_VARIANT;
        }
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->postId = null;
        $this->title = '';
        $this->format = SocialImageRenderer::DEFAULT_FORMAT;
        $this->logoVariant = SocialImageRenderer::DEFAULT_LOGO_VARIANT;
    }

    public function render()
    {
        return view('livewire.admin.cms.web-content.news.news-social-image', [
            'formats' => SocialImageRenderer::FORMATS,
            'logoVariants' => SocialImageRenderer::LOGO_VARIANTS,
        ]);
    }
}
