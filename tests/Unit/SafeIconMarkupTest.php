<?php

namespace Tests\Unit;

use App\Support\SafeIconMarkup;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeIconMarkupTest extends TestCase
{
    public function test_basic_static_svg_is_normalized_and_preserved(): void
    {
        $safe = SafeIconMarkup::svg(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">'
            .'<title>Schutz</title><g fill="none" stroke="currentColor" stroke-width="2">'
            .'<path stroke-linecap="round" d="M2 2 L22 22 Z"/></g></svg>'
        );

        $this->assertNotNull($safe);
        $this->assertStringContainsString('<svg', $safe);
        $this->assertStringContainsString('<path', $safe);
        $this->assertStringContainsString('currentColor', $safe);
        $this->assertStringNotContainsString('script', strtolower($safe));
    }

    #[DataProvider('unsafeSvgProvider')]
    public function test_active_or_ambiguous_svg_markup_is_rejected(string $payload): void
    {
        $this->assertNull(SafeIconMarkup::svg($payload));
    }

    /** @return array<string, array{string}> */
    public static function unsafeSvgProvider(): array
    {
        return [
            'script' => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            'quoted event' => ['<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><path d="M1 1"/></svg>'],
            'unquoted event' => ['<svg xmlns="http://www.w3.org/2000/svg" onload=alert(1)><path d="M1 1"/></svg>'],
            'javascript link' => ['<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><path d="M1 1"/></a></svg>'],
            'external image' => ['<svg xmlns="http://www.w3.org/2000/svg"><image href="https://evil.test/x.svg"/></svg>'],
            'foreign object' => ['<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><iframe srcdoc="x"/></foreignObject></svg>'],
            'active animation' => ['<svg xmlns="http://www.w3.org/2000/svg"><animate attributeName="href" values="x;javascript:alert(1)"/></svg>'],
            'style attribute' => ['<svg xmlns="http://www.w3.org/2000/svg"><path style="fill:url(javascript:alert(1))" d="M1 1"/></svg>'],
            'paint url' => ['<svg xmlns="http://www.w3.org/2000/svg"><path fill="url(https://evil.test/a.svg#x)" d="M1 1"/></svg>'],
            'doctype entity' => ['<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg"><title>&xxe;</title></svg>'],
            'processing instruction' => ['<?xml-stylesheet href="https://evil.test/x.css"?><svg xmlns="http://www.w3.org/2000/svg"/>'],
            'html instead of svg' => ['<img src=x onerror=alert(1)>'],
            'malformed extra root' => ['<svg xmlns="http://www.w3.org/2000/svg"/><script>alert(1)</script>'],
        ];
    }

    public function test_fontawesome_classes_are_allowlisted_as_complete_tokens(): void
    {
        $this->assertSame('fas fa-shield-alt fa-lg', SafeIconMarkup::fontAwesomeClasses('fas fa-shield-alt fa-lg'));
        $this->assertSame('far fa-circle', SafeIconMarkup::fontAwesomeClasses('far  fa-circle'));
        $this->assertNull(SafeIconMarkup::fontAwesomeClasses('fa-shield-alt'));
        $this->assertNull(SafeIconMarkup::fontAwesomeClasses('fas'));
        $this->assertNull(SafeIconMarkup::fontAwesomeClasses('fas fa-x" onmouseover="alert(1)'));
        $this->assertNull(SafeIconMarkup::fontAwesomeClasses('fas fa-x <script>'));
        $this->assertNull(SafeIconMarkup::fontAwesomeClasses('fas text-red-500 fa-shield-alt'));
    }
}

