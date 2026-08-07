<?php

namespace App\Livewire\Admin\Cms\WebContent\News;

use App\Models\Post;
use App\Support\NewsSocialImage as SocialImageRenderer;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Vorschau und Download des Social-Media-Bildes einer News.
 *
 * Gleiche Mechanik wie NewsEditCreate: Die Komponente wird einmal pro Seite
 * eingebunden und ueber einen Livewire-Dispatch geoeffnet. Dadurch haengt die
 * Sichtbarkeit an einer serverseitigen Eigenschaft und nicht an Alpine-Zustand
 * im DOM - genau das hatte zuvor dazu gefuehrt, dass der Overlay dauerhaft
 * offen stand und sich nicht schliessen liess.
 */
class NewsSocialImage extends Component
{
    public bool $show = false;

    public ?int $postId = null;

    public string $title = '';

    /** Aktiver Zuschnitt; siehe SocialImageRenderer::FORMATS. */
    public string $format = SocialImageRenderer::DEFAULT_FORMAT;

    /** Gewaehlte Logo-Variante; siehe SocialImageRenderer::LOGO_VARIANTS. */
    public string $logoVariant = SocialImageRenderer::DEFAULT_LOGO_VARIANT;

    /** Vollstaendige, pro Format getrennte Layoutkonfiguration. */
    public array $layoutSettings = [];

    public bool $layoutSettingsDirty = false;

    /** Merkt ungespeicherte Aenderungen getrennt je Bildformat. */
    public array $dirtyFormats = [];

    public ?string $settingsStatus = null;

    /** Erzwingt nach dem Speichern eine frische Bildanfrage je Format. */
    public array $previewRevisions = [];

    protected $listeners = [
        'open-news-social-image' => 'openModal',
    ];

    public function openModal($postId = null): void
    {
        // Der Dispatch liefert je nach Aufrufart einen Skalar oder ein Array.
        if (is_array($postId)) {
            $postId = $postId['postId'] ?? null;
        }

        $post = $postId ? Post::where('type', 'news')->find((int) $postId) : null;

        if (! $post) {
            $this->closeModal();

            return;
        }

        $this->postId = $post->id;
        $this->title = (string) $post->title;
        $this->format = SocialImageRenderer::DEFAULT_FORMAT;
        $this->logoVariant = SocialImageRenderer::DEFAULT_LOGO_VARIANT;
        $this->layoutSettings = SocialImageRenderer::normalizeLayoutSettings($post->social_image_settings);
        $this->logoVariant = $this->layoutSettings[$this->format]['logo_variant'];
        $this->layoutSettingsDirty = false;
        $this->dirtyFormats = array_fill_keys(array_keys(SocialImageRenderer::FORMATS), false);
        $this->settingsStatus = null;
        $this->previewRevisions = array_fill_keys(array_keys(SocialImageRenderer::FORMATS), 0);
        $this->resetValidation();
        $this->show = true;
    }

    /** Format wechseln; das Bild wird daraufhin neu angefordert. */
    public function setFormat(string $format): void
    {
        if (SocialImageRenderer::isFormat($format)) {
            $this->format = $format;
            $this->logoVariant = $this->layoutSettings[$format]['logo_variant'];
            $this->layoutSettingsDirty = (bool) ($this->dirtyFormats[$format] ?? false);
            $this->settingsStatus = null;
        }
    }

    /**
     * Kommt ueber wire:model.live vom Selectfeld. Ungueltige Werte fallen auf
     * den Standard zurueck, damit ueber das DOM nichts anderes einschleusbar
     * ist; die Vorschau laedt durch den Re-Render automatisch neu.
     */
    public function updatedLogoVariant(string $value): void
    {
        if (! SocialImageRenderer::isLogoVariant($value)) {
            $this->logoVariant = SocialImageRenderer::DEFAULT_LOGO_VARIANT;
        }

        $this->layoutSettings[$this->format]['logo_variant'] = $this->logoVariant;
        $this->dirtyFormats[$this->format] = true;
        $this->layoutSettingsDirty = true;
        $this->settingsStatus = null;
        $this->saveLayoutSettings($this->format);
    }

    public function updatedLayoutSettings(mixed $value = null, ?string $key = null): void
    {
        $changedFormat = is_string($key) ? strtok($key, '.') : $this->format;

        if (! SocialImageRenderer::isFormat($changedFormat)) {
            $changedFormat = $this->format;
        }

        $this->dirtyFormats[$changedFormat] = true;
        $this->layoutSettingsDirty = (bool) ($this->dirtyFormats[$this->format] ?? false);
        $this->settingsStatus = null;
        $this->resetValidation();

        // Jede Dropdown-Aenderung ist sofort dauerhaft und erzeugt durch die
        // neue Format-Revision unmittelbar eine frische Bildanfrage.
        $this->saveLayoutSettings($changedFormat);
    }

