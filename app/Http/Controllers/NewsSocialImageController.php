<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\NewsSocialImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Throwable;

/**
 * Liefert das Social-Media-Bild einer News.
 *
 * Das Bild liegt unter storage/app/public/news-social/{postId}/ und wird nur
 * dann neu erzeugt, wenn sich die News geaendert hat. Der Fingerabdruck im
 * Dateinamen entscheidet darueber; beim Neuerzeugen ersetzt die neue Datei den
 * alten Stand desselben Formats.
 */
class NewsSocialImageController extends Controller
{
    /**
     * Hochzaehlen, wenn sich das Bildlayout aendert - dann werden alle
     * abgelegten Staende beim naechsten Aufruf neu erzeugt.
     */
    private const LAYOUT_VERSION = 6;

    /** Vorschau im Modal. */
    public function preview(Request $request, Post $post): Response
    {
        return $this->stream($post, $this->format($request), $this->logoVariant($request), false);
    }

    /** Download als Anhang. */
    public function download(Request $request, Post $post): Response
    {
        return $this->stream($post, $this->format($request), $this->logoVariant($request), true);
    }

    /** Unbekannte Logo-Varianten fallen auf den Standard zurueck. */
    private function logoVariant(Request $request): string
    {
        $variant = $request->query('logo');

        return NewsSocialImage::isLogoVariant(is_string($variant) ? $variant : null)
            ? (string) $variant
            : NewsSocialImage::DEFAULT_LOGO_VARIANT;
    }

    /** Ablageordner je News auf der oeffentlichen Platte des Admins. */
    private function directory(Post $post): string
    {
        return 'news-social/'.$post->id;
    }

    /**
     * Fingerabdruck der Felder, die das Bild bestimmen.
     *
     * `updated_at` ist bewusst dabei: jede Aenderung an der News erzeugt damit
     * ein neues Bild, auch wenn sie eines der hier genannten Felder gar nicht
     * betrifft. LAYOUT_VERSION erzwingt eine Neuerzeugung, wenn sich das
     * Layout selbst aendert.
     */
    private function fingerprint(Post $post, string $format, string $logoVariant): string
    {
        $category = $post->newsCategory;

        return substr(sha1(implode('|', [
            self::LAYOUT_VERSION,
            $format,
            $logoVariant,
            (string) $post->title,
            (string) $post->excerpt,
            (string) ($category?->id ?? ''),
            (string) ($category?->name ?? ''),
            (string) ($category?->color ?? ''),
            (string) ($post->cover_image ?? ''),
            json_encode($post->images ?? []),
            json_encode(NewsSocialImage::normalizeLayoutSettings($post->social_image_settings)),
            (string) optional($post->updated_at)->getTimestamp(),
        ])), 0, 16);
    }

    /**
     * Liefert das Bild aus der Ablage und erzeugt es nur, wenn es dort noch
     * nicht liegt. Beim Neuerzeugen fliegen die alten Staende derselben News
     * und desselben Formats raus, damit sich nichts ansammelt.
     */
    private function cached(Post $post, string $format, NewsSocialImage $generator): string
    {
        $disk = Storage::disk('public');
        $dir = $this->directory($post);
        $variant = $generator->logoVariant();
        $prefix = $format.'-'.$variant.'-';
        $path = $dir.'/'.$prefix.$this->fingerprint($post, $format, $variant).'.png';

        if ($disk->exists($path)) {
            $existing = $disk->get($path);

            if (is_string($existing) && $existing !== '') {
                return $existing;
            }
        }

        $png = $generator->render();

        // Veraltete Staende desselben Formats und derselben Variante entfernen;
        // andere Varianten bleiben liegen. Der zweite Ausdruck raeumt Dateien
        // aus der Zeit vor den Logo-Varianten ab ({format}-{hash}.png).
        foreach ($disk->files($dir) as $file) {
            $name = basename($file);

            if (str_starts_with($name, $prefix)
                || preg_match('/^'.preg_quote($format, '/').'-[0-9a-f]{16}\.png$/', $name) === 1) {
                $disk->delete($file);
            }
        }

        $disk->put($path, $png);

        return $png;
    }

    /** Nur bekannte Zuschnitte zulassen, alles andere faellt auf den Standard. */
    private function format(Request $request): string
    {
        $format = $request->query('format');

        return NewsSocialImage::isFormat(is_string($format) ? $format : null)
            ? (string) $format
            : NewsSocialImage::DEFAULT_FORMAT;
    }

    /**
     * Das Bild wird vollstaendig erzeugt, BEVOR der erste Header rausgeht.
     *
     * Vorher lief das als Stream: die Kopfzeilen mit Status 200 und
     * `image/png` waren bereits gesendet, wenn das Rendern scheiterte. Der
     * Browser bekam damit ein gueltiges Bild mit null Bytes - im Modal genau
     * das Muster "fertig geladen, aber Vorschau fehlgeschlagen", ohne dass ein
     * Fehlerstatus moeglich gewesen waere. GD baut das Bild ohnehin komplett
     * im Speicher auf, ein Stream bringt hier also nichts.
     *
     * Die Daten kommen aus der Ablage; erzeugt wird nur beim ersten Aufruf
     * eines Standes.
     */
    private function stream(Post $post, string $format, string $logoVariant, bool $asAttachment): Response
    {
        abort_unless($post->type === 'news', 404);

        $generator = new NewsSocialImage($post, $format, $logoVariant);

        try {
            $png = $this->cached($post, $format, $generator);
        } catch (Throwable $e) {
            Log::error('Social-Bild konnte nicht erzeugt werden.', [
                'post_id' => $post->id,
                'format' => $format,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            abort(500, 'Das Social-Media-Bild konnte nicht erzeugt werden.');
        }

        if ($png === '') {
            Log::error('Social-Bild ist leer geblieben.', ['post_id' => $post->id, 'format' => $format]);

            abort(500, 'Das Social-Media-Bild konnte nicht erzeugt werden.');
        }

        $disposition = ($asAttachment ? 'attachment' : 'inline')
            .'; filename="'.$generator->filename().'"';

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => (string) strlen($png),
            'Content-Disposition' => $disposition,
            // Der Browser darf nicht puffern: die Adresse bleibt gleich, auch
            // wenn nach einer Aenderung ein neues Bild dahinter liegt.
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
