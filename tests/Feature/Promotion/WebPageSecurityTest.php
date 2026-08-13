<?php

namespace Tests\Feature\Promotion;

use App\Livewire\Admin\Cms\Webpages\WebpagesList;
use App\Models\Team;
use App\Models\User;
use App\Models\WebPage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class WebPageSecurityTest extends TestCase
{
    private const SAFE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path d="M0 0L10 10"/></svg>';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_delegated_editor_cannot_read_set_or_overwrite_executable_head_fields(): void
    {
        $staff = $this->staffWithWebPermission();
        $page = $this->page([
            'custom_css' => '.trusted { color: green; }',
            'custom_js' => 'window.trusted = true;',
            'custom_meta' => json_encode([['name' => 'author', 'content' => 'Trusted']], JSON_THROW_ON_ERROR),
        ]);

        $component = Livewire::actingAs($staff)
            ->test(WebpagesList::class)
            ->assertDontSee('Benutzerdefiniertes JavaScript')
            ->call('edit', $page->id)
            ->assertSet('custom_css', '')
            ->assertSet('custom_js', '')
            ->assertSet('custom_meta', '')
            ->set('title', 'Delegiert bearbeitet')
            ->set('custom_css', 'body { display: none; }')
            ->set('custom_js', 'window.injected = true;')
            ->set('custom_meta', '[{"name":"injected","content":"yes"}]')
            ->call('save')
            ->assertHasNoErrors();

        $component->assertSet('modalOpen', false);

        $page->refresh();
        $this->assertSame('Delegiert bearbeitet', $page->title);
        $this->assertSame('.trusted { color: green; }', $page->custom_css);
        $this->assertSame('window.trusted = true;', $page->custom_js);
        $this->assertSame([['name' => 'author', 'content' => 'Trusted']], $page->custom_meta);

        Livewire::actingAs($staff)
            ->test(WebpagesList::class)
            ->call('create')
            ->set('title', 'Delegierte neue Seite')
            ->set('slug', 'delegierte-neue-seite')
            ->set('custom_css', 'body { display: none; }')
            ->set('custom_js', 'window.injected = true;')
            ->set('custom_meta', '[{"name":"injected","content":"yes"}]')
            ->call('save')
            ->assertHasNoErrors();

        $created = WebPage::where('slug', 'delegierte-neue-seite')->firstOrFail();
        $this->assertNull($created->custom_css);
        $this->assertNull($created->custom_js);
        $this->assertNull($created->custom_meta);
    }

    public function test_global_admin_can_store_head_fields_and_only_sanitized_icons(): void
    {
        $admin = $this->admin();
        $page = $this->page();

        Livewire::actingAs($admin)
            ->test(WebpagesList::class)
            ->assertSee('Benutzerdefiniertes JavaScript')
            ->call('edit', $page->id)
            ->set('custom_css', '.allowed { color: blue; }')
            ->set('custom_js', 'window.allowed = true;')
            ->set('custom_meta', '[{"name":"author","content":"Admin"}]')
            ->set('icon', self::SAFE_ICON)
            ->assertSee('M0 0L10 10', false)
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $this->assertSame('.allowed { color: blue; }', $page->custom_css);
        $this->assertSame('window.allowed = true;', $page->custom_js);
        $this->assertSame([['name' => 'author', 'content' => 'Admin']], $page->custom_meta);
        $this->assertStringContainsString('<svg', (string) $page->icon);
        $this->assertStringContainsString('M0 0L10 10', (string) $page->icon);
        $this->assertStringNotContainsString('onload', (string) $page->icon);
    }

    public function test_active_svg_and_html_title_are_rejected_without_database_changes(): void
    {
        $admin = $this->admin();
        $page = $this->page();
        $payload = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script></svg>';

        Livewire::actingAs($admin)
            ->test(WebpagesList::class)
            ->call('edit', $page->id)
            ->set('icon', $payload)
            ->assertDontSee('<script', false)
            ->assertDontSee('onload="alert', false)
            ->call('save')
            ->assertHasErrors(['icon']);

        Livewire::actingAs($admin)
            ->test(WebpagesList::class)
            ->call('edit', $page->id)
            ->set('title', '<img src=x onerror=alert(3)>')
            ->call('save')
            ->assertHasErrors(['title']);

        $page->refresh();
        $this->assertSame('Bestehende Seite', $page->title);
        $this->assertSame(self::SAFE_ICON, $page->icon);
    }

    private function admin(): User
    {
        return $this->user('admin', 'web-admin@example.test');
    }

    private function staffWithWebPermission(): User
    {
        $owner = $this->user('admin', 'web-owner@example.test');
        $team = Team::forceCreate([
            'user_id' => $owner->id,
            'name' => 'Web Redaktion',
            'personal_team' => false,
            'rbac_permissions' => ['content.web.manage' => true],
        ]);
        $staff = $this->user('staff', 'web-staff@example.test', $team->id);
        $staff->teams()->attach($team, ['role' => 'staff']);

        return $staff->fresh();
    }

    private function user(string $role, string $email, ?int $currentTeamId = null): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'status' => true,
            'email_verified_at' => now(),
            'current_team_id' => $currentTeamId,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function page(array $overrides = []): WebPage
    {
        $attributes = array_merge([
            'title' => 'Bestehende Seite',
            'slug' => 'bestehende-seite',
            'custom_css' => null,
            'custom_js' => null,
            'custom_meta' => null,
            'icon' => self::SAFE_ICON,
            'is_fixed' => false,
            'is_active' => true,
            'settings' => json_encode(['showHeader' => true, 'header_image_positioning' => 'center'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        $id = DB::table('web_pages')->insertGetId($attributes);

        return WebPage::findOrFail($id);
    }

    private function createSchema(): void
    {
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

        Schema::create('web_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('robots_meta')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->text('og_image')->nullable();
            $table->longText('custom_css')->nullable();
            $table->longText('custom_js')->nullable();
            $table->json('custom_meta')->nullable();
            $table->longText('icon')->nullable();
            $table->string('header_image')->nullable();
            $table->boolean('is_fixed')->default(false);
            $table->boolean('is_active')->default(true);
            $table->dateTime('published_from')->nullable();
            $table->dateTime('published_until')->nullable();
            $table->unsignedBigInteger('last_editor')->nullable();
            $table->string('language')->nullable();
            $table->unsignedBigInteger('pagebuilder_project')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('pagebuilder_projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->longText('data')->nullable();
            $table->longText('html')->nullable();
            $table->longText('cleaned_html')->nullable();
            $table->longText('js')->nullable();
            $table->longText('css')->nullable();
            $table->unsignedBigInteger('last_edited_by')->nullable();
            $table->json('page')->nullable();
            $table->json('position')->nullable();
            $table->string('lang')->nullable();
            $table->boolean('lock')->default(false);
            $table->dateTime('published_from')->nullable();
            $table->dateTime('published_until')->nullable();
            $table->unsignedInteger('order_id')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('type')->nullable();
            $table->boolean('is_fixed')->default(true);
            $table->timestamps();
        });
    }
}
