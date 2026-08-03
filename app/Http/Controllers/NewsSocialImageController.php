<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\NewsSocialImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Throwable;

/**
 * Liefert das Social-Media-Bild einer News.
 *
 * Das Bild wird bei jedem Aufruf frisch im Arbeitsspeicher erzeugt und direkt
 * ausgeliefert. Es wird nirgends abgelegt - es gibt also weder eine Datei auf
 * dem Server noch etwas aufzuraeumen.
 */
class NewsSocialImageController extends Controller
{
    /** Vorschau im Modal. */
    public function preview(Request $request, Post $post): Response
    {
        return $this->stream($post, $this->format($request), false);
    }

    /** Download als Anhang. */
    public function download(Request $request, Post $post): Response
    {
        return $this->stream($post, $this->format($request), true);
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
     * Gespeichert wird weiterhin nichts: das PNG existiert nur als String.
     */
    private function stream(Post $post, string $format, bool $asAttachment): Response
    {
        abort_unless($post->type === 'news', 404);

        $generator = new NewsSocialImage($post, $format);

        try {
            $png = $generator->render();
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
            // Nichts zwischenspeichern: das Bild soll immer den aktuellen
            // Stand der News zeigen.
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
