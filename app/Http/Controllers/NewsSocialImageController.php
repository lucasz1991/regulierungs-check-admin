<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\NewsSocialImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
    public function preview(Request $request, Post $post): StreamedResponse
    {
        return $this->stream($post, $this->format($request), false);
    }

    /** Download als Anhang. */
    public function download(Request $request, Post $post): StreamedResponse
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

    private function stream(Post $post, string $format, bool $asAttachment): StreamedResponse
    {
        abort_unless($post->type === 'news', 404);

        $generator = new NewsSocialImage($post, $format);

        $disposition = $asAttachment
            ? 'attachment; filename="'.$generator->filename().'"'
            : 'inline; filename="'.$generator->filename().'"';

        return response()->stream(
            function () use ($generator, $post): void {
                try {
                    echo $generator->render();
                } catch (Throwable $e) {
                    // Der Header ist zu diesem Zeitpunkt schon raus, ein
                    // sauberer Fehlerstatus geht nicht mehr. Deshalb
                    // protokollieren und den Stream leer beenden.
                    Log::error('Social-Bild konnte nicht erzeugt werden.', [
                        'post_id' => $post->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            },
            200,
            [
                'Content-Type' => 'image/png',
                'Content-Disposition' => $disposition,
                // Nichts zwischenspeichern: das Bild soll immer den aktuellen
                // Stand der News zeigen.
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
