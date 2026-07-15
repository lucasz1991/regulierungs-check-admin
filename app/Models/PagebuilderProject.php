<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PagebuilderProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'data',
        'html',
        'cleaned_html',
        'js',
        'css',
        'last_edited_by',
        'page',
        'position',
        'lang',
        'lock',
        'published_from',
        'published_until',
        'order_id',
        'status',
        'type',
    ];

    protected $casts = [
        'page' => 'array',
        'position' => 'array',
        'lock' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::updated(function (PagebuilderProject $project): void {
            $project->updateHtmlContent($project);
            $project->setLastEditor();
        });
    }

    public function updateProjekt(?string $exportedCss = null): void
    {
        $project = PagebuilderProject::find($this->id);

        if (! $project) {
            return;
        }

        $project->updateHtmlContent($project, $exportedCss);
        $project->setLastEditor();
    }

    public function updateHtmlContent(
        PagebuilderProject $project,
        ?string $exportedCss = null
    ): void {
        $embeddedCss = $project->updateHtmlFromData();
        $project->updateCssFromData($exportedCss, $embeddedCss);
    }

    public function setLastEditor(): void
    {
        if (auth()->check()) {
            $this->updateQuietly(['last_edited_by' => auth()->id()]);
        }
    }

    /**
     * Speichert ausschließlich den Body-Inhalt. Script- und Style-Tags werden
     * vorher entfernt und in ihren eigenen Datenbankfeldern abgelegt.
     */
    public function updateHtmlFromData(): string
    {
        $html = (string) $this->html;

        if ($html === '') {
            $this->updateQuietly([
                'cleaned_html' => '',
                'js' => '',
            ]);

            return '';
        }

        [$htmlWithoutScripts, $extractedJs] = $this->extractScripts($html);
        [$htmlWithoutStyles, $extractedCss] = $this->extractStyles($htmlWithoutScripts);

        $this->cleaned_html = $this->extractBodyContent($htmlWithoutStyles);
        $this->js = $this->sanitizeJs($extractedJs);
        $this->updateQuietly([
            'cleaned_html' => $this->cleaned_html,
            'js' => $this->js,
        ]);

        return $extractedCss;
    }

    private function extractScripts(string $html): array
    {
        $scriptBlocks = [];
        $cleanedHtml = preg_replace_callback(
            '/<script\b[^>]*>(.*?)<\/script>/is',
            function (array $matches) use (&$scriptBlocks): string {
                $scriptBlocks[] = trim($matches[1]);

                return '';
            },
            $html
        );

        return [$cleanedHtml ?? $html, implode("\n", $scriptBlocks)];
    }

    private function extractStyles(string $html): array
    {
        $styleBlocks = [];
        $cleanedHtml = preg_replace_callback(
            '/<style\b[^>]*>(.*?)<\/style>/is',
            function (array $matches) use (&$styleBlocks): string {
                $styleBlocks[] = trim($matches[1]);

                return '';
            },
            $html
        );

        return [$cleanedHtml ?? $html, implode("\n", $styleBlocks)];
    }

    private function sanitizeJs(string $js): string
    {
        $js = preg_replace(
            '/https?:\/\/[^"\']*swiper-bundle\.min\.js/i',
            '/adminresources/js/swiper-bundle.min.js',
            $js
        ) ?? '';
        $js = preg_replace(
            '/https?:\/\/[^"\']*swiper-bundle\.min\.css/i',
            '/adminresources/css/swiper-bundle.min.css',
            $js
        ) ?? '';

        return preg_replace(
            '/https?:\/\/(cdn\.(jsdelivr|cdnjs|unpkg)\.com|[^\s"\']+)/i',
            '',
            $js
        ) ?? '';
    }

    private function extractBodyContent(string $html): string
    {
        if ($html === '' || ! Str::contains($html, '<body')) {
            return $html;
        }

        if (! preg_match('/<body([^>]*)>(.*?)<\/body>/si', $html, $matches)) {
            return $html;
        }

        $bodyAttributes = trim($matches[1]);
        $bodyContent = trim($matches[2]);
        $attributes = $bodyAttributes === '' ? '' : ' '.$bodyAttributes;

        return '<div'.$attributes.'>'.$bodyContent.'</div>';
    }

    /**
     * Speichert den Studio-CSS-Export getrennt vom bereinigten HTML. Falls ein
     * älteres Projekt Style-Tags im HTML enthält, werden deren Regeln ergänzt.
     */
    public function updateCssFromData(
        ?string $exportedCss = null,
        string $embeddedCss = ''
    ): void {
        $generatedCss = $exportedCss;

        if ($generatedCss === null || trim($generatedCss) === '') {
            $generatedCss = $this->cssFromProjectData();
        }

        $cssParts = array_values(array_unique(array_filter([
            trim((string) $generatedCss),
            trim($embeddedCss),
        ], static fn (string $css): bool => $css !== '')));

        // Metadaten-Updates ohne Editor-Export dürfen bestehendes CSS nicht
        // leeren. Ein explizit übergebener leerer Export darf das dagegen.
        if ($exportedCss === null && $cssParts === []) {
            return;
        }

        $this->updateQuietly([
            'css' => $this->sanitizeCss(implode("\n", $cssParts)),
        ]);
    }

    private function cssFromProjectData(): string
    {
        $dataArray = json_decode((string) $this->data, true);

        if (! isset($dataArray['styles']) || ! is_array($dataArray['styles'])) {
            return '';
        }

        $cssRules = [];

        foreach ($dataArray['styles'] as $style) {
            if (! isset($style['selectors'], $style['style'])
                || ! is_array($style['selectors'])
                || ! is_array($style['style'])) {
                continue;
            }

            $selectors = implode(', ', array_filter(
                $style['selectors'],
                static fn ($selector): bool => is_string($selector) && $selector !== ''
            ));

            if ($selectors === '') {
                continue;
            }

            $rules = [];

            foreach ($style['style'] as $property => $value) {
                if (! is_string($property) || ! is_scalar($value)) {
                    continue;
                }

                $rules[] = "{$property}: {$value};";
            }

            if ($rules !== []) {
                $cssRules[] = "{$selectors} { ".implode(' ', $rules).' }';
            }
        }

        return implode("\n", $cssRules);
    }

    private function sanitizeCss(string $css): string
    {
        $css = str_replace(["\0", "\r\n", "\r"], ['', "\n", "\n"], $css);
        $css = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $css) ?? '';
        $css = preg_replace('/<\/?style\b[^>]*>/i', '', $css) ?? '';
        $css = preg_replace('/<[^>]+>/s', '', $css) ?? '';
        $css = preg_replace('/expression\s*\(/i', '(', $css) ?? '';
        $css = preg_replace(
            '/url\s*\(\s*(["\']?)\s*javascript:[^)]*\)/i',
            'url()',
            $css
        ) ?? '';

        return trim($css);
    }

    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function isPublished(): bool
    {
        $now = Carbon::now();

        return $this->status === 1
            && $this->published_from
            && Carbon::parse($this->published_from)->lte($now)
            && (! $this->published_until || Carbon::parse($this->published_until)->gte($now));
    }

    public function publish(): void
    {
        $this->update([
            'status' => 1,
            'published_from' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'status' => 0,
            'published_from' => null,
            'published_until' => null,
        ]);
    }

    public function getHtml()
    {
        return $this->html;
    }

    public function getCss()
    {
        return $this->css;
    }
}
