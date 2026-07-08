<?php

namespace App\Models;

use App\Http\Controllers\MediaController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mime\MimeTypes;

class File extends Model
{
    protected $fillable = [
        'filepool_id',
        'user_id',
        'name',
        'path',
        'disk',
        'mime_type',
        'type',
        'size',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleted(function (File $file) {
            $file->deleteViaMediaController();
        });
    }

    protected function deleteViaMediaController(): void
    {
        if (! $this->path) {
            return;
        }

        try {
            $request = Request::create('/admin/media/delete', 'DELETE', [
                'path' => $this->path,
                'visibility' => $this->storageDisk(),
            ]);

            $response = app(MediaController::class)->destroy($request);
            $data = method_exists($response, 'getData') ? $response->getData(true) : [];

            if (! ($data['success'] ?? false)) {
                Log::warning('Datei konnte ueber Base-API nicht geloescht werden.', [
                    'file_id' => $this->id,
                    'path' => $this->path,
                    'response' => $data,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Datei konnte ueber Base-API nicht geloescht werden.', [
                'file_id' => $this->id,
                'path' => $this->path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected static function fileTypeMap(): array
    {
        return [
            'pdf' => ['ext' => 'pdf', 'label' => 'PDF-Dokument', 'icon' => 'file-pdf.png', 'mime' => ['application/pdf']],
            'doc' => ['ext' => 'doc', 'label' => 'Microsoft Word-Dokument', 'icon' => 'file-word.png', 'mime' => ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']],
            'docx' => ['ext' => 'docx', 'label' => 'Microsoft Word-Dokument', 'icon' => 'file-word.png', 'mime' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document']],
            'xls' => ['ext' => 'xls', 'label' => 'Microsoft Excel-Arbeitsmappe', 'icon' => 'file-exel.png', 'mime' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']],
            'xlsx' => ['ext' => 'xlsx', 'label' => 'Microsoft Excel-Arbeitsmappe', 'icon' => 'file-exel.png', 'mime' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']],
            'ppt' => ['ext' => 'ppt', 'label' => 'Microsoft PowerPoint-Praesentation', 'icon' => 'file-powerpoint.png', 'mime' => ['application/vnd.ms-powerpoint']],
            'pptx' => ['ext' => 'pptx', 'label' => 'Microsoft PowerPoint-Praesentation', 'icon' => 'file-powerpoint.png', 'mime' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation']],
            'csv' => ['ext' => 'csv', 'label' => 'CSV-Tabelle', 'icon' => 'csv-icon.svg', 'mime' => ['text/csv']],
            'txt' => ['ext' => 'txt', 'label' => 'Textdatei', 'icon' => 'txt-icon.svg', 'mime' => ['text/plain']],
            'xml' => ['ext' => 'xml', 'label' => 'XML-Datei', 'icon' => 'xml-icon.svg', 'mime' => ['application/xml', 'text/xml']],
            'html' => ['ext' => 'html', 'label' => 'HTML-Dokument', 'icon' => 'html-icon.svg', 'mime' => ['text/html']],
            'htm' => ['ext' => 'htm', 'label' => 'HTML-Dokument', 'icon' => 'html-icon.svg', 'mime' => ['text/html']],
            'php' => ['ext' => 'php', 'label' => 'PHP-Datei', 'icon' => 'php-icon.svg', 'mime' => ['text/x-php', 'application/x-httpd-php', 'application/x-php']],
            'js' => ['ext' => 'js', 'label' => 'JavaScript-Datei', 'icon' => 'txt-icon.svg', 'mime' => ['application/javascript', 'text/javascript']],
            'json' => ['ext' => 'json', 'label' => 'JSON-Datei', 'icon' => 'txt-icon.svg', 'mime' => ['application/json']],
            'css' => ['ext' => 'css', 'label' => 'CSS-Stylesheet', 'icon' => 'txt-icon.svg', 'mime' => ['text/css']],
            'yaml' => ['ext' => 'yaml', 'label' => 'YAML-Datei', 'icon' => 'txt-icon.svg', 'mime' => ['application/x-yaml', 'text/yaml']],
            'sql' => ['ext' => 'sql', 'label' => 'SQL-Skript', 'icon' => 'txt-icon.svg', 'mime' => ['application/sql', 'text/x-sql']],
            'md' => ['ext' => 'md', 'label' => 'Markdown-Dokument', 'icon' => 'txt-icon.svg', 'mime' => ['text/markdown']],
            'ai' => ['ext' => 'ai', 'label' => 'Adobe Illustrator-Datei', 'icon' => 'ai-icon.svg', 'mime' => ['application/postscript', 'application/illustrator']],
            'eps' => ['ext' => 'eps', 'label' => 'PostScript-Datei (EPS)', 'icon' => 'eps-icon.svg', 'mime' => ['application/postscript']],
            'cdr' => ['ext' => 'cdr', 'label' => 'CorelDRAW-Datei', 'icon' => 'cdr-icon.svg', 'mime' => ['application/vnd.corel-draw']],
            'raw' => ['ext' => 'raw', 'label' => 'RAW-Bilddatei', 'icon' => 'raw-icon.svg', 'mime' => ['image/x-raw', 'image/raw']],
            'gif' => ['ext' => 'gif', 'label' => 'GIF-Bild', 'icon' => 'gif-icon.svg', 'mime' => ['image/gif']],
            'jpg' => ['ext' => 'jpg', 'label' => 'JPEG-Bild', 'icon' => null, 'mime' => ['image/jpeg']],
            'jpeg' => ['ext' => 'jpeg', 'label' => 'JPEG-Bild', 'icon' => null, 'mime' => ['image/jpeg']],
            'png' => ['ext' => 'png', 'label' => 'PNG-Bild', 'icon' => null, 'mime' => ['image/png']],
            'svg' => ['ext' => 'svg', 'label' => 'SVG-Vektorgrafik', 'icon' => null, 'mime' => ['image/svg+xml']],
            'webp' => ['ext' => 'webp', 'label' => 'WebP-Bild', 'icon' => null, 'mime' => ['image/webp']],
            'heic' => ['ext' => 'heic', 'label' => 'HEIC-Bild', 'icon' => null, 'mime' => ['image/heic', 'image/heif']],
            'mp3' => ['ext' => 'mp3', 'label' => 'MP3-Audiodatei', 'icon' => 'mp3-icon.svg', 'mime' => ['audio/mpeg']],
            'wav' => ['ext' => 'wav', 'label' => 'WAV-Audiodatei', 'icon' => 'wav-icon.svg', 'mime' => ['audio/wav', 'audio/x-wav']],
            'mp4' => ['ext' => 'mp4', 'label' => 'MP4-Video', 'icon' => 'mp4-icon.svg', 'mime' => ['video/mp4']],
            'avi' => ['ext' => 'avi', 'label' => 'AVI-Video', 'icon' => 'file-video.png', 'mime' => ['video/x-msvideo']],
            'mov' => ['ext' => 'mov', 'label' => 'MOV-Video', 'icon' => 'file-video.png', 'mime' => ['video/quicktime']],
            'mpg' => ['ext' => 'mpg', 'label' => 'MPEG-Video', 'icon' => 'file-video.png', 'mime' => ['video/mpeg']],
            'mpeg' => ['ext' => 'mpeg', 'label' => 'MPEG-Video', 'icon' => 'file-video.png', 'mime' => ['video/mpeg']],
            'zip' => ['ext' => 'zip', 'label' => 'ZIP-Archiv', 'icon' => 'zip-icon.svg', 'mime' => ['application/zip', 'application/x-zip-compressed']],
            'rar' => ['ext' => 'rar', 'label' => 'RAR-Archiv', 'icon' => 'rar-icon.svg', 'mime' => ['application/x-rar-compressed']],
            '7z' => ['ext' => '7z', 'label' => '7z-Archiv', 'icon' => 'zip-icon.svg', 'mime' => ['application/x-7z-compressed']],
            'tar' => ['ext' => 'tar', 'label' => 'TAR-Archiv', 'icon' => 'zip-icon.svg', 'mime' => ['application/x-tar']],
            'gz' => ['ext' => 'gz', 'label' => 'GZIP-Archiv', 'icon' => 'zip-icon.svg', 'mime' => ['application/gzip']],
            '*' => ['ext' => null, 'label' => 'Datei', 'icon' => 'doc-icon.svg', 'mime' => ['application/octet-stream']],
        ];
    }

    protected static function resolveFileType(?string $mime, ?string $ext): array
    {
        $types = static::fileTypeMap();
        $mime = strtolower((string) $mime);
        $ext = strtolower((string) $ext);

        if ($ext && isset($types[$ext])) {
            return $types[$ext];
        }

        foreach ($types as $type) {
            if (in_array($mime, $type['mime'] ?? [], true)) {
                return $type;
            }
        }

        return $types['*'];
    }

    public function storageDisk(): string
    {
        if (in_array($this->disk, ['public', 'private'], true)) {
            return $this->disk;
        }

        return 'private';
    }

    public function getIsImageAttribute(): bool
    {
        $mime = (string) ($this->mime_type ?? '');
        $ext = strtolower((string) ($this->guessExtension($mime, $this->path) ?? ''));
        $type = static::resolveFileType($mime, $ext);

        return str_starts_with($mime, 'image/') && $type['icon'] === null;
    }

    public function getIconOrThumbnailAttribute(): string
    {
        $mime = (string) ($this->mime_type ?? '');
        $ext = strtolower((string) ($this->guessExtension($mime, $this->path) ?? ''));
        $type = static::resolveFileType($mime, $ext);

        if ($this->is_image) {
            return $this->getEphemeralPublicUrl(10) ?: asset('site-images/fileicons/' . static::fileTypeMap()['*']['icon']);
        }

        return asset('site-images/fileicons/' . ($type['icon'] ?? static::fileTypeMap()['*']['icon']));
    }

    public function getSizeFormattedAttribute(): string
    {
        $bytes = (int) $this->size;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1000000) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }

        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }

    public function getEphemeralPublicUrl(int $minutes = 10, ?string $disk = null): string
    {
        if (! $this->id && ! $this->path) {
            return '';
        }

        try {
            $params = [
                'expires' => max(30, min(86400, $minutes * 60)),
            ];

            if ($this->id) {
                $params['file_id'] = $this->id;
            } else {
                $params['url'] = $this->path;
            }

            if (in_array($disk, ['private', 'public'], true)) {
                $params['disk'] = $disk;
            } elseif (in_array($this->disk, ['private', 'public'], true)) {
                $params['disk'] = $this->disk;
            }

            $response = app(MediaController::class)->resolve(
                Request::create('/admin/media/resolve', 'POST', $params)
            );

            $data = method_exists($response, 'getData') ? $response->getData(true) : [];

            if (! empty($data['success']) && ! empty($data['url'])) {
                return (string) $data['url'];
            }

            Log::warning('Ephemere Datei-URL konnte nicht ueber Base-API erzeugt werden.', [
                'file_id' => $this->id,
                'path' => $this->path,
                'response' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Ephemere Datei-URL konnte nicht ueber Base-API erzeugt werden.', [
                'file_id' => $this->id,
                'path' => $this->path,
                'error' => $e->getMessage(),
            ]);
        }

        return '';
    }

    public function download(?string $disk = null, bool $denyExpired = true): StreamedResponse
    {
        if ($denyExpired && $this->isExpired()) {
            abort(403, 'Diese Datei ist abgelaufen und kann nicht mehr heruntergeladen werden.');
        }

        $url = $this->getEphemeralPublicUrl(10, $disk);
        if (! $url) {
            abort(404, 'Datei konnte nicht geladen werden.');
        }

        $filename = $this->name_with_extension ?? $this->name ?? 'datei';
        $mime = $this->mime_type ?: 'application/octet-stream';

        $response = Http::timeout(120)
            ->withOptions(['stream' => true])
            ->withoutVerifying()
            ->get($url);

        if (! $response->successful()) {
            abort($response->status(), 'Datei konnte nicht geladen werden.');
        }

        $body = $response->toPsrResponse()->getBody();

        return response()->streamDownload(function () use ($body) {
            while (! $body->eof()) {
                echo $body->read(8192);

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            }
        }, $filename, ['Content-Type' => $mime]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsOwnedByAuthUserAttribute(): bool
    {
        $authUser = Auth::user();

        return $authUser && (int) $this->user_id === (int) $authUser->id;
    }

    public function getNameWithExtensionAttribute(): string
    {
        $safeName = $this->sanitizeName($this->name ?? 'datei');
        if (pathinfo($safeName, PATHINFO_EXTENSION) !== '') {
            return $safeName;
        }

        $mime = $this->mime_type ?? '';
        $guessed = $this->guessExtension($mime, $this->path);
        $type = static::resolveFileType($mime, $guessed);
        $finalExt = $type['ext'] ?? $guessed;

        return $finalExt ? ($safeName . '.' . $finalExt) : $safeName;
    }

    protected function sanitizeName(string $name): string
    {
        $name = trim($name);
        $name = str_replace(['\\', '/', "\0"], '-', $name);

        return $name === '' ? 'datei' : $name;
    }

    protected function guessExtension(?string $mime, ?string $storagePath): ?string
    {
        if ($mime) {
            try {
                $candidates = MimeTypes::getDefault()->getExtensions($mime);
                if (! empty($candidates)) {
                    return strtolower($candidates[0]);
                }
            } catch (\Throwable) {
            }
        }

        if ($storagePath) {
            $ext = pathinfo($storagePath, PATHINFO_EXTENSION);
            if ($ext !== '') {
                return strtolower($ext);
            }
        }

        return null;
    }

    public function getMimeTypeForHumans(): string
    {
        $mime = strtolower((string) $this->mime_type);
        $ext = $this->guessExtension($mime, $this->path);
        $type = static::resolveFileType($mime, $ext);
        $label = $type['label'] ?? 'Datei';
        $extU = $type['ext'] ? strtoupper($type['ext']) : null;

        return $extU ? "{$label} ({$extU})" : $label;
    }

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
