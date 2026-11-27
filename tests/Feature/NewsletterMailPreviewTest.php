<?php

namespace Tests\Feature;

use App\Http\Controllers\MediaController;
use App\Services\NewsletterMailPreview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class NewsletterMailPreviewTest extends TestCase
{
    public function test_free_newsletter_preview_has_its_own_header_and_preferences_before_signature(): void
    {
        $html = app(NewsletterMailPreview::class)->render([
            'subject' => 'November', 'header' => 'Hallo,', 'body' => 'Ein freier Text.',
            'link' => 'https://example.test/info', 'button_label' => '0',
            'newsletter' => ['kind' => 'free', 'preheader' => 'Für deinen Posteingang', 'image' => null, 'settings_url' => '#settings', 'unsubscribe_url' => '#leave'],
        ]);
        $this->assertStringContainsString('>Newsletter</td>', $html);
        $this->assertStringContainsString('>Für deinen Posteingang</div>', $html);
        $this->assertStringContainsString('>0&nbsp;', $html);
        $this->assertStringNotContainsString('width="558"', $html);
        $this->assertTrue(strpos($html, 'Ein freier Text.') < strpos($html, 'href="#settings"'));
        $this->assertTrue(strpos($html, 'href="#settings"') < strpos($html, 'Viele Grüße'));
        $this->assertTrue(strpos($html, 'href="#leave"') > strpos($html, 'Viele Grüße'));
    }

    public function test_preview_uses_base_storage_and_detects_replaced_source(): void
    {
        DB::statement('CREATE TABLE settings (id INTEGER PRIMARY KEY, key TEXT, value TEXT)');
        config(['services.newsletter.base_url' => 'http://127.0.0.1:6575', 'services.newsletter.api_key' => 'local-test-key']);
        $url = 'http://127.0.0.1:6575/api/admin/newsletter-image';
        Http::preventStrayRequests();
        Http::fake([$url => Http::response(['image' => [
            'source' => 'uploads/news/current.jpg', 'url' => 'http://127.0.0.1:6575/storage/newsletter/bilder/1.jpg',
            'width' => 940, 'height' => 470,
        ]])]);
        $api = app(MediaController::class);
        $image = $api->newsletterImage(44, 50, 44, 'uploads/news/current.jpg');
        $this->assertSame(940, $image['width']);
        Http::assertSent(fn ($request) => $request->hasHeader('X-API-KEY', 'local-test-key')
            && $request->url() === $url && $request->data() === ['post_id' => 44, 'focus_x' => 50, 'focus_y' => 44]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Hauptbild der News wurde ersetzt');
        $api->newsletterImage(44, 50, 44, 'uploads/news/previous.jpg');
    }
}
