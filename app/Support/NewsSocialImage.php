<?php

namespace App\Support;

use App\Models\Post;
use GdImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Erzeugt das Social-Media-Bild einer News im Hochformat 1080x1920.
 *
 * Das Bild wird ausschliesslich im Arbeitsspeicher aufgebaut und als
 * PNG-String zurueckgegeben. Es landet nie im Dateisystem - der Controller
 * streamt es direkt an den Browser. Damit gibt es auch nichts aufzuraeumen.
 *
 * Gezeichnet wird intern mit doppelter Kantenlaenge und am Ende einmal
 * heruntergerechnet. Das glaettet Rundungen und Text, weil GD selbst keine
 * Kantenglaettung fuer Formen kennt.
 */
class NewsSocialImage
{
    public const WIDTH = 1080;

    public const HEIGHT = 1920;

    /** Faktor fuer das Supersampling. */
    private const SCALE = 2;

    private const NAVY = [10, 32, 53];

    private const TEAL = [20, 184, 166];

    private const MUTED = [214, 227, 237];

    private const DARK_TEXT = [15, 44, 64];

    private const FALLBACK_CATEGORY_COLOR = '#0c968e';

    public function __construct(private readonly Post $post)
    {
    }

    /**
     * Dateiname fuer den Download. Enthaelt keinen Pfad und keine Endung ausser .png.
     */
    public function filename(): string
    {
        $slug = (string) ($this->post->slug ?: 'news-'.$this->post->id);
        $slug = preg_replace('/[^a-z0-9\-]+/i', '-', $slug) ?? 'news';
        $slug = trim((string) preg_replace('/-+/', '-', $slug), '-');

        return 'social-'.($slug !== '' ? $slug : 'news-'.$this->post->id).'.png';
    }

