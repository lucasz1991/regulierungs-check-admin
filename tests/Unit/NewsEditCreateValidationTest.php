<?php

namespace Tests\Unit;

use App\Livewire\Admin\Cms\WebContent\News\NewsEditCreate;
use App\Models\PagebuilderProject;
use App\Models\Post;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class NewsEditCreateValidationTest extends TestCase
{
    private const CONNECTION = 'news_edit_create_validation_testing';

    private string $previousDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDefaultConnection = (string) config('database.default');

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
            $table->string('type')->nullable();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::connection(self::CONNECTION)->create('news_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::connection(self::CONNECTION)->create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('news_category_id')->nullable();
            $table->boolean('published')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->longText('images')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge(self::CONNECTION);
        config(['database.default' => $this->previousDefaultConnection]);

        parent::tearDown();
    }

    public function test_required_title_uses_the_german_error_message(): void
    {
        $this->assertValidationMessage(
            new NewsEditCreate,
            'title',
            'Bitte gib einen Titel ein.'
        );
    }

    public function test_title_length_errors_use_the_german_error_messages(): void
    {
        $tooShort = new NewsEditCreate;
        $tooShort->title = 'ab';

        $this->assertValidationMessage(
            $tooShort,
            'title',
            'Der Titel muss mindestens 3 Zeichen lang sein.'
        );

        $tooLong = new NewsEditCreate;
        $tooLong->title = str_repeat('a', 256);

        $this->assertValidationMessage(
            $tooLong,
            'title',
            'Der Titel darf höchstens 255 Zeichen lang sein.'
        );
    }

    public function test_invalid_publication_date_uses_the_german_error_message(): void
    {
        $component = $this->validComponent();
        $component->published = true;
        $component->published_at = '24.07.2026 12:30';

        $this->assertValidationMessage(
            $component,
            'published_at',
            'Bitte gib ein gültiges Veröffentlichungsdatum mit Uhrzeit an.'
        );
    }

    public function test_missing_category_uses_the_german_error_message(): void
    {
        $component = $this->validComponent();
        $component->news_category_id = 999;

        $this->assertValidationMessage(
            $component,
            'news_category_id',
            'Die ausgewählte News-Kategorie ist nicht mehr vorhanden.'
        );
    }

    public function test_slug_collision_is_rejected_with_the_german_error_message(): void
    {
        $this->insertPost([
            'title' => 'Bereits vorhanden',
            'slug' => 'bereits-vorhanden',
        ]);

        $component = $this->validComponent();
        $component->title = 'Bereits vorhanden';

        $this->assertValidationMessage(
            $component,
            'title',
            'Eine Seite mit derselben URL existiert bereits. Bitte ändere den Titel.'
        );
    }

    public function test_manipulated_existing_image_path_is_rejected(): void
    {
        $postId = $this->insertPost([
            'title' => 'News mit Bild',
            'slug' => 'news-mit-bild',
            'images' => json_encode([
                [
                    'path' => 'uploads/news/original.jpg',
                    'alt' => 'Original',
                    'caption' => null,
                    'sort' => 0,
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $component = new NewsEditCreate;
        $component->loadPost($postId);
        $component->images[0]['path'] = 'uploads/news/manipuliert.jpg';

        $this->assertValidationMessage(
            $component,
            'images.0.path',
            'Ein gespeichertes Bild wurde verändert. Bitte lade das Formular neu.'
        );
    }

    public function test_non_image_upload_uses_the_german_error_message(): void
    {
        $component = $this->validComponent();
        $component->imageFiles = [
            UploadedFile::fake()->create('kein-bild.pdf', 64, 'application/pdf'),
        ];

        $this->assertValidationMessage(
            $component,
            'imageFiles.0',
            'Datei 1 muss ein gültiges Bild sein.'
        );
    }

    public function test_image_content_with_an_unsafe_extension_is_rejected(): void
    {
        $component = $this->validComponent();
        $jpeg = UploadedFile::fake()->image('bild.jpg');
        $component->imageFiles = [
            new UploadedFile(
                $jpeg->getRealPath(),
                'bild.html',
                'image/jpeg',
                UPLOAD_ERR_OK,
                true
            ),
        ];

        $this->assertValidationMessage(
            $component,
            'imageFiles.0',
            'Bild 1 muss die Dateiendung JPG, JPEG, PNG, GIF oder WebP haben.'
        );
    }

    public function test_non_string_image_metadata_is_not_normalized_past_validation(): void
    {
        $postId = $this->insertPost([
            'title' => 'News mit Bild',
            'slug' => 'news-mit-bild',
            'images' => json_encode([
                [
                    'path' => 'uploads/news/original.jpg',
                    'alt' => 'Original',
                    'caption' => null,
                    'sort' => 0,
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $component = new NewsEditCreate;
        $component->loadPost($postId);
        $component->images[0]['alt'] = ['kein', 'text'];

        $this->assertValidationMessage(
            $component,
            'images.0.alt',
            'Der Alternativtext von Bild 1 muss aus Text bestehen.'
        );
    }

    public function test_uploaded_image_paths_are_normalized_and_restricted(): void
    {
        $component = new class extends NewsEditCreate
        {
            public function normalizePathForTest(mixed $path): string
            {
                return $this->normalizeUploadedImagePath($path);
            }
        };

        $this->assertSame(
            'uploads/news/test-bild.webp',
            $component->normalizePathForTest('/uploads/news/test-bild.webp')
        );

        try {
            $component->normalizePathForTest('uploads/anderer-ordner/test-bild.webp');
            $this->fail('A path outside the news upload directory unexpectedly passed.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Die Upload-Antwort enthält einen ungültigen Bildpfad.',
                $exception->getMessage()
            );
        }

        try {
            $component->normalizePathForTest('uploads/news/test-bild.html');
            $this->fail('A non-image path extension unexpectedly passed.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Die Upload-Antwort enthält einen ungültigen Bildpfad.',
                $exception->getMessage()
            );
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Der hochgeladene Bildpfad ist zu lang.');
        $component->normalizePathForTest('uploads/news/'.str_repeat('a', 240).'.jpg');
    }

    public function test_uploaded_images_are_cleaned_up_when_persistence_fails(): void
    {
        $component = new class extends NewsEditCreate
        {
            public array $deletedPaths = [];

            protected function uploadImageViaMediaController($file): string
            {
                return 'uploads/news/test-bild.jpg';
            }

            protected function deleteImageViaMediaController(string $path): void
            {
                $this->deletedPaths[] = $path;
            }

            protected function ensurePagebuilderProject(Post $post): PagebuilderProject
            {
                throw new RuntimeException('Erzwungener Fehler nach dem News-INSERT.');
            }
        };

        $component->title = 'Gültige Test-News';
        $component->imageFiles = [
            UploadedFile::fake()->image('test-bild.jpg'),
        ];

        $component->save();

        $this->assertSame(['uploads/news/test-bild.jpg'], $component->deletedPaths);
        $this->assertSame([], $component->images);
        $this->assertSame(
            ['Die News konnte nicht gespeichert werden. Bitte versuche es erneut.'],
            $component->getErrorBag()->get('save')
        );
        $this->assertSame(0, DB::table('posts')->count());
    }

    public function test_news_modal_renders_accessible_dialog_semantics(): void
    {
        Livewire::test(NewsEditCreate::class)
            ->assertSeeHtml('role="dialog"')
            ->assertSeeHtml('aria-modal="true"')
            ->assertSeeHtml('aria-labelledby="news-edit-create-modal-title"')
            ->assertSeeHtml('id="news-edit-create-modal-title"');
    }

    private function validComponent(): NewsEditCreate
    {
        $component = new NewsEditCreate;
        $component->title = 'Gültige Test-News';

        return $component;
    }

    private function insertPost(array $overrides = []): int
    {
        return (int) DB::table('posts')->insertGetId(array_merge([
            'type' => 'news',
            'title' => 'Test-News',
            'slug' => 'test-news',
            'excerpt' => null,
            'cover_image' => null,
            'news_category_id' => null,
            'published' => false,
            'published_at' => null,
            'images' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function assertValidationMessage(
        NewsEditCreate $component,
        string $field,
        string $expectedMessage
    ): void {
        try {
            $component->save();
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            $this->assertArrayHasKey($field, $errors);
            $this->assertSame($expectedMessage, $errors[$field][0]);

            return;
        }

        $this->fail("Validation for [{$field}] unexpectedly passed.");
    }
}