    /** Nur die Einstellungen des aktuell sichtbaren Formats zuruecksetzen. */
    public function resetCurrentFormat(): void
    {
        if (! SocialImageRenderer::isFormat($this->format)) {
            return;
        }

        $this->layoutSettings[$this->format] = SocialImageRenderer::defaultLayoutSettings()[$this->format];
        $this->dirtyFormats[$this->format] = true;
        $this->layoutSettingsDirty = true;
        $this->resetValidation();
        $this->saveLayoutSettings($this->format);
        $this->settingsStatus = sprintf(
            '%s auf Standard zurückgesetzt und neu gerendert.',
            SocialImageRenderer::FORMATS[$this->format]['label']
        );
    }

    /** Speichert ausschliesslich das aktuell sichtbare Bildformat. */
    public function saveLayoutSettings(?string $requestedFormat = null): void
    {
        $post = $this->postId
            ? Post::where('type', 'news')->find($this->postId)
            : null;

        if (! $post) {
            $this->closeModal();

            return;
        }

        $format = SocialImageRenderer::isFormat($requestedFormat)
            ? $requestedFormat
            : (SocialImageRenderer::isFormat($this->format)
                ? $this->format
                : SocialImageRenderer::DEFAULT_FORMAT);
        $validated = $this->validate($this->rulesForFormat($format), $this->messages());
        $stored = SocialImageRenderer::normalizeLayoutSettings($post->social_image_settings);
        $submitted = SocialImageRenderer::normalizeLayoutSettings([
            $format => $validated['layoutSettings'][$format],
        ]);

        $stored[$format] = $submitted[$format];

        // Layout-Metadaten veraendern nicht den redaktionellen updated_at-Wert.
        // So bleiben auch die Bild-Caches der beiden anderen Formate bestehen.
        $post->social_image_settings = $stored;
        $post->timestamps = false;

        try {
            $post->save();
        } finally {
            $post->timestamps = true;
        }

        $this->layoutSettings[$format] = $stored[$format];
        $this->dirtyFormats[$format] = false;
        $this->layoutSettingsDirty = false;
        $this->settingsStatus = sprintf(
            'Einstellungen für %s gespeichert und Bild neu gerendert.',
            SocialImageRenderer::FORMATS[$format]['label']
        );
        $this->previewRevisions[$format] = ($this->previewRevisions[$format] ?? 0) + 1;
    }

    protected function rules(): array
    {
        $format = SocialImageRenderer::isFormat($this->format)
            ? $this->format
            : SocialImageRenderer::DEFAULT_FORMAT;

        return $this->rulesForFormat($format);
    }

    private function rulesForFormat(string $format): array
    {
        $rules = [
            'layoutSettings' => ['required', 'array'],
            "layoutSettings.{$format}" => ['required', 'array'],
            "layoutSettings.{$format}.logo_variant" => [
                'required',
                Rule::in(array_keys(SocialImageRenderer::LOGO_VARIANTS)),
            ],
        ];

        foreach (SocialImageRenderer::LAYOUT_CONTROLS as $key => $control) {
            $rules["layoutSettings.{$format}.{$key}"] = [
                'required',
                'integer',
                Rule::in(array_keys($control['options'])),
            ];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'layoutSettings.required' => 'Die Bildeinstellungen fehlen.',
            'layoutSettings.array' => 'Die Bildeinstellungen haben ein ungültiges Format.',
            'layoutSettings.*.required' => 'Die Einstellungen für ein Bildformat fehlen.',
            'layoutSettings.*.array' => 'Die Einstellungen eines Bildformats sind ungültig.',
            'layoutSettings.*.*.required' => 'Bitte wähle für jede Bildeinstellung einen Wert aus.',
            'layoutSettings.*.*.integer' => 'Die gewählte Bildeinstellung ist ungültig.',
            'layoutSettings.*.*.in' => 'Dieser Wert ist für die Bildeinstellung nicht erlaubt.',
        ];
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->postId = null;
        $this->title = '';
        $this->format = SocialImageRenderer::DEFAULT_FORMAT;
        $this->logoVariant = SocialImageRenderer::DEFAULT_LOGO_VARIANT;
        $this->layoutSettings = [];
        $this->layoutSettingsDirty = false;
        $this->dirtyFormats = [];
        $this->settingsStatus = null;
        $this->previewRevisions = [];
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.cms.web-content.news.news-social-image', [
            'formats' => SocialImageRenderer::FORMATS,
            'logoVariants' => SocialImageRenderer::LOGO_VARIANTS,
            'layoutControls' => SocialImageRenderer::LAYOUT_CONTROLS,
        ]);
    }
}