    /**
     * Rendert das Bild und gibt die PNG-Daten zurueck.
     */
    public function render(): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('Die PHP-Erweiterung GD ist nicht verfuegbar.');
        }

        $s = self::SCALE;
        $w = self::WIDTH * $s;
        $h = self::HEIGHT * $s;

        $canvas = imagecreatetruecolor($w, $h);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);

        try {
            $this->drawBackground($canvas, $w, $h);
            $this->drawScrims($canvas, $w, $h, $s);
            $this->drawLogo($canvas, $s);
            $this->drawBadge($canvas, $w, $s);

            $buttonTop = $this->drawButton($canvas, $h, $s);
            $excerptTop = $this->drawExcerpt($canvas, $w, $s, $buttonTop);
            $ruleTop = $this->drawAccentRule($canvas, $s, $excerptTop);
            $this->drawTitle($canvas, $w, $s, $ruleTop);

            return $this->downscale($canvas, $w, $h);
        } finally {
            imagedestroy($canvas);
        }
    }

    // ------------------------------------------------------------- Bausteine

    private function drawBackground(GdImage $canvas, int $w, int $h): void
    {
        $bytes = $this->fetchPhoto();

        if ($bytes !== null) {
            $photo = @imagecreatefromstring($bytes);

            if ($photo !== false) {
                $this->drawCover($canvas, $photo, $w, $h);
                imagedestroy($photo);

                return;
            }
        }

        // Ohne Bild ein ruhiger Markenverlauf statt einer leeren Flaeche.
        $this->verticalGradientOpaque($canvas, $w, 0, $h, [11, 60, 88], self::NAVY);
    }

    /**
     * Holt das Beitragsbild. Die Bilder liegen auf der Base-Installation,
     * deshalb ein HTTP-Aufruf mit kurzem Timeout. Faellt er aus, bleibt der
     * Markenverlauf als Hintergrund.
     */
    private function fetchPhoto(): ?string
    {
        try {
            // newsImages() liest die Base-URL aus den Settings; auch das darf
            // das Rendern nicht abbrechen.
            $images = $this->post->newsImages();
            $url = $images[0]['url'] ?? null;

            if (! is_string($url) || $url === '') {
                return null;
            }

            $response = Http::timeout(8)->retry(1, 200)->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('Social-Bild: Beitragsbild nicht erreichbar.', [
                'post_id' => $this->post->id,
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Social-Bild: Beitragsbild konnte nicht geladen werden.', [
                'post_id' => $this->post->id,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function drawScrims(GdImage $canvas, int $w, int $h, int $s): void
    {
        // Oben abdunkeln, damit Logo und Badge auf hellen Fotos lesbar bleiben.
        $this->verticalGradient($canvas, $w, 0, 420 * $s, self::NAVY, 0.62, 0.0);
        // Unten die Flaeche fuer den Textblock.
        $this->verticalGradient($canvas, $w, 820 * $s, 1500 * $s, self::NAVY, 0.0, 0.96);
        imagefilledrectangle($canvas, 0, 1500 * $s, $w, $h, $this->color($canvas, self::NAVY));
    }

    private function drawLogo(GdImage $canvas, int $s): void
    {
        $path = public_path('site-images/logo/logo-white-yelllow.png');

        if (! is_file($path)) {
            return;
        }

        $logo = @imagecreatefrompng($path);

        if ($logo === false) {
            return;
        }

        imagealphablending($logo, true);
        $targetWidth = 340 * $s;
        $targetHeight = (int) round($targetWidth * imagesy($logo) / imagesx($logo));
        imagecopyresampled(
            $canvas, $logo,
            72 * $s, 66 * $s, 0, 0,
            $targetWidth, $targetHeight,
            imagesx($logo), imagesy($logo)
        );
        imagedestroy($logo);
    }

    private function drawBadge(GdImage $canvas, int $w, int $s): void
    {
        $category = $this->post->newsCategory;
        $label = mb_strtoupper(trim((string) ($category->name ?? 'News')));

        if ($label === '') {
            return;
        }

        $font = $this->font('DejaVuSans-Bold.ttf');
        $size = 30 * $s;
        $padX = 34 * $s;
        $height = 84 * $s;
        $width = (int) round($this->textWidth($font, $size, $label) + 2 * $padX);
        $x = $w - 72 * $s - $width;
        $y = 66 * $s;

        $this->roundedRect(
            $canvas, $x, $y, $width, $height, 20 * $s,
            $this->color($canvas, $this->hexToRgb($category->color ?? self::FALLBACK_CATEGORY_COLOR))
        );

        $capHeight = $this->textHeight($font, $size, 'H');
        imagettftext(
            $canvas, $size, 0,
            (int) round($x + $padX),
            (int) round($y + ($height + $capHeight) / 2),
            $this->color($canvas, [255, 255, 255]), $font, $label
        );
    }

    /**
     * Der Button ist reine Grafik - im Bild kann es keinen echten Link geben.
     * Er signalisiert nur, dass der Beitrag hinter dem Post-Link steht.
     *
     * @return int obere Kante, damit der Text darueber ausgerichtet werden kann
     */
    private function drawButton(GdImage $canvas, int $h, int $s): int
    {
        $font = $this->font('DejaVuSans-Bold.ttf');
        $iconFont = $this->font('fa-solid-900.ttf');
        $label = 'ZUM ARTIKEL';
        // Font-Awesome-Glyph fuer fa-link, als Escape statt als Rohzeichen,
        // damit die Datei in jedem Editor lesbar bleibt.
        $glyph = (string) json_decode('"\uf0c1"');

        $size = 34 * $s;
        $iconSize = 36 * $s;
        $padX = 48 * $s;
        $gap = 24 * $s;
        $height = 108 * $s;

        $iconWidth = $this->textWidth($iconFont, $iconSize, $glyph);
        $width = (int) round($iconWidth + $gap + $this->textWidth($font, $size, $label) + 2 * $padX);
        $x = 72 * $s;
        $y = $h - 96 * $s - $height;

        $this->roundedRect($canvas, $x, $y, $width, $height, (int) ($height / 2), $this->color($canvas, [255, 255, 255]));

        $capHeight = $this->textHeight($font, $size, 'H');
        $baseline = (int) round($y + ($height + $capHeight) / 2);

        imagettftext($canvas, $iconSize, 0, (int) round($x + $padX), $baseline + (int) round(3 * $s), $this->color($canvas, self::TEAL), $iconFont, $glyph);
        imagettftext($canvas, $size, 0, (int) round($x + $padX + $iconWidth + $gap), $baseline, $this->color($canvas, self::DARK_TEXT), $font, $label);

        return $y;
    }

    private function drawExcerpt(GdImage $canvas, int $w, int $s, int $buttonTop): int
    {
        $text = trim(strip_tags((string) $this->post->excerpt));

        if ($text === '') {
            return $buttonTop - 40 * $s;
        }

        $font = $this->font('DejaVuSans.ttf');
        $size = 34 * $s;
        $lead = 50 * $s;
        $lines = $this->wrapLines($font, $size, $text, $w - 144 * $s, 2);
        $baseline = $buttonTop - 70 * $s;
        $top = $baseline - (count($lines) - 1) * $lead;

        foreach ($lines as $i => $line) {
            imagettftext($canvas, $size, 0, 72 * $s, (int) round($top + $i * $lead), $this->color($canvas, self::MUTED), $font, $line);
        }

        return (int) round($top - $this->textHeight($font, $size, 'Hg'));
    }

    private function drawAccentRule(GdImage $canvas, int $s, int $excerptTop): int
    {
        $height = 8 * $s;
        $y = $excerptTop - 46 * $s;
        $this->roundedRect($canvas, 72 * $s, $y, 120 * $s, $height, (int) ($height / 2), $this->color($canvas, self::TEAL));

        return $y;
    }

    private function drawTitle(GdImage $canvas, int $w, int $s, int $ruleTop): void
    {
        $text = trim(strip_tags((string) $this->post->title));

        if ($text === '') {
            return;
        }

        $font = $this->font('DejaVuSans-Bold.ttf');
        $maxWidth = $w - 144 * $s;

        [$size, $lines] = $this->fitLines($font, $text, $maxWidth, 4, 78 * $s, 40 * $s, 2 * $s);
        $lead = (int) round($size * 1.34);
        $baseline = $ruleTop - 52 * $s;
        $top = $baseline - (count($lines) - 1) * $lead;

        foreach ($lines as $i => $line) {
            imagettftext($canvas, $size, 0, 72 * $s, (int) round($top + $i * $lead), $this->color($canvas, [255, 255, 255]), $font, $line);
        }
    }

    private function downscale(GdImage $canvas, int $w, int $h): string
    {
        $out = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopyresampled($out, $canvas, 0, 0, 0, 0, self::WIDTH, self::HEIGHT, $w, $h);

        ob_start();
        imagepng($out, null, 6);
        $data = (string) ob_get_clean();
        imagedestroy($out);

        return $data;
    }

    // ---------------------------------------------------------------- Helfer

    private function font(string $file): string
    {
        $path = resource_path('fonts/'.$file);

        if (! is_file($path)) {
            throw new RuntimeException("Schriftdatei fehlt: {$file}");
        }

        return $path;
    }

    /** @param array{0:int,1:int,2:int} $rgb */
    private function color(GdImage $im, array $rgb): int
    {
        return imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    }

    /** @return array{0:int,1:int,2:int} */
    private function hexToRgb(?string $hex): array
    {
        $hex = ltrim((string) ($hex ?: self::FALLBACK_CATEGORY_COLOR), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            $hex = ltrim(self::FALLBACK_CATEGORY_COLOR, '#');
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private function textWidth(string $font, float $size, string $text): float
    {
        if ($text === '') {
            return 0.0;
        }

        $box = imagettfbbox($size, 0, $font, $text);

        return $box[2] - $box[0];
    }

    private function textHeight(string $font, float $size, string $text): float
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return abs($box[7] - $box[1]);
    }

    /**
     * Umbruch ohne Zeilenbegrenzung.
     *
     * @return list<string>
     */
    private function wrapAll(string $font, float $size, string $text, float $maxWidth): array
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $probe = $current === '' ? $word : $current.' '.$word;

            if ($current !== '' && $this->textWidth($font, $size, $probe) > $maxWidth) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $probe;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    /**
     * Kuerzt auf maxLines und haengt eine Ellipse an die letzte Zeile.
     *
     * @param list<string> $lines
     * @return list<string>
     */
    private function truncateLines(array $lines, string $font, float $size, float $maxWidth, int $maxLines): array
    {
        if (count($lines) <= $maxLines) {
            return $lines;
        }

        $lines = array_slice($lines, 0, $maxLines);
        $last = rtrim($lines[$maxLines - 1]);

        while ($last !== '' && $this->textWidth($font, $size, $last.'…') > $maxWidth) {
            $last = rtrim(mb_substr($last, 0, mb_strlen($last) - 1));
        }

        $lines[$maxLines - 1] = $last.'…';

        return $lines;
    }

    /**
     * Umbruch mit Zeilenbegrenzung.
     *
     * @return list<string>
     */
    private function wrapLines(string $font, float $size, string $text, float $maxWidth, int $maxLines): array
    {
        return $this->truncateLines(
            $this->wrapAll($font, $size, $text, $maxWidth),
            $font, $size, $maxWidth, $maxLines
        );
    }

    /**
     * Ausgewogener Umbruch: die schmalste Breite suchen, die noch dieselbe
     * Zeilenzahl ergibt. Verhindert Zeilen mit nur einem kurzen Wort.
     *
     * @return list<string>
     */
    private function wrapBalanced(string $font, float $size, string $text, float $maxWidth, int $target): array
    {
        $lines = $this->wrapAll($font, $size, $text, $maxWidth);

        if ($target < 2) {
            return $lines;
        }

        $low = 0.0;
        $high = $maxWidth;
        $best = $lines;

        for ($i = 0; $i < 24; $i++) {
            $mid = ($low + $high) / 2;
            $probe = $this->wrapAll($font, $size, $text, $mid);

            if (count($probe) <= $target && ! $this->overflows($font, $size, $probe, $maxWidth)) {
                $best = $probe;
                $high = $mid;
            } else {
                $low = $mid;
            }
        }

        return $best;
    }

    /**
     * Groesste Schriftgroesse, bei der der Text vollstaendig in hoechstens
     * maxLines Zeilen passt. Noetig, weil lange deutsche Komposita sonst aus
     * dem Bild laufen.
     *
     * Wichtig: geprueft wird auch, dass nichts abgeschnitten wird - sonst
     * gilt ein gekuerzter Text faelschlich als passend.
     *
     * @return array{0:float,1:list<string>}
     */
    private function fitLines(string $font, string $text, float $maxWidth, int $maxLines, float $from, float $to, float $step): array
    {
        for ($size = $from; $size >= $to; $size -= $step) {
            $lines = $this->wrapAll($font, $size, $text, $maxWidth);

            if (count($lines) <= $maxLines && ! $this->overflows($font, $size, $lines, $maxWidth)) {
                return [$size, $this->wrapBalanced($font, $size, $text, $maxWidth, count($lines))];
            }
        }

        // Passt in keiner Groesse: kleinste Groesse nehmen und kuerzen.
        $lines = $this->wrapAll($font, $to, $text, $maxWidth);

        return [$to, $this->truncateLines($lines, $font, $to, $maxWidth, $maxLines)];
    }

    /** @param list<string> $lines */
    private function overflows(string $font, float $size, array $lines, float $maxWidth): bool
    {
        foreach ($lines as $line) {
            if ($this->textWidth($font, $size, $line) > $maxWidth) {
                return true;
            }
        }

        return false;
    }

    private function roundedRect(GdImage $im, int $x, int $y, int $w, int $h, int $r, int $color): void
    {
        $r = min($r, (int) floor($w / 2), (int) floor($h / 2));

        imagefilledrectangle($im, $x + $r, $y, $x + $w - $r - 1, $y + $h - 1, $color);
        imagefilledrectangle($im, $x, $y + $r, $x + $w - 1, $y + $h - $r - 1, $color);

        $d = $r * 2;
        imagefilledellipse($im, $x + $r, $y + $r, $d, $d, $color);
        imagefilledellipse($im, $x + $w - $r - 1, $y + $r, $d, $d, $color);
        imagefilledellipse($im, $x + $r, $y + $h - $r - 1, $d, $d, $color);
        imagefilledellipse($im, $x + $w - $r - 1, $y + $h - $r - 1, $d, $d, $color);
    }

    /** @param array{0:int,1:int,2:int} $rgb */
    private function verticalGradient(GdImage $im, int $w, int $y0, int $y1, array $rgb, float $a0, float $a1): void
    {
        $span = max(1, $y1 - $y0);

        for ($i = 0; $i <= $span; $i++) {
            $t = $i / $span;
            $alpha = (int) round((1 - ($a0 + ($a1 - $a0) * $t)) * 127);
            $alpha = max(0, min(127, $alpha));

            if ($alpha >= 127) {
                continue;
            }

            $color = imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], $alpha);
            imagefilledrectangle($im, 0, $y0 + $i, $w - 1, $y0 + $i, $color);
            imagecolordeallocate($im, $color);
        }
    }

    /**
     * @param array{0:int,1:int,2:int} $from
     * @param array{0:int,1:int,2:int} $to
     */
    private function verticalGradientOpaque(GdImage $im, int $w, int $y0, int $y1, array $from, array $to): void
    {
        $span = max(1, $y1 - $y0);

        for ($i = 0; $i <= $span; $i++) {
            $t = $i / $span;
            $color = imagecolorallocate(
                $im,
                (int) round($from[0] + ($to[0] - $from[0]) * $t),
                (int) round($from[1] + ($to[1] - $from[1]) * $t),
                (int) round($from[2] + ($to[2] - $from[2]) * $t)
            );
            imagefilledrectangle($im, 0, $y0 + $i, $w - 1, $y0 + $i, $color);
            imagecolordeallocate($im, $color);
        }
    }

    private function drawCover(GdImage $dst, GdImage $src, int $dw, int $dh): void
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scale = max($dw / $sw, $dh / $sh);
        $cropW = (int) ceil($dw / $scale);
        $cropH = (int) ceil($dh / $scale);
        $sx = (int) max(0, ($sw - $cropW) / 2);
        $sy = (int) max(0, ($sh - $cropH) / 2);

        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $dw, $dh, $cropW, $cropH);
    }
}
