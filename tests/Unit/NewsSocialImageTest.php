<?php

namespace Tests\Unit;

use App\Models\NewsCategory;
use App\Models\Post;
use App\Support\NewsSocialImage;
use Tests\TestCase;

class NewsSocialImageTest extends TestCase
{
    /** Ohne erreichbares Beitragsbild greift der Markenverlauf. */
    public function test_it_renders_a_portrait_png_without_a_photo(): void
    {
        $png = (new NewsSocialImage($this->newsPost()))->render();
        $size = getimagesizefromstring($png);

        $this->assertSame(NewsSocialImage::WIDTH, $size[0]);
        $this->assertSame(NewsSocialImage::HEIGHT, $size[1]);
        $this->assertSame('image/png', $size['mime']);
        $this->assertSame(1080 / 1920, $size[0] / $size[1], 'Hochformat 9:16 fuer Story und Reel.');
    }

    public function test_it_renders_even_without_category_or_excerpt(): void
    {
        $post = new Post(['type' => 'news', 'title' => 'Nur ein Titel']);
        $post->id = 4711;
        $post->setRelation('newsCategory', null);

        $png = (new NewsSocialImage($post))->render();

        $this->assertNotSame('', $png);
        $this->assertSame(NewsSocialImage::WIDTH, getimagesizefromstring($png)[0]);
    }

    /** Sehr lange Titel duerfen das Bild nicht sprengen. */
    public function test_a_very_long_title_still_produces_a_valid_image(): void
    {
        $post = $this->newsPost();
        $post->title = str_repeat('Sozialgerichtsbarkeitszustaendigkeitsstreit ', 12);

        $png = (new NewsSocialImage($post))->render();
        $size = getimagesizefromstring($png);

        $this->assertSame(NewsSocialImage::WIDTH, $size[0]);
        $this->assertSame(NewsSocialImage::HEIGHT, $size[1]);
    }

    public function test_filename_is_derived_from_the_slug_and_is_safe(): void
    {
        $post = $this->newsPost();
        $post->slug = 'bgh-stärkt/rechte..von';

        $name = (new NewsSocialImage($post))->filename();

        $this->assertStringStartsWith('social-', $name);
        $this->assertStringEndsWith('.png', $name);
        $this->assertMatchesRegularExpression('/^social-[a-z0-9\-]+\.png$/i', $name);
        $this->assertStringNotContainsString('/', $name);
    }

    public function test_filename_falls_back_to_the_id_without_a_slug(): void
    {
        $post = $this->newsPost();
        $post->slug = null;

        $this->assertSame('social-news-321.png', (new NewsSocialImage($post))->filename());
    }

    private function newsPost(): Post
    {
        $post = new Post([
            'type' => 'news',
            'title' => 'BGH stärkt Rechte von Gebäudeversicherten',
            'excerpt' => 'Mehr Rechtssicherheit bei größeren Gebäudeschäden.',
        ]);
        $post->id = 321;
        $post->slug = 'bgh-starkt-rechte';

        $category = new NewsCategory(['name' => 'Urteil']);
        $category->color = '#7C3AED';
        $post->setRelation('newsCategory', $category);

        return $post;
    }
}
