<?php

namespace Tests\Feature\Promotion;

use App\Livewire\Admin\Config\PromotionSettings;
use App\Models\PromotionSetting;
use App\Models\Team;
use App\Models\User;
use App\Services\Promotion\PromotionSettingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PromotionSettingsUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_global_admin_can_store_and_enable_complete_promotion_settings(): void
    {
        $admin = $this->user('Admin', 'promotion-settings-admin@example.test', 'admin');

        Livewire::actingAs($admin)
            ->test(PromotionSettings::class)
            ->assertSee('Promotion-Glücksrad')
            ->assertDontSee('Google-Anmeldung')
            ->assertDontSee('Apple-Anmeldung')
            ->assertSee('Alles Notwendige wird direkt hier verwaltet')
            ->assertDontSee('.env')
            ->assertDontSee('Command')
            ->assertDontSee('Hintergrundjob')
            ->assertDontSee('Kontroll-E-Mail')
            ->assertDontSee('Zugriffskontexte aufbewahren')
            ->assertSeeHtml('id="promotion-settings-modal"')
            ->assertSet('showSettingsModal', false)
            ->call('openSettingsModal')
            ->assertSet('showSettingsModal', true)
            ->set('redemptionBaseUrl', 'https://promotion.example.test/')
            ->set('qrTtlMinutes', 30)
            ->set('enabled', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('effectiveEnabled', true)
            ->assertSet('isConfigured', true)
            ->assertSet('auditKeyConfigured', true)
            ->assertSet('showSettingsModal', false)
            ->assertSee('Aktiv und freigegeben')
            ->assertSee('/gluecksrad');

        $stored = PromotionSetting::query()->findOrFail(1);
        $this->assertTrue($stored->enabled);
        $this->assertSame('https://promotion.example.test', $stored->redemption_base_url);
        $this->assertSame(30, $stored->qr_ttl_minutes);
        $this->assertSame(32, strlen(base64_decode(Crypt::decryptString($stored->getRawOriginal('audit_secret_encrypted')), true)));
        $this->assertTrue(app(PromotionSettingsService::class)->isEnabled());
        $this->assertDatabaseCount('promotion_settings', 1);
    }

    public function test_social_login_has_its_own_admin_settings_tab_outside_promotion(): void
    {
        $adminConfig = file_get_contents(resource_path('views/livewire/admin-config.blade.php'));
        $promotionSettings = file_get_contents(resource_path('views/livewire/admin/config/promotion-settings.blade.php'));

        $this->assertIsString($adminConfig);
        $this->assertIsString($promotionSettings);
        $this->assertStringContainsString("activeTab === 'social-login'", $adminConfig);
        $this->assertStringContainsString("@livewire('admin.config.social-auth-settings')", $adminConfig);
        $this->assertStringContainsString('Social-Login-Einstellungen', $adminConfig);
        $this->assertStringNotContainsString('admin.config.social-auth-settings', $promotionSettings);
    }

    public function test_delegated_settings_manager_cannot_mount_component_or_see_section(): void
    {
        $owner = $this->user('Owner', 'promotion-owner@example.test', 'admin');
        $team = new Team;
        $team->forceFill([
            'user_id' => $owner->id,
            'name' => 'Allgemeine Einstellungen',
            'personal_team' => false,
            'rbac_permissions' => ['settings.manage' => true],
        ])->save();

        $staff = $this->user('Settings Staff', 'promotion-settings-staff@example.test', 'staff');
        $staff->forceFill(['current_team_id' => $team->id])->save();
        $team->users()->attach($staff->id, ['role' => 'team_access']);

        $this->actingAs($staff)
            ->get(route('admin.config'))
            ->assertForbidden()
            ->assertDontSee('Promotion-Glücksrad')
            ->assertDontSee('Promotion-Einstellungen');

        Livewire::actingAs($staff)
            ->test(PromotionSettings::class)
            ->assertForbidden();
    }

    public function test_inactive_admin_cannot_reuse_existing_settings_snapshot(): void
    {
        $admin = $this->user('Admin', 'promotion-inactive-admin@example.test', 'admin');
        $component = Livewire::actingAs($admin)
            ->test(PromotionSettings::class)
            ->assertOk();

        $admin->forceFill(['status' => false])->save();
        auth()->setUser($admin);

        $component->set('redemptionBaseUrl', 'https://attacker.example.test')->assertForbidden();

        $this->assertNull(PromotionSetting::query()->find(1));
    }

    public function test_secret_never_appears_in_livewire_html_or_snapshot(): void
    {
        app(PromotionSettingsService::class)->save([
            'enabled' => true,
            'redemption_base_url' => 'https://promotion.example.test',
            'qr_ttl_minutes' => 30,
        ]);
        $stored = PromotionSetting::query()->findOrFail(1);
        $encrypted = (string) $stored->getRawOriginal('audit_secret_encrypted');
        $encodedSecret = Crypt::decryptString($encrypted);
        $admin = $this->user('Admin', 'promotion-secret-admin@example.test', 'admin');

        $component = Livewire::actingAs($admin)
            ->test(PromotionSettings::class);

        $serialized = json_encode([
            'html' => $component->html(),
            'snapshot' => $component->snapshot,
        ], JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString($encodedSecret, $serialized);
        $this->assertStringNotContainsString($encrypted, $serialized);
        $this->assertStringNotContainsString('audit_secret_encrypted', $serialized);
        $this->assertStringNotContainsString('audit_key', $serialized);
        $this->assertStringNotContainsString('auditEmail', $serialized);
        $this->assertStringNotContainsString('accessContextRetentionMonths', $serialized);
    }

    public function test_ui_rejects_insecure_remote_http_and_invalid_limits(): void
    {
        $admin = $this->user('Admin', 'promotion-invalid-admin@example.test', 'admin');

        Livewire::actingAs($admin)
            ->test(PromotionSettings::class)
            ->call('openSettingsModal')
            ->set('redemptionBaseUrl', 'http://promotion.example.test')
            ->set('qrTtlMinutes', 4)
            ->set('enabled', true)
            ->call('save')
            ->assertHasErrors([
                'redemptionBaseUrl',
                'qrTtlMinutes',
            ])
            ->assertSet('showSettingsModal', true);

        $this->assertNull(PromotionSetting::query()->find(1));
    }

    public function test_ui_allows_localhost_http_outside_production(): void
    {
        $admin = $this->user('Admin', 'promotion-local-admin@example.test', 'admin');

        Livewire::actingAs($admin)
            ->test(PromotionSettings::class)
            ->set('redemptionBaseUrl', 'http://127.0.0.1:8000/')
            ->set('qrTtlMinutes', 15)
            ->set('enabled', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('effectiveEnabled', true);

        $this->assertSame('http://127.0.0.1:8000', PromotionSetting::query()->findOrFail(1)->redemption_base_url);
    }

    public function test_ui_requires_https_even_for_localhost_in_production(): void
    {
        $this->app['env'] = 'production';
        $admin = $this->user('Admin', 'promotion-production-admin@example.test', 'admin');

        Livewire::actingAs($admin)
            ->test(PromotionSettings::class)
            ->set('redemptionBaseUrl', 'http://localhost:8000')
            ->set('qrTtlMinutes', 30)
            ->set('enabled', true)
            ->call('save')
            ->assertHasErrors(['redemptionBaseUrl']);

        $this->assertNull(PromotionSetting::query()->find(1));
    }

    private function user(string $name, string $email, string $role): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'status' => true,
            'email_verified_at' => now(),
        ]);
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
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->boolean('personal_team');
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

        Schema::create('promotion_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('enabled')->default(false);
            $table->string('redemption_base_url', 2048)->nullable();
            $table->unsignedSmallInteger('qr_ttl_minutes')->default(30);
            $table->text('audit_secret_encrypted');
            $table->char('configuration_mac', 64);
            $table->timestamps();
        });

        Schema::create('social_auth_provider_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32)->unique();
            $table->boolean('enabled')->default(false);
            $table->string('client_id')->nullable();
            $table->text('client_secret_encrypted')->nullable();
            $table->string('redirect_uri', 2048)->nullable();
            $table->string('apple_team_id', 64)->nullable();
            $table->string('apple_key_id', 64)->nullable();
            $table->dateTime('client_secret_expires_at')->nullable();
            $table->char('configuration_mac', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('activity_log', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });
    }
}
