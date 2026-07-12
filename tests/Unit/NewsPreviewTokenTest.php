<?php

namespace Tests\Unit;

use App\Http\Middleware\MaintainNewsPreviewCookie;
use App\Models\User;
use App\Support\NewsPreviewToken;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class NewsPreviewTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'news-preview.shared_secret' => 'unit-test-shared-secret',
            'news-preview.cookie_domain' => '.regulierungs-check.de',
            'news-preview.ttl_minutes' => 30,
        ]);
    }

    public function test_token_contains_signed_admin_and_session_proof(): void
    {
        $user = $this->adminUser();
        $token = NewsPreviewToken::issue($user, 'admin-session-id', 1_700_000_000);

        $this->assertNotNull($token);
        [$encodedPayload, $encodedSignature] = explode('.', $token, 2);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true, flags: JSON_THROW_ON_ERROR);
        $signature = $this->base64UrlDecode($encodedSignature);

        $this->assertSame(42, $payload['admin_user_id']);
        $this->assertSame('admin-session-id', $payload['admin_session_id']);
        $this->assertSame(1_700_000_000, $payload['issued']);
        $this->assertSame(1_700_001_800, $payload['expires']);
        $this->assertTrue(hash_equals(
            hash_hmac('sha256', $encodedPayload, 'unit-test-shared-secret', true),
            $signature,
        ));
    }

    public function test_shared_app_key_is_an_automatic_fallback(): void
    {
        config([
            'news-preview.shared_secret' => null,
            'news-preview.allow_app_key_fallback' => true,
            'app.key' => 'base64:shared-app-key-for-preview',
        ]);

        $token = NewsPreviewToken::issue($this->adminUser(), 'admin-session-id');

        $this->assertNotNull($token);
        [$encodedPayload, $encodedSignature] = explode('.', $token, 2);
        $this->assertTrue(hash_equals(
            hash_hmac('sha256', $encodedPayload, 'base64:shared-app-key-for-preview', true),
            $this->base64UrlDecode($encodedSignature),
        ));
    }

    public function test_middleware_sets_a_raw_shared_secure_cookie_for_an_active_admin(): void
    {
        $request = Request::create('https://admin850.regulierungs-check.de/admin');
        $request->setLaravelSession($this->createSession('current-admin-session'));
        $request->setUserResolver(fn () => $this->adminUser());

        $response = (new MaintainNewsPreviewCookie())->handle(
            $request,
            fn () => new Response('ok'),
        );

        $cookie = $this->previewCookie($response);

        $this->assertSame('.regulierungs-check.de', $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isRaw());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        $this->assertStringContainsString('.', (string) $cookie->getValue());
    }

    public function test_middleware_removes_the_host_only_cookie_for_a_guest_locally(): void
    {
        $request = Request::create('http://localhost/admin');
        $request->setLaravelSession($this->createSession('guest-session'));
        $request->setUserResolver(fn () => null);

        $response = (new MaintainNewsPreviewCookie())->handle(
            $request,
            fn () => new Response('ok'),
        );

        $cookie = $this->previewCookie($response);

        $this->assertNull($cookie->getDomain());
        $this->assertFalse($cookie->isSecure());
        $this->assertSame('', $cookie->getValue());
        $this->assertTrue($cookie->isCleared());
    }

    private function adminUser(): User
    {
        $user = new User();
        $user->forceFill([
            'id' => 42,
            'role' => 'admin',
            'status' => 1,
        ]);

        return $user;
    }

    private function createSession(string $id): Store
    {
        $session = new Store('test', new ArraySessionHandler(120));
        $session->setId($id);

        return $session;
    }

    private function previewCookie(Response $response): Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'rc_news_preview') {
                return $cookie;
            }
        }

        $this->fail('The preview cookie was not present on the response.');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = (4 - strlen($value) % 4) % 4;

        return (string) base64_decode(strtr($value.str_repeat('=', $padding), '-_', '+/'), true);
    }
}
