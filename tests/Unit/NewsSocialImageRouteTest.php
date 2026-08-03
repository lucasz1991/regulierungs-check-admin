<?php

namespace Tests\Unit;

use App\Models\Post;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Prueft den Auslieferungsweg der Vorschau: Route, Model-Binding, Controller
 * und Antwortkopf. Die Bilderzeugung selbst deckt NewsSocialImageTest ab.
 */
class NewsSocialImageRouteTest extends TestCase
{
    private const CONNECTION = 'social_image_route_testing';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => self::CONNECTION,
            'database.connections.'.self::CONNECTION => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ],
        ]);
        DB::purge(self::CONNECTION);

        Schema::connection(self::CONNECTION)->create('posts', function (Blueprint $t): void {
            $t->id();
            $t->string('type');
            $t->string('title');
            $t->string('slug')->nullable();
            $t->text('excerpt')->nullable();
            $t->unsignedSmallInteger('reading_time_minutes')->nullable();
            $t->longText('body')->nullable();
            $t->string('cover_image')->nullable();
            $t->unsignedBigInteger('news_category_id')->nullable();
            $t->unsignedBigInteger('pagebuilder_project_id')->nullable();
            $t->boolean('published')->default(false);
            $t->dateTime('published_at')->nullable();
            $t->longText('images')->nullable();
            $t->timestamps();
        });
        Schema::connection(self::CONNECTION)->create('users', function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->string('role')->default('guest');
            $t->timestamp('email_verified_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::connection(self::CONNECTION)->create('settings', function (Blueprint $t): void {
            $t->id(); $t->string('key'); $t->longText('value')->nullable(); $t->string('type')->nullable(); $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge(self::CONNECTION);
        parent::tearDown();
    }


    /**
     * Echter Login statt Middleware-Umgehung: nur so laufen SubstituteBindings
     * und die Rollenpruefung so wie im Betrieb.
     */
    private function asAdmin(): self
    {
        $user = \App\Models\User::firstOrCreate(['email' => 'admin@example.test'], [
            'name' => 'Admin',
            'password' => bcrypt('geheim'), 'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        return $this->actingAs($user);
    }

    public function test_preview_route_streams_a_png_for_every_format(): void
    {
        $post = Post::create(['type' => 'news', 'title' => 'Routen-Test', 'slug' => 'routen-test']);

        $this->asAdmin();

        foreach (['story', 'square', 'landscape'] as $format) {
            $response = $this
                ->get(route('admin.news.social-image.preview', ['post' => $post->id, 'format' => $format]));

            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/png');

            // Bewusst kein Stream mehr: das Bild wird vollstaendig erzeugt,
            // bevor Header rausgehen - nur so kann ein Fehlschlag noch einen
            // echten Fehlerstatus liefern statt eines leeren 200er-PNG.
            $body = $response->getContent();
            $size = getimagesizefromstring($body);

            $this->assertNotFalse($size, "Format {$format} liefert kein gueltiges Bild.");
            $this->assertSame('image/png', $size['mime']);
        }
    }

    public function test_download_route_sends_an_attachment(): void
    {
        $post = Post::create(['type' => 'news', 'title' => 'Routen-Test', 'slug' => 'routen-test']);

        $response = $this->asAdmin()
            ->get(route('admin.news.social-image.download', ['post' => $post->id]));

        $response->assertOk();
        $this->assertStringStartsWith('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_non_news_posts_are_rejected(): void
    {
        $post = Post::create(['type' => 'blog', 'title' => 'Kein News-Beitrag', 'slug' => 'blog']);

        $this->asAdmin()
            ->get(route('admin.news.social-image.preview', ['post' => $post->id]))
            ->assertNotFound();
    }
}
