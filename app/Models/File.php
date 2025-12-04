<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Log;

class File extends Model
{
    // feste Disk für alle Datei-Operationen
    private const DISK = 'public';

    protected $fillable = [
        'filepool_id',
        'user_id',
        'name',
        'path',
        'mime_type',
        'size',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function (File $file) {
            $disk = Storage::disk(self::DISK);

            if ($disk->exists($file->path)) {
                $absPath = $disk->path($file->path);
                $file->uploadImageViaMediaController($absPath);
                unlink($absPath); // Datei nach Upload löschen
            } else {
                Log::warning("File::created - Datei nicht gefunden auf Disk '".self::DISK."': {$file->path}");
            }
        });

        static::deleted(function (File $file) {
            // relativen Pfad an den Controller geben; Disk = public
            $file->deleteImageViaMediaController($file->path);
        });
    }




        /* ------------------------------------------
     * zentrale Dateitypen-Definition
     * ----------------------------------------*/
protected static function fileTypeMap(): array
{
    return [
        // -----------------------------------------
        // OFFICE & DOKUMENTE
        // -----------------------------------------
        'pdf' => [
            'ext'  => 'pdf',
            'label'=> 'PDF-Dokument',
            'icon' => 'file-pdf.png',
            'mime' => ['application/pdf'],
        ],
        'doc' => [
            'ext'  => 'doc',
            'label'=> 'Microsoft Word-Dokument',
            'icon' => 'file-word.png',
            'mime' => [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ],
        'docx' => [
            'ext'  => 'docx',
            'label'=> 'Microsoft Word-Dokument',
            'icon' => 'file-word.png',
            'mime' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ],
        'xls' => [
            'ext'  => 'xls',
            'label'=> 'Microsoft Excel-Arbeitsmappe',
            'icon' => 'file-exel.png',
            'mime' => [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ],
        'xlsx' => [
            'ext'  => 'xlsx',
            'label'=> 'Microsoft Excel-Arbeitsmappe',
            'icon' => 'file-exel.png',
            'mime' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ],
        'ppt' => [
            'ext'  => 'ppt',
            'label'=> 'Microsoft PowerPoint-Präsentation',
            'icon' => 'file-powerpoint.png',
            'mime' => ['application/vnd.ms-powerpoint'],
        ],
        'pptx' => [
            'ext'  => 'pptx',
            'label'=> 'Microsoft PowerPoint-Präsentation',
            'icon' => 'file-powerpoint.png',
            'mime' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        ],
        'csv' => [
            'ext'  => 'csv',
            'label'=> 'CSV-Tabelle',
            'icon' => 'csv-icon.svg',
            'mime' => ['text/csv'],
        ],
        'txt' => [
            'ext'  => 'txt',
            'label'=> 'Textdatei',
            'icon' => 'txt-icon.svg',
            'mime' => ['text/plain'],
        ],
        'xml' => [
            'ext'  => 'xml',
            'label'=> 'XML-Datei',
            'icon' => 'xml-icon.svg',
            'mime' => ['application/xml', 'text/xml'],
        ],
        'html' => [
            'ext'  => 'html',
            'label'=> 'HTML-Dokument',
            'icon' => 'html-icon.svg',
            'mime' => ['text/html'],
        ],
        'htm' => [
            'ext'  => 'htm',
            'label'=> 'HTML-Dokument',
            'icon' => 'html-icon.svg',
            'mime' => ['text/html'],
        ],

        // -----------------------------------------
        // CODE & SCRIPTING
        // -----------------------------------------
        'php' => [
            'ext'  => 'php',
            'label'=> 'PHP-Datei',
            'icon' => 'php-icon.svg',
            'mime' => ['text/x-php', 'application/x-httpd-php', 'application/x-php'],
        ],
        'js' => [
            'ext'  => 'js',
            'label'=> 'JavaScript-Datei',
            'icon' => 'txt-icon.svg',
            'mime' => ['application/javascript', 'text/javascript'],
        ],
        'json' => [
            'ext'  => 'json',
            'label'=> 'JSON-Datei',
            'icon' => 'txt-icon.svg',
            'mime' => ['application/json'],
        ],
        'css' => [
            'ext'  => 'css',
            'label'=> 'CSS-Stylesheet',
            'icon' => 'txt-icon.svg',
            'mime' => ['text/css'],
        ],
        'yaml' => [
            'ext'  => 'yaml',
            'label'=> 'YAML-Datei',
            'icon' => 'txt-icon.svg',
            'mime' => ['application/x-yaml', 'text/yaml'],
        ],
        'sql' => [
            'ext'  => 'sql',
            'label'=> 'SQL-Skript',
            'icon' => 'txt-icon.svg',
            'mime' => ['application/sql', 'text/x-sql'],
        ],
        'md' => [
            'ext'  => 'md',
            'label'=> 'Markdown-Dokument',
            'icon' => 'txt-icon.svg',
            'mime' => ['text/markdown'],
        ],

        // -----------------------------------------
        // GRAFIK / DESIGN
        // -----------------------------------------
        'ai' => [
            'ext'  => 'ai',
            'label'=> 'Adobe Illustrator-Datei',
            'icon' => 'ai-icon.svg',
            'mime' => ['application/postscript', 'application/illustrator'],
        ],
        'eps' => [
            'ext'  => 'eps',
            'label'=> 'PostScript-Datei (EPS)',
            'icon' => 'eps-icon.svg',
            'mime' => ['application/postscript'],
        ],
        'cdr' => [
            'ext'  => 'cdr',
            'label'=> 'CorelDRAW-Datei',
            'icon' => 'cdr-icon.svg',
            'mime' => ['application/vnd.corel-draw'],
        ],
        'raw' => [
            'ext'  => 'raw',
            'label'=> 'RAW-Bilddatei',
            'icon' => 'raw-icon.svg',
            'mime' => ['image/x-raw', 'image/raw'],
        ],
        'gif' => [
            'ext'  => 'gif',
            'label'=> 'GIF-Bild',
            'icon' => 'gif-icon.svg',
            'mime' => ['image/gif'],
        ],
        'jpg' => [
            'ext'  => 'jpg',
            'label'=> 'JPEG-Bild',
            'icon' => null,
            'mime' => ['image/jpeg'],
        ],
        'jpeg' => [
            'ext'  => 'jpeg',
            'label'=> 'JPEG-Bild',
            'icon' => null,
            'mime' => ['image/jpeg'],
        ],
        'png' => [
            'ext'  => 'png',
            'label'=> 'PNG-Bild',
            'icon' => null,
            'mime' => ['image/png'],
        ],
        'svg' => [
            'ext'  => 'svg',
            'label'=> 'SVG-Vektorgrafik',
            'icon' => null,
            'mime' => ['image/svg+xml'],
        ],
        'webp' => [
            'ext'  => 'webp',
            'label'=> 'WebP-Bild',
            'icon' => null,
            'mime' => ['image/webp'],
        ],
        'heic' => [
            'ext'  => 'heic',
            'label'=> 'HEIC-Bild',
            'icon' => null,
            'mime' => ['image/heic', 'image/heif'],
        ],

        // -----------------------------------------
        // AUDIO / VIDEO
        // -----------------------------------------
        'mp3' => [
            'ext'  => 'mp3',
            'label'=> 'MP3-Audiodatei',
            'icon' => 'mp3-icon.svg',
            'mime' => ['audio/mpeg'],
        ],
        'wav' => [
            'ext'  => 'wav',
            'label'=> 'WAV-Audiodatei',
            'icon' => 'wav-icon.svg',
            'mime' => ['audio/wav', 'audio/x-wav'],
        ],
        'mp4' => [
            'ext'  => 'mp4',
            'label'=> 'MP4-Video',
            'icon' => 'mp4-icon.svg',
            'mime' => ['video/mp4'],
        ],
        'avi' => [
            'ext'  => 'avi',
            'label'=> 'AVI-Video',
            'icon' => 'file-video.png',
            'mime' => ['video/x-msvideo'],
        ],
        'mov' => [
            'ext'  => 'mov',
            'label'=> 'MOV-Video',
            'icon' => 'file-video.png',
            'mime' => ['video/quicktime'],
        ],
        'mpg' => [
            'ext'  => 'mpg',
            'label'=> 'MPEG-Video',
            'icon' => 'file-video.png',
            'mime' => ['video/mpeg'],
        ],
        'mpeg' => [
            'ext'  => 'mpeg',
            'label'=> 'MPEG-Video',
            'icon' => 'file-video.png',
            'mime' => ['video/mpeg'],
        ],

        // -----------------------------------------
        // ARCHIVE
        // -----------------------------------------
        'zip' => [
            'ext'  => 'zip',
            'label'=> 'ZIP-Archiv',
            'icon' => 'zip-icon.svg',
            'mime' => ['application/zip', 'application/x-zip-compressed'],
        ],
        'rar' => [
            'ext'  => 'rar',
            'label'=> 'RAR-Archiv',
            'icon' => 'rar-icon.svg',
            'mime' => ['application/x-rar-compressed'],
        ],
        '7z' => [
            'ext'  => '7z',
            'label'=> '7z-Archiv',
            'icon' => 'zip-icon.svg',
            'mime' => ['application/x-7z-compressed'],
        ],
        'tar' => [
            'ext'  => 'tar',
            'label'=> 'TAR-Archiv',
            'icon' => 'zip-icon.svg',
            'mime' => ['application/x-tar'],
        ],
        'gz' => [
            'ext'  => 'gz',
            'label'=> 'GZIP-Archiv',
            'icon' => 'zip-icon.svg',
            'mime' => ['application/gzip'],
        ],

        // -----------------------------------------
        // FALLBACK
        // -----------------------------------------
        '*' => [
            'ext'  => null,
            'label'=> 'Datei',
            'icon' => 'doc-icon.svg',
            'mime' => ['application/octet-stream'],
        ],
    ];
}

    /**
     * Übergibt eine lokale Datei als UploadedFile an den MediaController::store
     * @param string $absolutePath absoluter Serverpfad (KEINE URL)
     */
    protected function uploadImageViaMediaController(string $absolutePath): ?string
    {
        if (!is_file($absolutePath)) {
            Log::warning("uploadImageViaMediaController: Datei nicht gefunden: {$absolutePath}");
            return null;
        }

        try {
            $uploaded = new UploadedFile(
                $absolutePath,
                basename($absolutePath),
                @mime_content_type($absolutePath) ?: null,
                UPLOAD_ERR_OK,
                true // Testmodus
            );

            $request = Request::create('/admin/media/upload', 'POST');
            $request->files->set('file', $uploaded);

            $controller = app(MediaController::class);
            $response = $controller->store($request);

            if (method_exists($response, 'getData')) {
                $data = $response->getData(true);
                Log::info('Upload via MediaController erfolgreich', ['path' => $data['path'] ?? null]);
                
                // speichern des neuen Pfads in der Datenbank
                $this->update(['path' => $data['path'] ?? null]);
                // Rückgabe des neuen Pfads
                return $data['path'] ?? null;
            }

            Log::warning('Upload via MediaController ohne getData()-Antwort.');
            return null;

        } catch (\Throwable $e) {
            Log::warning('Bild konnte nicht über MediaController hochgeladen werden: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Löscht über den MediaController per relativem Pfad (Disk = public)
     */
    protected function deleteImageViaMediaController(?string $relativePath): void
    {
        if (!$relativePath) return;

        try {
            $request = Request::create('/admin/media/delete', 'POST', ['path' => $relativePath]);

            /** @var MediaController $controller */
            $controller = app(MediaController::class);
            $response = $controller->destroy($request);

            if (method_exists($response, 'getData')) {
                $result = $response->getData(true);
                if (!($result['success'] ?? false)) {
                    Log::warning('Löschen nicht erfolgreich: ' . ($result['message'] ?? 'unbekannt'));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Bild konnte nicht über MediaController gelöscht werden: ' . $e->getMessage());
        }
    }

    // App/Models/File.php

    public function getEphemeralPublicUrl(int $ttl = 300, ?string $disk = null): ?string
    {
        try {
            // künstlichen Request an den MediaController bauen
            $params = [
                'file_id' => $this->id,
                'expires' => $ttl,
            ];

            // optional: Disk explizit mitgeben (falls du es brauchst)
            if ($disk) {
                $params['disk'] = $disk; // 'private' oder 'public'
            }

            $request = Request::create('/admin/media/resolve', 'POST', $params);

            /** @var \App\Http\Controllers\MediaController $controller */
            $controller = app(\App\Http\Controllers\MediaController::class);
            $response   = $controller->resolve($request);

            if (method_exists($response, 'getData')) {
                $data = $response->getData(true);

                if (!empty($data['success']) && !empty($data['url'])) {
                    return $data['url'];
                }

                Log::warning('Ephemeral-URL vom MediaController ohne URL', ['data' => $data, 'file_id' => $this->id]);
            }
        } catch (\Throwable $e) {
            Log::warning('getEphemeralPublicUrl fehlgeschlagen: '.$e->getMessage(), ['file_id' => $this->id]);
        }

        return null;
    }


    /** Morphable Beziehung – z. B. zu User, Course, Task, etc. */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }


        protected static function resolveFileType(?string $mime, ?string $ext): array
    {
        $types = static::fileTypeMap();
        $mime = strtolower((string) $mime);
        $ext  = strtolower((string) $ext);

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

    public function getIsImageAttribute(): bool
    {
        $mime = (string) ($this->mime_type ?? '');
        $ext  = strtolower((string) ($this->guessExtension($mime, $this->path) ?? ''));
        $type = static::resolveFileType($mime, $ext);

        if (str_starts_with($mime, 'image/') && $type['icon'] === null) {
            return true;
        }else {
            return false;
        }
    }

    public function getIconOrThumbnailAttribute(): string
    {
        $mime = (string) ($this->mime_type ?? '');
        $ext  = strtolower((string) ($this->guessExtension($mime, $this->path) ?? ''));
        $type = static::resolveFileType($mime, $ext);

        if (str_starts_with($mime, 'image/') && $type['icon'] === null) {
            return $this->getEphemeralPublicUrl(10);
        }
        return asset('site-images/fileicons/' . ($type['icon'] ?? static::fileTypeMap()['*']['icon']));
    }

    public function getSizeFormattedAttribute(): string
    {
        $bytes = (int) $this->size;
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1000000) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        } else {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }
    }
    public function getIsOwnedByAuthUserAttribute(): bool
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return false;
        }
        return (int) $this->user_id === (int) $authUser->id;
    }

    public function getNameWithExtensionAttribute(): string
    {
        $safeName = $this->sanitizeName($this->name ?? 'datei');
        $ext = pathinfo($safeName, PATHINFO_EXTENSION);
        if ($ext !== '') {
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
                if (!empty($candidates)) {
                    return strtolower($candidates[0]);
                }
            } catch (\Throwable $e) {
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
        $ext  = $this->guessExtension($mime, $this->path);
        $type = static::resolveFileType($mime, $ext);
        $label = $type['label'] ?? 'Datei';
        $extU = $type['ext'] ? strtoupper($type['ext']) : null;
        return $extU ? "{$label} ({$extU})" : $label;
    }
}
