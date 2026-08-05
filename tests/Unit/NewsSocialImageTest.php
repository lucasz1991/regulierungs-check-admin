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

        $this->assertSame(1080, $size[0]);
        $this->assertSame(1920, $size[1]);
        $this->assertSame('image/png', $size['mime']);
        $this->assertSame(1080 / 1920, $size[0] / $size[1], 'Hochformat 9:16 fuer Story und Reel.');
    }

    /** Jeder angebotene Zuschnitt muss auch genau so herauskommen. */
    public function test_every_format_renders_at_its_declared_size(): void
    {
        foreach (NewsSocialImage::FORMATS as $key => $spec) {
            $png = (new NewsSocialImage($this->newsPost(), $key))->render();
            $size = getimagesizefromstring($png);

            $this->assertSame($spec['width'], $size[0], "Breite im Format {$key}");
            $this->assertSame($spec['height'], $size[1], "Hoehe im Format {$key}");
            $this->assertSame('image/png', $size['mime']);
        }
    }

    /** Der fruehere weisse CTA-Button darf in keinem Zuschnitt mehr erscheinen. */
    public function test_article_button_is_not_drawn_at_the_bottom_of_any_format(): void
    {
        foreach (NewsSocialImage::FORMATS as $key => $spec) {
            $image = imagecreatefromstring((new NewsSocialImage($this->newsPost(), $key))->render());
            $x = (int) round(200 * $spec['type']);
            $y = (int) round($spec['height'] - 150 * $spec['type']);
            $color = imagecolorat($image, $x, $y);

            $this->assertSame([
                10,
                32,
                53,
            ], [
                ($color >> 16) & 0xFF,
                ($color >> 8) & 0xFF,
                $color & 0xFF,
            ], "Im Format {$key} muss der fruehere Buttonbereich frei bleiben.");

            imagedestroy($image);
        }
    }

    public function test_an_unknown_format_falls_back_to_the_default(): void
    {
        $png = (new NewsSocialImage($this->newsPost(), 'gibt-es-nicht'))->render();
        $size = getimagesizefromstring($png);

        $default = NewsSocialImage::FORMATS[NewsSocialImage::DEFAULT_FORMAT];
        $this->assertSame($default['width'], $size[0]);
        $this->assertSame($default['height'], $size[1]);
    }

    public function test_is_format_only_accepts_known_keys(): void
    {
        $this->assertTrue(NewsSocialImage::isFormat('story'));
        $this->assertTrue(NewsSocialImage::isFormat('square'));
        $this->assertTrue(NewsSocialImage::isFormat('landscape'));
        $this->assertFalse(NewsSocialImage::isFormat('gibt-es-nicht'));
        $this->assertFalse(NewsSocialImage::isFormat(null));
    }

    public function test_it_renders_even_without_category_or_excerpt(): void
    {
        $post = new Post(['type' => 'news', 'title' => 'Nur ein Titel']);
        $post->id = 4711;
        $post->setRelation('newsCategory', null);

        $png = (new NewsSocialImage($post))->render();

        $this->assertNotSame('', $png);
        $this->assertSame(1080, getimagesizefromstring($png)[0]);
    }

    /** Sehr lange Titel duerfen das Bild nicht sprengen. */
    public function test_a_very_long_title_still_produces_a_valid_image(): void
    {
        $post = $this->newsPost();
        $post->title = str_repeat('Sozialgerichtsbarkeitszustaendigkeitsstreit ', 12);

        // In jedem Zuschnitt, denn die flachen Formate haben am wenigsten Platz.
        foreach (array_keys(NewsSocialImage::FORMATS) as $key) {
            $size = getimagesizefromstring((new NewsSocialImage($post, $key))->render());

            $this->assertSame(NewsSocialImage::FORMATS[$key]['width'], $size[0]);
            $this->assertSame(NewsSocialImage::FORMATS[$key]['height'], $size[1]);
        }
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

    /** Jede Logo-Variante liefert ein gueltiges PNG in voller Groesse. */
    public function test_every_logo_variant_renders_a_valid_png(): void
    {
        foreach (array_keys(NewsSocialImage::LOGO_VARIANTS) as $variant) {
            $png = (new NewsSocialImage($this->newsPost(), 'story', $variant))->render();
            $size = getimagesizefromstring($png);

            $this->assertNotFalse($size, "Variante {$variant} liefert kein Bild.");
            $this->assertSame(1080, $size[0], "Breite bei Variante {$variant}");
            $this->assertSame(1920, $size[1], "Hoehe bei Variante {$variant}");
        }
    }

    public function test_an_unknown_logo_variant_falls_back_to_the_default(): void
    {
        $generator = new NewsSocialImage($this->newsPost(), 'story', 'gibt-es-nicht');

        $this->assertSame(NewsSocialImage::DEFAULT_LOGO_VARIANT, $generator->logoVariant());
        $this->assertNotFalse(getimagesizefromstring($generator->render()));
    }

    /** Nur abweichende Varianten aendern den Dateinamen. */
    public function test_filename_carries_the_logo_variant_only_when_it_deviates(): void
    {
        $post = $this->newsPost();

        $this->assertStringEndsWith('.png', (new NewsSocialImage($post, 'story', 'yellow'))->filename());
        $this->assertStringNotContainsString('-yellow', (new NewsSocialImage($post, 'story', 'yellow'))->filename());
        $this->assertStringEndsWith('-teal.png', (new NewsSocialImage($post, 'story', 'teal'))->filename());
        $this->assertStringEndsWith('-white.png', (new NewsSocialImage($post, 'story', 'white'))->filename());
    }

    /**
     * Die Teal-Variante muss den Haken tatsaechlich umfaerben: kein Gelb mehr
     * im Bild, dafuer Marken-Teal - und der Standard behaelt sein Gelb.
     */
    public function test_teal_variant_replaces_the_yellow_check(): void
    {
        $yellowInDefault = $this->countHue((new NewsSocialImage($this->newsPost(), 'story', 'yellow'))->render(), 'gelb');
        $tealPng = (new NewsSocialImage($this->newsPost(), 'story', 'teal'))->render();

        $this->assertGreaterThan(50, $yellowInDefault, 'Der Standard muss den gelben Haken enthalten.');
        $this->assertSame(0, $this->countHue($tealPng, 'gelb'), 'Im Teal-Bild darf kein Gelb uebrig bleiben.');
        $this->assertGreaterThan(50, $this->countHue($tealPng, 'teal'), 'Der Haken muss als Teal wieder auftauchen.');
    }

    /** Zaehlt Pixel einer Farbfamilie im Logo-Bereich (oberes Viertel). */
    private function countHue(string $png, string $family): int
    {
        $im = imagecreatefromstring($png);
        $w = imagesx($im);
        $h = (int) (imagesy($im) / 4);
        $count = 0;

        for ($y = 0; $y < $h; $y += 2) {
            for ($x = 0; $x < $w; $x += 2) {
                $c = imagecolorat($im, $x, $y);
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;

                $match = $family === 'gelb'
                    ? ($r > 180 && $g > 120 && $b < 90 && $r - $b > 90)
                    : ($g > 130 && $b > 110 && $r < 90 && $g - $r > 60);

                if ($match) {
                    $count++;
                }
            }
        }

        imagedestroy($im);

        return $count;
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
