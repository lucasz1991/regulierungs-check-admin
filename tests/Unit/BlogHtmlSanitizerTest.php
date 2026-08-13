<?php

namespace Tests\Unit;

use App\Support\BlogHtmlSanitizer;
use PHPUnit\Framework\TestCase;

class BlogHtmlSanitizerTest extends TestCase
{
    public function test_active_content_attributes_and_unsafe_urls_are_removed(): void
    {
        $html = <<<'HTML'
<p onclick="alert(1)" style="position:fixed">Text
    <img src="x" onerror="alert(2)">
    <a href="java&#x73;cript:alert(8)" target="_blank">JS-Link</a>
    <a href="data:text/html;base64,PHNjcmlwdD4=" target="_blank">Data-Link</a>
    <a href="&#x0A;javascript:alert(9)">Steuerzeichen-Link</a>
    <a href="vbscript:msgbox(10)">VB-Link</a>
    <a href="file:///etc/passwd">Datei-Link</a>
    <a href="blob:https://evil.example/id">Blob-Link</a>
    <a href="//evil.example/path">Protokollrelativer-Link</a>
    <script>alert(3)</script><style>body{display:none}</style>
    <svg onload="alert(4)"><script>alert(5)</script></svg>
    <math><mtext onclick="alert(6)">math</mtext></math>
    <iframe srcdoc="<script>alert(7)</script>"></iframe>
    <object data="https://evil.example"></object><embed src="https://evil.example">
    <form action="https://evil.example"><input name="secret"></form>
</p>
HTML;

        $sanitized = BlogHtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('<p>Text', $sanitized);
        $this->assertStringContainsString('<a>JS-Link</a>', $sanitized);
        $this->assertStringContainsString('<a>Data-Link</a>', $sanitized);
        $this->assertStringContainsString('<a>Steuerzeichen-Link</a>', $sanitized);
        $this->assertStringContainsString('<a>VB-Link</a>', $sanitized);
        $this->assertStringContainsString('<a>Datei-Link</a>', $sanitized);
        $this->assertStringContainsString('<a>Blob-Link</a>', $sanitized);
        $this->assertStringContainsString('<a>Protokollrelativer-Link</a>', $sanitized);

        foreach (['script', 'style', 'svg', 'math', 'iframe', 'object', 'embed', 'form', 'img'] as $tag) {
            $this->assertStringNotContainsString('<'.$tag, strtolower($sanitized));
        }

        foreach (['onclick', 'onerror', 'srcdoc', 'javascript:', 'data:', 'position:fixed'] as $payload) {
            $this->assertStringNotContainsString($payload, strtolower($sanitized));
        }
    }

    public function test_editor_formatting_and_safe_links_are_preserved_with_tight_attributes(): void
    {
        $html = <<<'HTML'
<h2 class="not-allowed">Überschrift</h2>
<p><strong>Fett</strong> und <em>Kursiv</em> mit <code>Code</code>.</p>
<blockquote>Zitat</blockquote>
<ol start="3"><li value="4">Vier</li></ol>
<ul class="contains-task-list evil"><li class="task-list-item evil"><input type="checkbox" class="task-list-item-checkbox evil" checked onclick="alert(1)"> Aufgabe</li></ul>
<table style="display:none"><thead><tr><th scope="col" colspan="2">Kopf</th></tr></thead><tbody><tr><td rowspan="2">Wert</td></tr></tbody></table>
<p><a href="https://example.com/artikel" target="_blank" rel="opener evil">Extern</a> <a href="/intern" target="evil">Intern</a></p>
HTML;

        $sanitized = BlogHtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('<h2>Überschrift</h2>', $sanitized);
        $this->assertStringContainsString('<strong>Fett</strong>', $sanitized);
        $this->assertStringContainsString('<em>Kursiv</em>', $sanitized);
        $this->assertStringContainsString('<blockquote>Zitat</blockquote>', $sanitized);
        $this->assertStringContainsString('<ol start="3"><li value="4">Vier</li></ol>', $sanitized);
        $this->assertStringContainsString('class="contains-task-list"', $sanitized);
        $this->assertStringContainsString('class="task-list-item"', $sanitized);
        $this->assertStringContainsString('type="checkbox" disabled checked', $sanitized);
        $this->assertStringContainsString('<th colspan="2" scope="col">Kopf</th>', $sanitized);
        $this->assertStringContainsString('<td rowspan="2">Wert</td>', $sanitized);
        $this->assertStringContainsString('href="https://example.com/artikel" target="_blank" rel="noopener noreferrer"', $sanitized);
        $this->assertStringContainsString('<a href="/intern">Intern</a>', $sanitized);
        $this->assertStringNotContainsString('evil', $sanitized);
        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringNotContainsString('style=', $sanitized);
    }
}
