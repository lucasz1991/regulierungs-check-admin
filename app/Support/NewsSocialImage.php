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
    /**
     * Die drei anbietbaren Zuschnitte.
     *
     * `type` skaliert die Typografie: im flachen Link-Format ist deutlich
     * weniger Hoehe fuer den Textblock da. `lines` begrenzt die Titelzeilen,
     * `scrim` legt fest, wo die Abdunklung beginnt (Anteil der Hoehe).
     */
    public const FORMATS = [
        'story' => [
            'label' => 'Story', 'hint' => '9:16', 'width' => 1080, 'height' => 1920,
            'type' => 1.0, 'lines' => 4, 'scrim' => [0.22, 0.43, 0.78],
        ],
        'square' => [
            'label' => 'Post', 'hint' => '1:1', 'width' => 1080, 'height' => 1080,
            'type' => 0.94, 'lines' => 3, 'scrim' => [0.26, 0.30, 0.70],
        ],
        'landscape' => [
            'label' => 'Link', 'hint' => '1200×630', 'width' => 1200, 'height' => 630,
            'type' => 0.62, 'lines' => 2, 'scrim' => [0.30, 0.16, 0.58],
        ],
    ];

    public const DEFAULT_FORMAT = 'story';

    /**
     * Waehlbare Logo-Varianten fuer die Kopfzeile.
     *
     * Die Assets geben nur zwei Faerbungen her (Tintenanalyse): die Wortmarke
     * mit gelbem Haken und das komplette Lockup mit weisser Wortmarke samt
     * weissem Haken. Den gruen/blauen Haken gibt es nicht als Datei - er
     * entsteht beim Zeichnen, indem die gelben Pixel der Wortmarke auf das
     * Marken-Teal umgefaerbt werden.
     */
    public const LOGO_VARIANTS = [
        'yellow' => 'Gelber Haken',
        'teal' => 'Grün/blauer Haken',
        'white' => 'Alles weiß mit Icon',
    ];

    public const DEFAULT_LOGO_VARIANT = 'yellow';

    /**
     * Pro Format speicherbare Layout-Werte.
     *
     * Die Auswahllisten sind bewusst endlich: So kann das Livewire-Formular
     * keine Werte speichern, mit denen Text oder Logo ausserhalb der
     * Bildflaeche landen. Alle Angaben sind Basispixel und werden wie das
     * bisherige Layout mit dem jeweiligen Formatfaktor skaliert.
     */
    public const LAYOUT_CONTROLS = [
        'title_font_size' => [
            'group' => 'Schrift', 'label' => 'Titelgröße', 'default' => 78,
            'options' => [58 => 'Klein (58 px)', 68 => 'Kompakt (68 px)', 78 => 'Standard (78 px)', 88 => 'Groß (88 px)', 98 => 'Sehr groß (98 px)'],
        ],
        'excerpt_font_size' => [
            'group' => 'Schrift', 'label' => 'Kurztextgröße', 'default' => 34,
            'options' => [26 => 'Klein (26 px)', 30 => 'Kompakt (30 px)', 34 => 'Standard (34 px)', 38 => 'Groß (38 px)', 42 => 'Sehr groß (42 px)'],
        ],
        'title_line_height' => [
            'group' => 'Schrift', 'label' => 'Titel-Zeilenhöhe', 'default' => 134,
            'options' => [115 => 'Eng (115 %)', 125 => 'Kompakt (125 %)', 134 => 'Standard (134 %)', 145 => 'Luftig (145 %)'],
        ],
        'excerpt_line_height' => [
            'group' => 'Schrift', 'label' => 'Kurztext-Zeilenhöhe', 'default' => 50,
            'options' => [42 => 'Eng (42 px)', 46 => 'Kompakt (46 px)', 50 => 'Standard (50 px)', 58 => 'Luftig (58 px)'],
        ],
        'horizontal_padding' => [
            'group' => 'Abstände', 'label' => 'Seitenabstand', 'default' => 72,
            'options' => [48 => 'Klein (48 px)', 60 => 'Kompakt (60 px)', 72 => 'Standard (72 px)', 90 => 'Groß (90 px)', 108 => 'Sehr groß (108 px)'],
        ],
        'bottom_spacing' => [
            'group' => 'Abstände', 'label' => 'Unterer Freiraum', 'default' => 204,
            'options' => [96 => 'Klein (96 px)', 150 => 'Mittel (150 px)', 204 => 'Standard (204 px)', 240 => 'Groß (240 px)', 288 => 'Sehr groß (288 px)'],
        ],
        'section_spacing' => [
            'group' => 'Abstände', 'label' => 'Textblock-Abstand', 'default' => 46,
            'options' => [30 => 'Eng (30 px)', 38 => 'Kompakt (38 px)', 46 => 'Standard (46 px)', 56 => 'Luftig (56 px)', 68 => 'Sehr luftig (68 px)'],
        ],
        'logo_size' => [
            'group' => 'Marke', 'label' => 'Logogröße', 'default' => 92,
            'options' => [72 => 'Klein (72 px)', 82 => 'Kompakt (82 px)', 92 => 'Standard (92 px)', 104 => 'Groß (104 px)', 116 => 'Sehr groß (116 px)'],
        ],
        'badge_font_size' => [
            'group' => 'Marke', 'label' => 'Kategoriegröße', 'default' => 30,
            'options' => [24 => 'Klein (24 px)', 27 => 'Kompakt (27 px)', 30 => 'Standard (30 px)', 34 => 'Groß (34 px)', 38 => 'Sehr groß (38 px)'],
        ],
    ];

    /** Faktor fuer das Supersampling. */
    private const SCALE = 2;

    /** Layout-Einheit: Supersampling mal Typo-Skalierung des Formats. */
    private float $unit;

    private array $spec;

    /** Normalisierte Einstellungen des aktiven Formats. */
    private array $layout;

    private const NAVY = [10, 32, 53];

    private const TEAL = [20, 184, 166];

    private const MUTED = [214, 227, 237];

    private const FALLBACK_CATEGORY_COLOR = '#0c968e';

    public function __construct(
        private readonly Post $post,
        private readonly string $format = self::DEFAULT_FORMAT,
        private string $logoVariant = self::DEFAULT_LOGO_VARIANT
    ) {
        $actualFormat = self::isFormat($format) ? $format : self::DEFAULT_FORMAT;
        $this->spec = self::FORMATS[$actualFormat];
        $this->unit = self::SCALE * $this->spec['type'];
        $this->layout = self::normalizeLayoutSettings($this->post->social_image_settings)[$actualFormat];

        if (! self::isLogoVariant($this->logoVariant)) {
            $this->logoVariant = self::DEFAULT_LOGO_VARIANT;
        }
    }

    public static function isFormat(?string $format): bool
    {
        return $format !== null && array_key_exists($format, self::FORMATS);
    }

    public static function isLogoVariant(?string $variant): bool
    {
        return $variant !== null && array_key_exists($variant, self::LOGO_VARIANTS);
    }

    /** Vollstaendige Standardkonfiguration fuer alle Bildformate. */
    public static function defaultLayoutSettings(): array
    {
        $defaults = ['logo_variant' => self::DEFAULT_LOGO_VARIANT];

        foreach (self::LAYOUT_CONTROLS as $key => $control) {
            $defaults[$key] = $control['default'];
        }

        return array_fill_keys(array_keys(self::FORMATS), $defaults);
    }

    /**
     * Ergaenzt fehlende Werte und verwirft alles ausserhalb der erlaubten
     * Dropdown-Optionen. Damit bleiben auch alte oder manuell geaenderte JSONs
     * renderbar.
     */
    public static function normalizeLayoutSettings(mixed $settings): array
    {
        $settings = is_array($settings) ? $settings : [];
        $normalized = self::defaultLayoutSettings();

        foreach (array_keys(self::FORMATS) as $format) {
            $formatSettings = is_array($settings[$format] ?? null) ? $settings[$format] : [];

            if (self::isLogoVariant($formatSettings['logo_variant'] ?? null)) {
                $normalized[$format]['logo_variant'] = $formatSettings['logo_variant'];
            }

            foreach (self::LAYOUT_CONTROLS as $key => $control) {
                $candidate = filter_var($formatSettings[$key] ?? null, FILTER_VALIDATE_INT);
                $allowed = array_map('intval', array_keys($control['options']));

                if ($candidate !== false && in_array($candidate, $allowed, true)) {
                    $normalized[$format][$key] = $candidate;
                }
            }
        }

        return $normalized;
    }

    /** Layout-Einheit in Zielpixeln. */
    private function px(float $base): int
    {
        return (int) round($base * $this->unit);
    }

    /**
     * Dateiname fuer den Download. Enthaelt keinen Pfad und keine Endung ausser .png.
     */
    public function filename(): string
    {
        $slug = (string) ($this->post->slug ?: 'news-'.$this->post->id);
        $slug = preg_replace('/[^a-z0-9\-]+/i', '-', $slug) ?? 'news';
        $slug = trim((string) preg_replace('/-+/', '-', $slug), '-');

        // Nur abweichende Logo-Varianten landen im Namen; der Standard bleibt
        // unveraendert, damit bestehende Ablagen und Downloads stabil sind.
        $suffix = $this->logoVariant === self::DEFAULT_LOGO_VARIANT ? '' : '-'.$this->logoVariant;

        return 'social-'.($slug !== '' ? $slug : 'news-'.$this->post->id).$suffix.'.png';
    }

    public function logoVariant(): string
    {
        return $this->logoVariant;
    }

    /**
     * Rendert das Bild und gibt die PNG-Daten zurueck.
     */
    public function render(): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('Die PHP-Erweiterung GD ist nicht verfuegbar.');
        }

        /*
         * Zweifache Ueberabtastung kostet Speicher: allein die Leinwand belegt
         * bei 1080x1920 rund 33 MB, dazu Foto und verkleinerte Ausgabe. Mit dem
         * verbreiteten Limit von 128 MB reisst das ab - und da PHP eine
         * Speichererschoepfung nicht als Throwable meldet, bricht der Aufruf
         * ohne verwertbare Fehlermeldung ab. Deshalb hier gezielt anheben.
         */
        $this->ensureMemoryLimit(512);

        $s = self::SCALE;
        $w = $this->spec['width'] * $s;
        $h = $this->spec['height'] * $s;

        $canvas = imagecreatetruecolor($w, $h);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);

        try {
            $this->drawBackground($canvas, $w, $h);
            $this->drawScrims($canvas, $w, $h, $s);
            $this->drawLogo($canvas, $s);
            $this->drawBadge($canvas, $w, $s);

            $contentBottom = $h - $this->px($this->layout['bottom_spacing']);
            $excerptTop = $this->drawExcerpt($canvas, $w, $contentBottom);
            $ruleTop = $this->drawAccentRule($canvas, $s, $excerptTop);
            $this->drawTitle($canvas, $w, $s, $ruleTop);

            // downscale() gibt die grosse Leinwand selbst frei, sobald sie
            // nicht mehr gebraucht wird - siehe dort.
            return $this->downscale($canvas, $w, $h);
        } finally {
            if ($canvas instanceof GdImage) {
                imagedestroy($canvas);
            }
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
        [$topEnd, $fadeStart, $solidFrom] = $this->spec['scrim'];

        // Oben abdunkeln, damit Logo und Badge auf hellen Fotos lesbar bleiben.
        $this->verticalGradient($canvas, $w, 0, (int) round($h * $topEnd), self::NAVY, 0.62, 0.0);
        // Unten die Flaeche fuer den Textblock.
        $this->verticalGradient($canvas, $w, (int) round($h * $fadeStart), (int) round($h * $solidFrom), self::NAVY, 0.0, 0.96);
        imagefilledrectangle($canvas, 0, (int) round($h * $solidFrom), $w, $h, $this->color($canvas, self::NAVY));
    }

    /**
     * Oben links steht die gewaehlte Logo-Variante.
     *
     * `yellow` und `teal`: farbiges Schild-Icon plus weisse Wortmarke; beim
     * Teal wird der gelbe Haken der Wortmarke umgefaerbt. `white`: das
     * komplette Lockup aus logo-white.png, dessen Wortmarke samt Haken ganz
     * in Weiss gehalten ist.
     */
    private function drawLogo(GdImage $canvas, int $s): void
    {
        $left = $this->px($this->layout['horizontal_padding']);
        $centerY = $this->px(112);
        $gap = $this->px(24);
        $wordmarkHeight = $this->layout['logo_size'];
        $iconHeight = $wordmarkHeight * 132 / 92;

        if ($this->logoVariant === 'white') {
            // Ein einzelnes Asset; die Icon-Hoehe bestimmt die Gesamthoehe.
            $this->drawPngByHeight(
                $canvas,
                public_path('site-images/logo/logo-white.png'),
                $left,
                $centerY,
                $this->px($iconHeight)
            );

            return;
        }

        /*
         * Die Wortmarke fuellt ihre Bildhoehe vollstaendig aus und enthaelt
         * zwei Textzeilen, das Icon nutzt nur 91 % seiner Datei. Bei gleicher
         * Zielhoehe wirkt das Icon deshalb deutlich zu klein. Es bekommt daher
         * die groessere Zielhoehe, damit die sichtbare Marke die Wortmarke
         * ueberragt - so wie im Logo auf regulierungs-check.de.
         */
        $iconWidth = $this->drawPngByHeight(
            $canvas,
            public_path('site-images/logo/logo-icon.png'),
            $left,
            $centerY,
            $this->px($iconHeight)
        );

        $this->drawPngByHeight(
            $canvas,
            public_path('site-images/logo/logo-white-yelllow.png'),
            $left + ($iconWidth > 0 ? $iconWidth + $gap : 0),
            $centerY,
            $this->px($wordmarkHeight),
            $this->logoVariant === 'teal' ? self::TEAL : null
        );
    }

    /**
     * Zeichnet ein PNG auf eine Zielhoehe skaliert, vertikal um $centerY
     * zentriert, und liefert die belegte Breite zurueck.
     *
     * Mit $checkTint werden die gelben Pixel der Grafik auf die uebergebene
     * Farbe umgefaerbt. Das passiert auf der bereits verkleinerten Kopie -
     * so bleibt die Schleife bei wenigen zehntausend statt Millionen Pixeln.
     *
     * @param array{0:int,1:int,2:int}|null $checkTint
     */
    private function drawPngByHeight(
        GdImage $canvas,
        string $path,
        int $x,
        int $centerY,
        int $targetHeight,
        ?array $checkTint = null
    ): int {
        if (! is_file($path)) {
            return 0;
        }

        $source = @imagecreatefrompng($path);

        if ($source === false) {
            return 0;
        }

        imagealphablending($source, true);

        $targetWidth = (int) round($targetHeight * imagesx($source) / imagesy($source));
        $destX = $x;
        $destY = $centerY - (int) round($targetHeight / 2);

        if ($checkTint === null) {
            imagecopyresampled(
                $canvas, $source,
                $destX, $destY, 0, 0,
                $targetWidth, $targetHeight,
                imagesx($source), imagesy($source)
            );
            imagedestroy($source);

            return $targetWidth;
        }

        // Erst verkleinern (mit erhaltenem Alphakanal), dann umfaerben.
        $scaled = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagecopyresampled(
            $scaled, $source,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            imagesx($source), imagesy($source)
        );
        imagedestroy($source);

        $this->tintYellowPixels($scaled, $checkTint);

        imagecopy($canvas, $scaled, $destX, $destY, 0, 0, $targetWidth, $targetHeight);
        imagedestroy($scaled);

        return $targetWidth;
    }

    /**
     * Faerbt gelb-dominante Pixel auf die Zielfarbe um, der Alphakanal bleibt.
     *
     * Die Kanten der Wortmarke sind ueber Transparenz weichgezeichnet, nicht
     * ueber Mischfarben - ein harter Farbtausch erhaelt deshalb die Glaettung.
     *
     * @param array{0:int,1:int,2:int} $tint
     */
    private function tintYellowPixels(GdImage $image, array $tint): void
    {
        $w = imagesx($image);
        $h = imagesy($image);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($image, $x, $y);
                $alpha = ($c >> 24) & 0x7F;

                if ($alpha >= 127) {
                    continue;
                }

                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;

                // Gelbfamilie: deutlich mehr Rot/Gruen als Blau.
                if ($r > 110 && $r - $b > 45 && $g - $b > 25) {
                    imagesetpixel($image, $x, $y, imagecolorallocatealpha(
                        $image, $tint[0], $tint[1], $tint[2], $alpha
                    ));
                }
            }
        }
    }

    private function drawBadge(GdImage $canvas, int $w, int $s): void
    {
        $category = $this->post->newsCategory;
        $label = mb_strtoupper(trim((string) ($category->name ?? 'News')));

        if ($label === '') {
            return;
        }

        $font = $this->font('DejaVuSans-Bold.ttf');
        $baseSize = $this->layout['badge_font_size'];
        $size = $this->px($baseSize);
        $padX = $this->px($baseSize * 34 / 30);
        $height = $this->px($baseSize * 84 / 30);
        $width = (int) round($this->textWidth($font, $size, $label) + 2 * $padX);
        $x = $w - $this->px($this->layout['horizontal_padding']) - $width;
        $y = $this->px(66);

        $this->roundedRect(
            $canvas, $x, $y, $width, $height, $this->px($baseSize * 20 / 30),
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

    private function drawExcerpt(GdImage $canvas, int $w, int $contentBottom): int
    {
        $text = trim(strip_tags((string) $this->post->excerpt));

        if ($text === '') {
            return $contentBottom - $this->px(40);
        }

        $font = $this->font('DejaVuSans.ttf');
        $size = $this->px($this->layout['excerpt_font_size']);
        $lead = $this->px($this->layout['excerpt_line_height']);
        $horizontalPadding = $this->layout['horizontal_padding'];
        $lines = $this->wrapLines($font, $size, $text, $w - $this->px(2 * $horizontalPadding), 2);
        $baseline = $contentBottom - $this->px(70);
        $top = $baseline - (count($lines) - 1) * $lead;

        foreach ($lines as $i => $line) {
            imagettftext($canvas, $size, 0, $this->px($horizontalPadding), (int) round($top + $i * $lead), $this->color($canvas, self::MUTED), $font, $line);
        }

        return (int) round($top - $this->textHeight($font, $size, 'Hg'));
    }

    private function drawAccentRule(GdImage $canvas, int $s, int $excerptTop): int
    {
        $height = $this->px(8);
        $y = $excerptTop - $this->px($this->layout['section_spacing']);
        $this->roundedRect($canvas, $this->px($this->layout['horizontal_padding']), $y, $this->px(120), $height, (int) ($height / 2), $this->color($canvas, self::TEAL));

        return $y;
    }

    private function drawTitle(GdImage $canvas, int $w, int $s, int $ruleTop): void
    {
        $text = trim(strip_tags((string) $this->post->title));

        if ($text === '') {
            return;
        }

        $font = $this->font('DejaVuSans-Bold.ttf');
        $horizontalPadding = $this->layout['horizontal_padding'];
        $maxWidth = $w - $this->px(2 * $horizontalPadding);
        $maxSize = $this->layout['title_font_size'];

        [$size, $lines] = $this->fitLines($font, $text, $maxWidth, $this->spec['lines'], $this->px($maxSize), $this->px(max(24, $maxSize - 44)), $this->px(2));
        $lead = (int) round($size * $this->layout['title_line_height'] / 100);
        $baseline = $ruleTop - $this->px($this->layout['section_spacing'] + 6);
        $top = $baseline - (count($lines) - 1) * $lead;

        foreach ($lines as $i => $line) {
            imagettftext($canvas, $size, 0, $this->px($horizontalPadding), (int) round($top + $i * $lead), $this->color($canvas, [255, 255, 255]), $font, $line);
        }
    }

    /**
     * Verkleinert die ueberabgetastete Leinwand auf die Ausgabegroesse.
     *
     * Die grosse Leinwand wird sofort nach dem Verkleinern freigegeben - noch
     * vor dem PNG-Encoding. Das senkt die Speicherspitze um rund 33 MB und
     * entscheidet auf Servern mit 128 MB Limit darueber, ob der Aufruf
     * durchlaeuft. Der Aufrufer prueft deshalb auf null.
     */
    private function downscale(?GdImage &$canvas, int $w, int $h): string
    {
        $out = imagecreatetruecolor($this->spec['width'], $this->spec['height']);
        imagecopyresampled($out, $canvas, 0, 0, 0, 0, $this->spec['width'], $this->spec['height'], $w, $h);

        imagedestroy($canvas);
        $canvas = null;

        ob_start();
        imagepng($out, null, 6);
        $data = (string) ob_get_clean();
        imagedestroy($out);

        return $data;
    }

    // ---------------------------------------------------------------- Helfer

    /** Hebt das Speicherlimit auf mindestens $megabytes an, senkt es nie. */
    private function ensureMemoryLimit(int $megabytes): void
    {
        $current = trim((string) ini_get('memory_limit'));

        // -1 bedeutet unbegrenzt, da gibt es nichts zu tun.
        if ($current === '-1') {
            return;
        }

        $unit = strtolower(substr($current, -1));
        $value = (int) $current;
        $bytes = match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };

        if ($bytes > 0 && $bytes < $megabytes * 1024 * 1024) {
            @ini_set('memory_limit', $megabytes.'M');
        }
    }

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
