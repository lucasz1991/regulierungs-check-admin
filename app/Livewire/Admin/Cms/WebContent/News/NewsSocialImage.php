<?php

namespace App\Livewire\Admin\Cms\WebContent\News;

use App\Models\Post;
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
        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->postId = null;
        $this->title = '';
    }

    public function render()
    {
        return view('livewire.admin.cms.web-content.news.news-social-image');
    }
}
