<?php

namespace Tests\Unit;

use App\Models\Post;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

    /** Jede Logo-Variante kommt als PNG heraus, Unsinn faellt auf den Standard. */
    public function test_logo_variants_render_and_invalid_values_fall_back(): void
    {
        Storage::fake('public');

        $post = Post::create(['type' => 'news', 'title' => 'Varianten-Test', 'slug' => 'varianten-test']);
        $this->asAdmin();

        foreach (['yellow', 'teal', 'white', 'gibt-es-nicht'] as $variant) {
            $response = $this->get(route('admin.news.social-image.preview', [
                'post' => $post->id, 'format' => 'story', 'logo' => $variant,
            ]));

            $response->assertOk();
            $size = getimagesizefromstring($response->getContent());
            $this->assertNotFalse($size, "Variante {$variant} liefert kein Bild.");
        }

        // Drei gueltige Varianten -> drei getrennte Ablagen; der ungueltige
        // Wert lief auf yellow und hat dessen Datei wiederverwendet.
        $files = Storage::disk('public')->files('news-social/'.$post->id);
        $this->assertCount(3, $files);
    }

    /** Der Wechsel der Variante darf die Ablage der anderen nicht loeschen. */
    public function test_switching_the_logo_variant_keeps_the_other_variants_file(): void
    {
        Storage::fake('public');

        $post = Post::create(['type' => 'news', 'title' => 'Wechsel-Test', 'slug' => 'wechsel-test']);
        $this->asAdmin();

        $url = fn (string $logo) => route('admin.news.social-image.preview', [
            'post' => $post->id, 'format' => 'story', 'logo' => $logo,
        ]);

        $this->get($url('yellow'))->assertOk();
        $this->get($url('teal'))->assertOk();

        $disk = Storage::disk('public');
        $dir = 'news-social/'.$post->id;
        $this->assertCount(2, $disk->files($dir));

        // Erneuter Abruf beider Varianten erzeugt nichts Neues.
        $before = $disk->files($dir);
        $this->get($url('yellow'))->assertOk();
        $this->get($url('teal'))->assertOk();
        $this->assertSame($before, $disk->files($dir));
    }

    public function test_image_is_stored_once_and_replaced_only_after_a_change(): void
    {
        Storage::fake('public');

        $post = Post::create(['type' => 'news', 'title' => 'Ablage-Test', 'slug' => 'ablage-test']);
        $this->asAdmin();

        $url = route('admin.news.social-image.preview', ['post' => $post->id, 'format' => 'story']);
        $dir = 'news-social/'.$post->id;

        $this->get($url)->assertOk();
        $first = Storage::disk('public')->files($dir);
        $this->assertCount(1, $first, 'Der erste Aufruf muss genau eine Datei ablegen.');

        // Zweiter Aufruf ohne Aenderung: dieselbe Datei, nichts Neues.
        $this->get($url)->assertOk();
        $this->assertSame($first, Storage::disk('public')->files($dir));

        // Nach einer Aenderung ersetzt der neue Stand den alten.
        sleep(1);
        $post->update(['title' => 'Ablage-Test geaendert']);

        $this->get($url)->assertOk();
        $second = Storage::disk('public')->files($dir);

        $this->assertCount(1, $second, 'Der alte Stand muss ersetzt, nicht ergaenzt werden.');
        $this->assertNotSame($first, $second, 'Nach einer Aenderung muss ein neuer Stand entstehen.');
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
