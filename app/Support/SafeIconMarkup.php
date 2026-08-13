<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final class SafeIconMarkup
{
    /** @var list<string> */
    private const SVG_ELEMENTS = [
        'svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'title', 'desc',
    ];

    /** @var list<string> */
    private const SVG_ATTRIBUTES = [
        'xmlns', 'viewBox', 'width', 'height', 'preserveAspectRatio',
        'id', 'class', 'role', 'aria-hidden', 'focusable',
        'fill', 'fill-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
        'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'clip-rule', 'opacity',
        'transform', 'vector-effect',
        'd', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'points',
    ];

    public static function forType(?string $type, ?string $value): ?string
    {
        return match ($type) {
            'svg' => self::svg($value),
            'fontawesome' => self::fontAwesomeClasses($value),
            default => null,
        };
    }

    public static function svg(?string $markup): ?string
    {
        $markup = is_string($markup) ? trim($markup) : '';

        if ($markup === '' || strlen($markup) > 20000) {
            return null;
        }

        // XML declarations, entities and processing instructions are not part
        // of an icon and increase the parser attack surface.
        if (preg_match('/<!(?:DOCTYPE|ENTITY)|<\?/i', $markup) === 1) {
            return null;
        }

        $previousErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadXML(
            $markup,
            LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING
        );
        $hasErrors = libxml_get_errors() !== [];

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $root = $document->documentElement;

        if (! $loaded || $hasErrors || ! $root instanceof DOMElement || strtolower($root->localName) !== 'svg' || $document->doctype !== null) {
            return null;
        }

        foreach ($document->childNodes as $node) {
            if ($node !== $root && ! ($node->nodeType === XML_TEXT_NODE && trim((string) $node->nodeValue) === '')) {
                return null;
            }
        }

        if (! self::validateSvgNode($root)) {
            return null;
        }

        if (! $root->hasAttribute('xmlns')) {
            $root->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        }

        $safe = $document->saveXML($root);

        return is_string($safe) && $safe !== '' ? $safe : null;
    }

    public static function fontAwesomeClasses(?string $classes): ?string
    {
        $classes = is_string($classes) ? trim($classes) : '';

        if ($classes === '' || strlen($classes) > 255) {
            return null;
        }

        $tokens = preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hasStyle = false;
        $hasIcon = false;

        foreach ($tokens as $token) {
            if (preg_match('/^fa(?:s|r|l|d|b|t|ss|sr|sl|st)?$/D', $token) === 1) {
                $hasStyle = true;
                continue;
            }

            if (preg_match('/^fa-[a-z0-9]+(?:-[a-z0-9]+)*$/D', $token) === 1) {
                $hasIcon = true;
                continue;
            }

            return null;
        }

        return $hasStyle && $hasIcon ? implode(' ', array_values(array_unique($tokens))) : null;
    }

    private static function validateSvgNode(DOMNode $node): bool
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $parentName = strtolower($node->parentNode?->localName ?? '');

            return trim((string) $node->nodeValue) === '' || in_array($parentName, ['title', 'desc'], true);
        }

        if (! $node instanceof DOMElement) {
            return false;
        }

        $element = strtolower($node->localName);

        if (! in_array($element, self::SVG_ELEMENTS, true)) {
            return false;
        }

        if (! in_array($node->namespaceURI, [null, '', 'http://www.w3.org/2000/svg'], true)) {
            return false;
        }

        foreach (iterator_to_array($node->attributes) as $attribute) {
            $name = $attribute->nodeName;

            if (str_contains($name, ':') || str_starts_with(strtolower($name), 'on')) {
                return false;
            }

            if (! in_array($name, self::SVG_ATTRIBUTES, true) || ! self::validSvgAttribute($name, $attribute->nodeValue)) {
                return false;
            }
        }

        foreach ($node->childNodes as $child) {
            if (! self::validateSvgNode($child)) {
                return false;
            }
        }

        return true;
    }

    private static function validSvgAttribute(string $name, string $value): bool
    {
        $value = trim($value);

        return match ($name) {
            'xmlns' => $value === 'http://www.w3.org/2000/svg',
            'viewBox' => self::matches('/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:[ ,]+-?(?:\d+(?:\.\d+)?|\.\d+)){3}$/D', $value),
            'width', 'height', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
            'stroke-width', 'stroke-miterlimit', 'stroke-dashoffset', 'opacity' => self::isSvgNumber($value),
            'stroke-dasharray', 'points' => self::matches('/^(?:none|[-+]?\d*\.?\d+(?:[ ,]+[-+]?\d*\.?\d+)*)$/D', $value),
            'd' => $value !== '' && strlen($value) <= 12000 && self::matches('/^[MmZzLlHhVvCcSsQqTtAa0-9.,+\-\s]+$/D', $value),
            'fill', 'stroke' => self::isSafePaint($value),
            'fill-rule', 'clip-rule' => in_array($value, ['nonzero', 'evenodd', 'inherit'], true),
            'stroke-linecap' => in_array($value, ['butt', 'round', 'square', 'inherit'], true),
            'stroke-linejoin' => in_array($value, ['miter', 'round', 'bevel', 'inherit'], true),
            'vector-effect' => in_array($value, ['none', 'non-scaling-stroke'], true),
            'transform' => self::matches('/^(?:(?:matrix|translate|scale|rotate|skewX|skewY)\(\s*[-+0-9.,\s]+\)\s*)+$/D', $value),
            'preserveAspectRatio' => self::matches('/^(?:none|x(?:Min|Mid|Max)Y(?:Min|Mid|Max)(?:\s+(?:meet|slice))?)$/D', $value),
            'id' => self::matches('/^[A-Za-z_][A-Za-z0-9_.-]{0,127}$/D', $value),
            'class' => self::matches('/^[A-Za-z0-9_-]+(?:\s+[A-Za-z0-9_-]+)*$/D', $value),
            'role' => in_array($value, ['img', 'presentation'], true),
            'aria-hidden', 'focusable' => in_array($value, ['true', 'false'], true),
            default => false,
        };
    }

    private static function isSvgNumber(string $value): bool
    {
        return self::matches('/^[-+]?(?:\d+(?:\.\d+)?|\.\d+)(?:%|px|em|rem)?$/D', $value);
    }

    private static function isSafePaint(string $value): bool
    {
        return in_array($value, ['none', 'currentColor', 'inherit', 'transparent'], true)
            || self::matches('/^#[0-9A-Fa-f]{3,8}$/D', $value)
            || self::matches('/^[A-Za-z]{1,32}$/D', $value);
    }

    private static function matches(string $pattern, string $value): bool
    {
        return preg_match($pattern, $value) === 1;
    }
}
