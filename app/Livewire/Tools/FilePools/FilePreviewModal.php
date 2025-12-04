<?php

namespace App\Livewire\Tools\FilePools;

use Livewire\Component;
use App\Models\File;
use Livewire\Attributes\On;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FilePreviewModal extends Component
{
    public bool $open = false;
    public ?int $fileId = null;
    public ?File $file = null;

    /**
     * Pfad & URL der temporären Datei auf der public-Disk.
     */
    public ?string $tempPath = null;
    public ?string $tempUrl  = null;

    #[On('filepool:preview')] // Livewire-Event (PHP → PHP / JS → PHP)
    public function handlePreview(int $id): void
    {
        $this->openWith($id);
    }

    public function downloadFile(int $fileId): StreamedResponse
    {
        $file = File::findOrFail($fileId);
        return $file->download(); // 👈 zentral im Model
    }

    public function openWith(int $id): void
    {
        $this->fileId = $id;
        $this->file   = File::find($id);

        if (! $this->file) {
            $this->dispatch('toast', type: 'error', message: 'Datei nicht gefunden.');
            return;
        }

        // beim Öffnen Modal zurücksetzen
        $this->tempPath = null;
        $this->tempUrl  = null;

        $this->open = true;
    }

    public function close(): void
    {
        // temp-Datei aufräumen
        if ($this->tempPath && Storage::disk('public')->exists($this->tempPath)) {
            Storage::disk('public')->delete($this->tempPath);
        }

        $this->open     = false;
        $this->fileId   = null;
        $this->file     = null;
        $this->tempPath = null;
        $this->tempUrl  = null;
    }

    /**
     * Wird im Blade als $this->url verwendet.
     * Lädt die Datei einmal über die ephemere URL,
     * legt sie als temp-Datei auf der public-Disk ab und
     * gibt die lokale URL zurück.
     */
    public function getUrlProperty(): ?string
    {
        // Bereits erzeugt? → direkt zurückgeben
        if ($this->tempUrl) {
            return $this->tempUrl;
        }

        if (! $this->file) {
            return null;
        }

        // 1) Ephemere URL von deinem MediaController holen
        $remoteUrl = $this->file->getEphemeralPublicUrl(600); // z. B. 10 Minuten
        if (! $remoteUrl) {
            return null;
        }

        try {
            // 2) Datei vom Remote-Storage holen
            $response = Http::withoutVerifying()->get($remoteUrl);

            if (! $response->successful()) {
                return null;
            }

            $content = $response->body();
        } catch (\Throwable $e) {
            // Optional: Loggen, wenn du willst
            // \Log::warning('FilePreviewModal getUrlProperty error: '.$e->getMessage(), ['file_id' => $this->file->id]);
            return null;
        }

        // 3) sinnvollen Dateinamen/Endung bestimmen
        $nameWithExt = $this->file->name_with_extension ?? ($this->file->name ?? 'datei');
        $ext = pathinfo($nameWithExt, PATHINFO_EXTENSION) ?: 'bin';

        $filename = 'preview_' . $this->file->id . '_' . Str::random(8) . '.' . $ext;
        $tempPath = 'tmp/previews/' . $filename; // wird auf public-Disk gespeichert

        // 4) Inhalt als temp-Datei auf public-Disk schreiben
        Storage::disk('public')->put($tempPath, $content);

        // 5) URL zur temp-Datei merken & zurückgeben
        $this->tempPath = $tempPath;
        $this->tempUrl  = Storage::disk('public')->url($tempPath);

        return $this->tempUrl;
    }

    public function render()
    {
        return view('livewire.tools.file-pools.file-preview-modal');
    }
}
