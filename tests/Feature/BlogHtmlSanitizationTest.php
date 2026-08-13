<?php

namespace Tests\Feature;

use App\Livewire\Admin\Cms\WebContent\Blog\BlogEditCreate;
use App\Models\Post;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class BlogHtmlSanitizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('guest');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('blog_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->boolean('personal_team')->default(false);
            $table->json('rbac_permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('team_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('cover_image')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('published')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_blog_editor_sanitizes_rich_html_before_it_is_persisted(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'blog-security-owner@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => true,
            'email_verified_at' => now(),
        ]);
        $team = Team::forceCreate([
            'user_id' => $owner->id,
            'name' => 'Web Redaktion',
            'personal_team' => false,
            'rbac_permissions' => ['content.web.manage' => true],
        ]);
        $editor = User::create([
            'name' => 'Delegierte Redaktion',
            'email' => 'blog-security-editor@example.test',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => true,
            'current_team_id' => $team->id,
            'email_verified_at' => now(),
        ]);
        $editor->teams()->attach($team, ['role' => 'staff']);

        Livewire::actingAs($editor)
            ->test(BlogEditCreate::class)
            ->set('title', 'Sicher gespeicherter Beitrag')
            ->set('body', '<h2>Überschrift</h2><p onclick="alert(1)"><strong>Text</strong>'
                .'<img src=x onerror="alert(2)"><a href="javascript:alert(3)">Link</a>'
                .'<a href="https://example.com/artikel" target="_blank">Sicher</a></p><script>alert(4)</script>')
            ->set('type', 'blog')
            ->set('published', true)
            ->set('published_at', now()->format('Y-m-d H:i:s'))
            ->call('save')
            ->assertHasNoErrors();

        $body = (string) Post::firstOrFail()->body;

        $this->assertStringContainsString('<h2>Überschrift</h2>', $body);
        $this->assertStringContainsString('<strong>Text</strong>', $body);
        $this->assertStringContainsString('<a>Link</a>', $body);
        $this->assertStringContainsString('href="https://example.com/artikel" target="_blank" rel="noopener noreferrer"', $body);
        $this->assertStringNotContainsString('<img', $body);
        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('onerror', $body);
        $this->assertStringNotContainsString('javascript:', $body);
    }
}
