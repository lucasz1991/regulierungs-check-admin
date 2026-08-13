<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final class BlogHtmlSanitizer
{
    /** @var array<string, true> */
    private const ALLOWED_ELEMENTS = [
        'a' => true,
        'b' => true,
        'blockquote' => true,
        'br' => true,
        'code' => true,
        'del' => true,
        'em' => true,
        'h1' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'h5' => true,
        'h6' => true,
        'hr' => true,
        'i' => true,
        'input' => true,
        'kbd' => true,
        'li' => true,
        'mark' => true,
        'ol' => true,
        'p' => true,
        'pre' => true,
        's' => true,
        'span' => true,
        'strike' => true,
        'strong' => true,
        'sub' => true,
        'sup' => true,
        'table' => true,
        'tbody' => true,
        'td' => true,
        'tfoot' => true,
        'th' => true,
        'thead' => true,
        'tr' => true,
        'u' => true,
        'ul' => true,
    ];

    /** @var array<string, true> */
    private const DROP_WITH_CONTENT = [
        'applet' => true,
        'base' => true,
        'embed' => true,
        'form' => true,
        'iframe' => true,
        'img' => true,
        'link' => true,
        'math' => true,
        'meta' => true,
        'noscript' => true,
        'object' => true,
        'script' => true,
        'style' => true,
        'svg' => true,
        'template' => true,
        'xmp' => true,
    ];

    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $previousErrorMode = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8"><!DOCTYPE html><html><body>'.$html.'</body></html>',
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
            );

            if (! $loaded) {
                return '';
            }

            $body = $document->getElementsByTagName('body')->item(0);

            if (! $body instanceof DOMElement) {
                return '';
            }

            self::sanitizeChildren($body);

            $sanitized = '';
            foreach (iterator_to_array($body->childNodes) as $child) {
                $sanitized .= $document->saveHTML($child) ?: '';
            }

            return trim($sanitized);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        for ($child = $parent->firstChild; $child !== null; $child = $next) {
            $next = $child->nextSibling;

            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $parent->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                if ($child->nodeType !== XML_TEXT_NODE) {
                    $parent->removeChild($child);
                }

                continue;
            }

            $tag = strtolower($child->tagName);

            if (isset(self::DROP_WITH_CONTENT[$tag])) {
                $parent->removeChild($child);

                continue;
            }

            self::sanitizeChildren($child);

            if (! isset(self::ALLOWED_ELEMENTS[$tag])) {
                while ($child->firstChild !== null) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);

                continue;
            }

            if (! self::sanitizeAttributes($child, $tag)) {
                $parent->removeChild($child);
            }
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): bool
    {
        $attributes = [];
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $attributes[strtolower($attribute->name)] = $attribute->value;
            $element->removeAttributeNode($attribute);
        }

        if ($tag === 'a') {
            $href = self::sanitizeUrl($attributes['href'] ?? null);

            if ($href !== null) {
                $element->setAttribute('href', $href);
            }

            if (isset($attributes['title'])) {
                $title = self::plainAttribute($attributes['title'], 300);
                if ($title !== '') {
                    $element->setAttribute('title', $title);
                }
            }

            $opensNewWindow = ($attributes['target'] ?? '') === '_blank' && $href !== null;
            if ($opensNewWindow) {
                $element->setAttribute('target', '_blank');
            }

            if ($href !== null && ($opensNewWindow || preg_match('/^https?:\/\//i', $href) === 1)) {
                $element->setAttribute('rel', 'noopener noreferrer');
            }

            return true;
        }

        if ($tag === 'input') {
            if (strtolower(trim($attributes['type'] ?? '')) !== 'checkbox') {
                return false;
            }

            $element->setAttribute('type', 'checkbox');
            $element->setAttribute('disabled', 'disabled');

            if (array_key_exists('checked', $attributes)) {
                $element->setAttribute('checked', 'checked');
            }

            self::setKnownClasses($element, $attributes['class'] ?? '', ['task-list-item-checkbox']);

            return true;
        }

        if ($tag === 'ul') {
            self::setKnownClasses($element, $attributes['class'] ?? '', ['contains-task-list']);
        } elseif ($tag === 'li') {
            self::setKnownClasses($element, $attributes['class'] ?? '', ['task-list-item']);
            self::setIntegerAttribute($element, 'value', $attributes['value'] ?? null, -1000000, 1000000);
        } elseif ($tag === 'ol') {
            self::setIntegerAttribute($element, 'start', $attributes['start'] ?? null, -1000000, 1000000);
        } elseif ($tag === 'td' || $tag === 'th') {
            self::setIntegerAttribute($element, 'colspan', $attributes['colspan'] ?? null, 1, 100);
            self::setIntegerAttribute($element, 'rowspan', $attributes['rowspan'] ?? null, 1, 100);

            if ($tag === 'th' && in_array($attributes['scope'] ?? '', ['col', 'colgroup', 'row', 'rowgroup'], true)) {
                $element->setAttribute('scope', $attributes['scope']);
            }
        }

        return true;
    }

    private static function sanitizeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $decoded = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($decoded === '' || str_contains($decoded, '\\')) {
            return null;
        }

        $compact = preg_replace('/[\x00-\x20\x7F]+/u', '', $decoded) ?? '';
        if ($compact === '') {
            return null;
        }

        if (str_starts_with($compact, '#') || str_starts_with($compact, '?')) {
            return $compact;
        }

        if (str_starts_with($compact, '/')) {
            return str_starts_with($compact, '//') ? null : $compact;
        }

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $compact, $matches) === 1) {
            $scheme = strtolower($matches[1]);

            if (in_array($scheme, ['http', 'https'], true)) {
                return filter_var($compact, FILTER_VALIDATE_URL) !== false ? $compact : null;
            }

            if ($scheme === 'mailto') {
                $address = substr($compact, 7);

                return $address !== '' && ! str_contains($address, ':') ? $compact : null;
            }

            if ($scheme === 'tel') {
                return preg_match('/^tel:\+?[0-9().-]+$/i', $compact) === 1 ? $compact : null;
            }

            return null;
        }

        return $compact;
    }

    /** @param list<string> $allowed */
    private static function setKnownClasses(DOMElement $element, string $classes, array $allowed): void
    {
        $tokens = preg_split('/\s+/', trim($classes), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $safe = array_values(array_intersect($tokens, $allowed));

        if ($safe !== []) {
            $element->setAttribute('class', implode(' ', array_unique($safe)));
        }
    }

    private static function setIntegerAttribute(
        DOMElement $element,
        string $name,
        ?string $value,
        int $minimum,
        int $maximum
    ): void {
        if ($value === null || preg_match('/^-?\d+$/', $value) !== 1) {
            return;
        }

        $integer = (int) $value;
        if ($integer >= $minimum && $integer <= $maximum) {
            $element->setAttribute($name, (string) $integer);
        }
    }

    private static function plainAttribute(string $value, int $maximumLength): string
    {
        $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', '', $value) ?? '');

        return mb_substr($value, 0, $maximumLength);
    }
}
