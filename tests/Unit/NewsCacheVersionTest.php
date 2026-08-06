<?php

namespace Tests\Unit;

use App\Http\Controllers\PagebuilderProjectController;
use App\Livewire\Admin\Cms\WebContent\News\NewsCategoryManager;
use App\Livewire\Admin\Cms\WebContent\News\NewsEditCreate;
use App\Livewire\Admin\Cms\WebContent\News\NewsList;
use App\Models\NewsCategory;
use App\Models\Post;
use App\Support\NewsCacheVersion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewsCacheVersionTest extends TestCase
{
    private const CONNECTION = 'news_cache_version_testing';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => self::CONNECTION,
            'database.connections.'.self::CONNECTION => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge(self::CONNECTION);

        Schema::connection(self::CONNECTION)->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(['key', 'type', 'value']);
        });

        Schema::connection(self::CONNECTION)->create('news_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('color')->default('#2563EB');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::connection(self::CONNECTION)->create('pagebuilder_projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->longText('data')->nullable();
            $table->longText('html')->nullable();
            $table->longText('cleaned_html')->nullable();
            $table->longText('js')->nullable();
            $table->longText('css')->nullable();
            $table->unsignedBigInteger('last_edited_by')->nullable();
            $table->longText('page')->nullable();
            $table->longText('position')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::connection(self::CONNECTION)->create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('excerpt')->nullable();
            $table->unsignedSmallInteger('reading_time_minutes')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('news_category_id')->nullable();
            $table->unsignedBigInteger('pagebuilder_project_id')->nullable();
            $table->boolean('published')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->longText('images')->nullable();
            $table->json('social_image_settings')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge(self::CONNECTION);

        parent::tearDown();
    }

    public function test_bump_persists_a_new_unique_generation_in_one_shared_setting(): void
    {
        $first = NewsCacheVersion::bump();
        $second = NewsCacheVersion::bump();

        $this->assertTrue(Str::isUuid($first));
        $this->assertTrue(Str::isUuid($second));
        $this->assertNotSame($first, $second);
        $this->assertSame(1, DB::table('settings')->count());
        $this->assertSame($second, $this->generation());
    }

    public function test_bump_and_reader_contract_select_the_latest_legacy_duplicate(): void
    {
        DB::table('settings')->insert([
            [
                'type' => NewsCacheVersion::SETTING_TYPE,
                'key' => NewsCacheVersion::SETTING_KEY,
                'value' => 'legacy-first',
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'type' => NewsCacheVersion::SETTING_TYPE,
                'key' => NewsCacheVersion::SETTING_KEY,
                'value' => 'legacy-second',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $version = NewsCacheVersion::bump();

        $this->assertSame(2, DB::table('settings')->count());
        $this->assertSame($version, $this->generation());
    }

    public function test_news_create_update_and_delete_each_bump_the_generation(): void
    {
        $create = new NewsEditCreate;
        $create->title = 'Cache-Test News';
        $create->save();

        $post = Post::where('type', 'news')->firstOrFail();
        $first = $this->generation();

        $update = new NewsEditCreate;
        $update->loadPost($post->id);
        $update->title = 'Aktualisierte Cache-Test News';
        $update->save();

        $second = $this->generation();
        $this->assertNotSame($first, $second);

        (new NewsList)->delete($post->id);

        $this->assertNotSame($second, $this->generation());
        $this->assertDatabaseMissing('posts', ['id' => $post->id], self::CONNECTION);
    }

    public function test_reading_time_is_stored_and_can_be_cleared_again(): void
    {
        $create = new NewsEditCreate;
        $create->title = 'News mit Lesezeit';
        $create->reading_time_minutes = '7';
        $create->save();

        $post = Post::where('type', 'news')->firstOrFail();
        $this->assertSame(7, $post->reading_time_minutes);

        // Beim erneuten Oeffnen muss der Wert im Formular stehen.
        $edit = new NewsEditCreate;
        $edit->loadPost($post->id);
        $this->assertSame(7, $edit->reading_time_minutes);

        // Leeren heisst: die Base schaetzt wieder selbst.
        $edit->reading_time_minutes = '';
        $edit->save();

        $this->assertNull($post->fresh()->reading_time_minutes);
    }

    public function test_reading_time_rejects_values_outside_the_allowed_range(): void
    {
        $component = new NewsEditCreate;
        $component->title = 'News mit unzulaessiger Lesezeit';
        $component->reading_time_minutes = '0';

        try {
            $component->save();
            $this->fail('Eine Lesezeit von 0 haette abgelehnt werden muessen.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('reading_time_minutes', $e->errors());
        }

        $this->assertDatabaseMissing('posts', ['title' => 'News mit unzulaessiger Lesezeit'], self::CONNECTION);
    }

    public function test_social_image_modal_stays_closed_without_a_valid_news(): void
    {
        $modal = new \App\Livewire\Admin\Cms\WebContent\News\NewsSocialImage;

        $this->assertFalse($modal->show, 'Das Modal startet geschlossen.');

        // Genau der gemeldete Fall: kein Beitrag ausgewaehlt.
        $modal->openModal(null);
        $this->assertFalse($modal->show);
        $this->assertNull($modal->postId);

        // Unbekannte ID darf ebenfalls nicht zu einem offenen Fehlerzustand fuehren.
        $modal->openModal(999999);
        $this->assertFalse($modal->show);
        $this->assertNull($modal->postId);
    }

    public function test_social_image_modal_opens_for_an_existing_news_and_closes_again(): void
    {
        $create = new NewsEditCreate;
        $create->title = 'News fuer das Social-Bild';
        $create->save();

        $post = Post::where('type', 'news')->firstOrFail();

        $modal = new \App\Livewire\Admin\Cms\WebContent\News\NewsSocialImage;

        // Livewire liefert das Payload als benannten Parameter; das Array ist der Rueckfall.
        $modal->openModal(['postId' => $post->id]);
        $this->assertTrue($modal->show);
        $this->assertSame($post->id, $modal->postId);
        $this->assertSame('News fuer das Social-Bild', $modal->title);

        $modal->closeModal();
        $this->assertFalse($modal->show);
        $this->assertNull($modal->postId);
        $this->assertSame('', $modal->title);
    }

    public function test_social_image_layout_settings_are_saved_per_news_and_format(): void
    {
        $post = Post::create([
            'type' => 'news',
            'title' => 'News mit eigener Bildgestaltung',
        ]);

        $modal = new \App\Livewire\Admin\Cms\WebContent\News\NewsSocialImage;
        $modal->openModal($post->id);

        $this->assertSame(204, $modal->layoutSettings['story']['bottom_spacing']);
        $this->assertSame(72, $modal->layoutSettings['square']['horizontal_padding']);

        $modal->layoutSettings['square']['horizontal_padding'] = 90;
        $modal->layoutSettings['square']['title_font_size'] = 88;
        $modal->updatedLayoutSettings();
        $modal->saveLayoutSettings();

        $saved = $post->fresh()->social_image_settings;

        $this->assertSame(90, $saved['square']['horizontal_padding']);
        $this->assertSame(88, $saved['square']['title_font_size']);
        $this->assertSame(72, $saved['story']['horizontal_padding']);
        $this->assertFalse($modal->layoutSettingsDirty);
        $this->assertSame('Bildeinstellungen dauerhaft gespeichert.', $modal->settingsStatus);
        $this->assertSame(1, $modal->previewRevision);
    }

    public function test_social_image_layout_rejects_values_outside_the_dropdowns(): void
    {
        $post = Post::create([
            'type' => 'news',
            'title' => 'News mit manipuliertem Bildwert',
        ]);

        $modal = new \App\Livewire\Admin\Cms\WebContent\News\NewsSocialImage;
        $modal->openModal($post->id);
        $modal->layoutSettings['story']['title_font_size'] = 999;

        try {
            $modal->saveLayoutSettings();
            $this->fail('Ein nicht angebotener Schriftwert hätte abgelehnt werden müssen.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(
                'Dieser Wert ist für die Bildeinstellung nicht erlaubt.',
                $e->errors()['layoutSettings.story.title_font_size'][0]
            );
        }

        $this->assertNull($post->fresh()->social_image_settings);
    }

    public function test_social_image_modal_renders_the_accessible_layout_dropdown(): void
    {
        $post = Post::create([
            'type' => 'news',
            'title' => 'News für die Layout-Oberfläche',
        ]);

        \Livewire\Livewire::test(\App\Livewire\Admin\Cms\WebContent\News\NewsSocialImage::class)
            ->call('openModal', $post->id)
            ->assertSee('Layout anpassen')
            ->assertSee('Titelgröße')
            ->assertSee('Unterer Freiraum')
            ->assertSee('Logogröße')
            ->assertSee('Einstellungen speichern')
            ->assertSeeHtml('aria-controls="social-image-layout-settings"')
            ->assertSeeHtml('min-h-[44px]');
    }

    public function test_category_create_update_and_delete_each_bump_the_generation(): void
    {
        $manager = new NewsCategoryManager;
        $manager->name = 'Urteile';
        $manager->save();

        $category = NewsCategory::firstOrFail();
        $first = $this->generation();

        $manager->categoryId = $category->id;
        $manager->name = 'Neue Urteile';
        $manager->save();

        $second = $this->generation();
        $this->assertNotSame($first, $second);

        $manager->delete($category->id);

        $this->assertNotSame($second, $this->generation());
        $this->assertDatabaseMissing('news_categories', ['id' => $category->id], self::CONNECTION);
    }

    public function test_visibility_toggle_bumps_the_generation_after_persisting_the_raw_value(): void
    {
        $component = new NewsList;
        $component->mount();
        $component->updatedNewsEnabled(true);

        $first = $this->generation();
        $this->assertSame('1', (string) DB::table('settings')
            ->where('type', 'webcontent')
            ->where('key', 'news_enabled')
            ->value('value'));

        $component->updatedNewsEnabled(false);

        $this->assertNotSame($first, $this->generation());
        $this->assertSame('0', (string) DB::table('settings')
            ->where('type', 'webcontent')
            ->where('key', 'news_enabled')
            ->value('value'));
    }

    public function test_pagebuilder_save_only_bumps_after_a_valid_save_for_a_linked_news_post(): void
    {
        DB::table('pagebuilder_projects')->insert([
            'id' => 10,
            'name' => 'News content',
            'data' => '{"styles":[]}',
            'html' => '<body></body>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('posts')->insert([
            'type' => 'blog',
            'title' => 'Not a news post',
            'pagebuilder_project_id' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = new PagebuilderProjectController;
        $response = $controller->save($this->pagebuilderRequest('{"styles":[]}'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($this->generation());

        DB::table('posts')->update(['type' => 'news']);

        $invalidResponse = $controller->save($this->pagebuilderRequest('not-json'));

        $this->assertSame(422, $invalidResponse->getStatusCode());
        $this->assertNull($this->generation());

        $validResponse = $controller->save($this->pagebuilderRequest(
            '{"styles":[]}',
            '.saved-pagebuilder-style { color: rgb(8 64 88); }'
        ));

        $this->assertSame(200, $validResponse->getStatusCode());
        $this->assertTrue(Str::isUuid((string) $this->generation()));
        $this->assertSame(
            '.saved-pagebuilder-style { color: rgb(8 64 88); }',
            (string) DB::table('pagebuilder_projects')->where('id', 10)->value('css')
        );
    }

    public function test_pagebuilder_save_cleans_and_stores_html_css_and_js_separately(): void
    {
        DB::table('pagebuilder_projects')->insert([
            'id' => 10,
            'name' => 'Cleaner contract',
            'data' => '{"styles":[]}',
            'html' => '<body></body>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $html = <<<'HTML'
            <!DOCTYPE html>
            <html>
                <head><style>.head-editor-style { display: grid; }</style></head>
                <body id="editor-body">
                    <section style="color: #142536">Cleaned content marker</section>
                    <style>.embedded-editor-style { padding: 12px; }</style>
                    <script>window.pagebuilderCleanerMarker = true;</script>
                </body>
            </html>
            HTML;

        $css = <<<'CSS'
            #studio-editor-style { color: #084058; }
            .unsafe { background: url("javascript:alert(1)"); }
            </style><script>window.cssInjection = true;</script><style>
            CSS;

        $response = (new PagebuilderProjectController)->save(
            $this->pagebuilderRequest('{"styles":[]}', $css, $html)
        );

        $this->assertSame(200, $response->getStatusCode());

        $project = DB::table('pagebuilder_projects')->where('id', 10)->first();

        $this->assertStringContainsString('Cleaned content marker', $project->cleaned_html);
        $this->assertStringContainsString('style="color: #142536"', $project->cleaned_html);
        $this->assertStringNotContainsString('<html', $project->cleaned_html);
        $this->assertStringNotContainsString('<body', $project->cleaned_html);
        $this->assertStringNotContainsString('<style', $project->cleaned_html);
        $this->assertStringNotContainsString('<script', $project->cleaned_html);
        $this->assertStringContainsString('window.pagebuilderCleanerMarker = true;', $project->js);
        $this->assertStringContainsString('#studio-editor-style', $project->css);
        $this->assertStringContainsString('.head-editor-style', $project->css);
        $this->assertStringContainsString('.embedded-editor-style', $project->css);
        $this->assertStringNotContainsString('<style', $project->css);
        $this->assertStringNotContainsString('<script', $project->css);
        $this->assertStringNotContainsString('javascript:', $project->css);
        $this->assertStringNotContainsString('window.cssInjection', $project->css);
    }

    public function test_empty_editor_autosave_cannot_overwrite_existing_content(): void
    {
        DB::table('pagebuilder_projects')->insert([
            'id' => 10,
            'name' => 'Protected content',
            'data' => '{"styles":[]}',
            'html' => '<body><p>Existing raw content</p></body>',
            'cleaned_html' => '<div><p>Existing cleaned content</p></div>',
            'css' => '#existing { color: #084058; }',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $emptyData = '{"styles":[],"pages":[{"frames":[{"component":{"components":[]}}]}]}';
        $emptyHtml = '<!DOCTYPE html><html><head></head><body id="itix"></body></html>';
        $response = (new PagebuilderProjectController)->save(
            $this->pagebuilderRequest($emptyData, '', $emptyHtml)
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            '<div><p>Existing cleaned content</p></div>',
            DB::table('pagebuilder_projects')->where('id', 10)->value('cleaned_html')
        );
        $this->assertSame(
            '#existing { color: #084058; }',
            DB::table('pagebuilder_projects')->where('id', 10)->value('css')
        );
    }

    private function pagebuilderRequest(
        string $data,
        ?string $css = null,
        string $html = '<body><section>Inhalt</section></body>'
    ): Request {
        $payload = [
            'id' => 10,
            'data' => $data,
            'html' => $html,
        ];

        if ($css !== null) {
            $payload['css'] = $css;
        }

        return Request::create('/admin/pagebuilder/save', 'POST', $payload);
    }

    private function generation(): ?string
    {
        $value = DB::table('settings')
            ->where('type', NewsCacheVersion::SETTING_TYPE)
            ->where('key', NewsCacheVersion::SETTING_KEY)
            ->latest('updated_at')
            ->latest('id')
            ->value('value');

        return $value === null ? null : (string) $value;
    }
}
