<?php

namespace Tests\Feature\Promotion;

use App\Livewire\Admin\Config\SocialAuthSettings;
use App\Livewire\Admin\PromotionAdministration;
use App\Livewire\Admin\UserProfile;
use App\Livewire\AdminConfig;
use App\Livewire\Promotion\PromotionConsole;
use App\Mail\PromotionResultMail;
use App\Models\Customer;
use App\Models\PromotionCampaign;
use App\Models\PromotionCampaignState;
use App\Models\PromotionPrize;
use App\Models\PromotionSpinResult;
use App\Models\Sale;
use App\Models\SocialAccount;
use App\Models\SocialAuthProviderSetting;
use App\Models\Team;
use App\Models\User;
use App\Services\Promotion\PromotionAuditChain;
use App\Services\Promotion\PromotionSettingsService;
use App\Services\Promotion\PromotionTicketQrSigner;
use App\Services\Promotion\PromotionTicketService;
use App\Services\Promotion\PromotionTurnService;
use App\Services\Promotion\PromotionWinService;
use App\Services\Promotion\SocialAuthProviderSettingsService;
use App\Support\Promotion\AppleClientSecretFactory;
use App\Support\Rbac\PromotionTeamService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PromotionV2AdminFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        app(PromotionSettingsService::class)->save([
            'enabled' => true,
            'redemption_base_url' => 'https://teilnahme.example.test',
            'qr_ttl_minutes' => 30,
        ]);
    }

    public function test_social_login_renders_as_its_own_admin_settings_tab(): void
    {
        $admin = $this->user('Volladmin', 'social-settings-navigation@example.test', 'admin');

        Livewire::actingAs($admin)
            ->test(AdminConfig::class)
            ->assertSee('Promotion-Einstellungen')
            ->assertSee('Social Login')
            ->assertSee('Social-Login-Einstellungen');
    }

    public function test_scanner_flow_masks_identity_handles_mail_failure_and_blocks_exhausted_quota(): void
    {
        [$admin, $staff, $campaign, $prize, $noWin, $retry] = $this->promotionFixture();
        $participant = $this->user('Anna Teilnehmerin', 'anna.teilnehmerin@example.test');
        $ticket = app(PromotionTicketService::class)->ensureTicket($participant, $campaign);
        $turns = app(PromotionTurnService::class);
        $turn = $turns->scanTicket(app(PromotionTicketQrSigner::class)->payload($ticket), $staff);

        $this->fakeMail();
        Livewire::actingAs($staff)->test(PromotionConsole::class)
            ->assertSee('Gewinn auswählen')
            ->assertSee($prize->name)
            ->assertSee('Kein Gewinn')
            ->assertSee('Zusatzdreh')
            ->call('recordResult', $turn->id, $prize->id);

        $original = PromotionSpinResult::query()->latest('id')->firstOrFail();
        $this->assertSame('sent', $original->fresh()->mail_status->value);
        Mail::assertSent(PromotionResultMail::class, 1);

        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('transport unavailable'));
        Livewire::actingAs($staff)->test(PromotionConsole::class)
            ->call('prepareCorrection', $original->id)
            ->set('correctionPrizeId', $noWin->id)
            ->set('correctionReason', 'staff_correction')
            ->call('correctResult')
            ->assertSee('Die E-Mail ist fehlgeschlagen');

        $correction = PromotionSpinResult::query()->latest('id')->firstOrFail();
        $this->assertSame($original->id, $correction->corrects_result_id);
        $this->assertSame('failed', $correction->mail_status->value);
        $this->assertNotNull($original->fresh()->superseded_at);

        $this->fakeMail();
        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('resendMail', $correction->id)
            ->assertSee('Ergebnis-E-Mail wurde erneut versendet');
        $this->assertSame('sent', $correction->fresh()->mail_status->value);
        Mail::assertSent(PromotionResultMail::class, 1);

        $nextParticipant = $this->user('Berta Beispiel', 'berta.beispiel@example.test');
        $nextTicket = app(PromotionTicketService::class)->ensureTicket($nextParticipant, $campaign);
        $nextTurn = $turns->scanTicket($nextTicket->participation->public_id, $staff);

        $this->fakeMail();
        Livewire::actingAs($staff)->test(PromotionConsole::class)
            ->call('recordResult', $nextTurn->id, $retry->id);
        $retryResult = PromotionSpinResult::query()->latest('id')->firstOrFail();
        $this->assertFalse($retryResult->is_final);
        $this->assertSame('not_required', $retryResult->mail_status->value);
        Mail::assertNothingSent();

        Livewire::actingAs($staff)->test(PromotionConsole::class)
            ->call('recordResult', $nextTurn->id, $prize->id)
            ->assertSee('Neue Drehungen sind gesperrt')
            ->assertSee('B**** B*******')
            ->assertDontSee('berta.beispiel@example.test');
        Mail::assertSent(PromotionResultMail::class, 1);
        $this->assertSame(1, $prize->fresh()->awarded_count);

        $turn->forceFill(['completed_at' => now()->subMinutes(11)])->save();
        Livewire::actingAs($staff)->test(PromotionConsole::class)
            ->assertDontSeeHtml('wire:click="prepareCorrection('.$correction->id.')"');
    }

    public function test_admin_surfaces_show_four_campaign_areas_profile_history_and_no_promotion_audit_ui(): void
    {
        [$admin, , $campaign] = $this->promotionFixture();
        $participant = $this->user('Profil Teilnehmer', 'profil@example.test');
        $ticket = app(PromotionTicketService::class)->ensureTicket($participant, $campaign);
        Sale::query()->create([
            'customer_id' => $participant->customer->id,
            'date' => now(),
            'sale_price' => 100,
            'net_sale_price' => 84,
            'status' => 1,
        ]);
        SocialAccount::query()->create([
            'user_id' => $participant->id,
            'provider' => 'google',
            'provider_user_id' => 'google-profile-1',
            'provider_email' => $participant->email,
        ]);

        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->assertSee('Übersicht')
            ->assertSee('Kampagne')
            ->assertSee('Gewinne')
            ->assertSee('Gewinnbezeichnung')
            ->assertSee('Menge')
            ->assertDontSee('Ergebnistyp')
            ->assertDontSee('Sortierung')
            ->assertDontSeeHtml('wire:model="prizeFulfillmentMode"')
            ->assertDontSee('Radfelder')
            ->assertSee('Verlauf')
            ->assertSee('Dauerhafter Poster-Link')
            ->assertDontSee('Gewinn-QR erzeugen')
            ->assertDontSee('Auditkette');

        Livewire::actingAs($admin)->test(UserProfile::class, ['userId' => $participant->id])
            ->assertSee('Promotion-Profil')
            ->assertSee('84.00 €')
            ->assertSee($ticket->participation->public_id)
            ->assertSee('Google verknüpft');
    }

    public function test_expired_campaign_keeps_active_turn_recoverable_but_blocks_new_scans(): void
    {
        [$admin, $staff, $campaign, , $noWin] = $this->promotionFixture();
        $endsAt = now()->addMinute()->startOfMinute();

        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->set('campaignEndsAt', $endsAt->format('Y-m-d\TH:i'))
            ->call('saveCampaign')
            ->assertHasNoErrors();

        $participant = $this->user('Aktiver Teilnehmer', 'active-expired@example.test');
        $waiting = $this->user('Wartender Teilnehmer', 'waiting-expired@example.test');
        $tickets = app(PromotionTicketService::class);
        $activeTicket = $tickets->ensureTicket($participant, $campaign->fresh());
        $waitingTicket = $tickets->ensureTicket($waiting, $campaign->fresh());
        $turn = app(PromotionTurnService::class)->scanTicket($activeTicket->participation->public_id, $staff);

        $this->travelTo($endsAt->copy()->addSecond());

        $this->actingAs($staff)->get(route('promotion.console'))
            ->assertOk()
            ->assertSee('Neue Scans sind pausiert')
            ->assertSee($activeTicket->participation->public_id);

        $this->fakeMail();
        Livewire::actingAs($staff)->test(PromotionConsole::class)
            ->assertSee('Drehplatz belegt')
            ->call('scanTicket', $waitingTicket->participation->public_id)
            ->assertReturned(fn (array $response): bool => ! $response['ok'] && str_contains($response['message'], 'Neue Scans sind pausiert'))
            ->call('recordResult', $turn->id, $noWin->id)
            ->assertReturned(fn (array $response): bool => $response['ok'] && $response['final'] && $response['scan_next'] === false);

        $this->assertSame('completed', $turn->fresh()->status->value);
        Mail::assertSent(PromotionResultMail::class, 1);
    }

    public function test_disabled_promotion_keeps_active_turn_releasable_and_blocks_new_scans(): void
    {
        [, $staff, $campaign] = $this->promotionFixture();
        $participant = $this->user('Aktiver Abbruch', 'active-disabled@example.test');
        $waiting = $this->user('Wartender Abbruch', 'waiting-disabled@example.test');
        $tickets = app(PromotionTicketService::class);
        $activeTicket = $tickets->ensureTicket($participant, $campaign);
        $waitingTicket = $tickets->ensureTicket($waiting, $campaign);
        $turn = app(PromotionTurnService::class)->scanTicket($activeTicket->participation->public_id, $staff);

        app(PromotionSettingsService::class)->save([
            'enabled' => false,
            'public_campaign_id' => $campaign->id,
            'redemption_base_url' => 'https://teilnahme.example.test',
            'qr_ttl_minutes' => 30,
        ]);

        $this->actingAs($staff)->get(route('promotion.console'))
            ->assertOk()
            ->assertSee('Neue Scans sind pausiert');

        Livewire::actingAs($staff)->test(PromotionConsole::class)
            ->call('scanTicket', $waitingTicket->participation->public_id)
            ->assertReturned(fn (array $response): bool => ! $response['ok'] && str_contains($response['message'], 'Neue Scans sind pausiert'))
            ->call('releaseTurn', $turn->id)
            ->assertReturned(['ok' => true]);

        $this->assertSame('released', $turn->fresh()->status->value);
        $this->assertSame('ready', $activeTicket->fresh()->status->value);
    }

    public function test_active_turn_blocks_admin_configuration_until_release_and_edit_prize_normalizes_enum(): void
    {
        [$admin, $staff, $campaign, $prize] = $this->promotionFixture();

        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->call('editPrize', $prize->id)
            ->assertSet('prizeFulfillmentMode', PromotionPrize::FULFILLMENT_ONSITE);

        $participant = $this->user('Konfigurationssperre', 'configuration-lock@example.test');
        $ticket = app(PromotionTicketService::class)->ensureTicket($participant, $campaign);
        $turn = app(PromotionTurnService::class)->scanTicket($ticket->participation->public_id, $staff);

        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->set('campaignIsPublic', false)
            ->call('saveCampaign')
            ->assertHasErrors(['campaignIsPublic']);
        $this->assertTrue($campaign->fresh()->is_public);

        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->set('campaignIsActive', false)
            ->call('saveCampaign')
            ->assertHasErrors(['campaignIsPublic']);
        $this->assertTrue($campaign->fresh()->is_active);

        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->call('editPrize', $prize->id)
            ->set('prizeName', 'Nicht waehrend einer Drehung')
            ->call('savePrize')
            ->assertHasErrors(['prize']);
        $this->assertSame('Gewinn', $prize->fresh()->name);

        app(PromotionTurnService::class)->releaseTurn($turn, $staff);
        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->set('campaignIsPublic', false)
            ->call('saveCampaign')
            ->assertHasNoErrors();
        $this->assertFalse($campaign->fresh()->is_public);
    }

    public function test_exhausted_prize_changes_require_a_fresh_sticker_acknowledgement(): void
    {
        [$admin, , $campaign, $prize] = $this->promotionFixture();
        app(PromotionWinService::class)->issue($campaign, $prize, $admin);
        $state = PromotionCampaignState::query()->create([
            'campaign_id' => $campaign->id,
            'active_turn_id' => null,
            'sticker_required' => false,
            'sticker_acknowledged_at' => null,
            'sticker_acknowledged_by' => null,
        ]);
        $audit = app(PromotionAuditChain::class);
        $audit->appendV2($campaign, 'campaign.runtime_initialized', null, $admin, [
            'active_turn_id' => null,
            'sticker_required' => false,
        ]);
        $this->assertTrue($audit->verify($campaign));

        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->set('campaignQuotaPolicy', 'sticker_continue')
            ->call('saveCampaign')
            ->assertHasNoErrors();

        $state->refresh();
        $this->assertTrue($state->sticker_required);
        $this->assertNull($state->sticker_acknowledged_at);
        $this->assertNull($state->sticker_acknowledged_by);
        $this->assertTrue($audit->verify($campaign));

        app(PromotionTurnService::class)->acknowledgeSticker($campaign->fresh(), $admin);
        $this->assertNotNull($state->fresh()->sticker_acknowledged_at);

        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->call('editPrize', $prize->id)
            ->set('prizeName', 'Gewinn neu bezeichnet')
            ->call('savePrize')
            ->assertHasNoErrors();

        $state->refresh();
        $this->assertTrue($state->sticker_required);
        $this->assertNull($state->sticker_acknowledged_at);
        $this->assertNull($state->sticker_acknowledged_by);
        $this->assertTrue($audit->verify($campaign));
    }

    public function test_social_settings_keep_secrets_out_of_livewire_and_apple_accepts_only_p256_keys(): void
    {
        $admin = $this->user('Volladmin', 'social-admin@example.test', 'admin');
        $social = app(SocialAuthProviderSettingsService::class);
        $plainGoogleSecret = 'google-secret-that-must-never-be-rendered';
        $social->save('google', [
            'enabled' => true,
            'client_id' => 'google-client-id',
            'client_secret' => $plainGoogleSecret,
            'redirect_uri' => 'https://teilnahme.example.test/auth/google/callback',
        ], $admin);

        $encrypted = SocialAuthProviderSetting::query()->where('provider', 'google')->value('client_secret_encrypted');
        $component = Livewire::actingAs($admin)->test(SocialAuthSettings::class)
            ->assertSee('Social Login')
            ->assertSee('Google-Anmeldung')
            ->assertSee('Apple-Anmeldung');
        $serialized = json_encode(['html' => $component->html(), 'snapshot' => $component->snapshot], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($plainGoogleSecret, $serialized);
        $this->assertStringNotContainsString((string) $encrypted, $serialized);

        $this->actingAs($admin)->post(route('admin.social-auth.google.save'), [
            'google' => [
                'enabled' => '0',
                'client_id' => 'google-client-id',
                'redirect_uri' => 'https://foreign.example.test/auth/google/callback',
            ],
        ])->assertSessionHasErrors(['google.redirect_uri'], null, 'googleSocial');
        $this->assertSame(
            'https://teilnahme.example.test/auth/google/callback',
            SocialAuthProviderSetting::query()->where('provider', 'google')->value('redirect_uri'),
        );

        $validKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $this->assertNotFalse($validKey);
        openssl_pkey_export($validKey, $validPem);
        $generated = app(AppleClientSecretFactory::class)->make('service.example', 'TEAM123', 'KEY123', $validPem);
        $this->assertCount(3, explode('.', $generated['secret']));

        $this->actingAs($admin)->post(route('admin.social-auth.apple.save'), [
            'apple' => [
                'enabled' => '0',
                'client_id' => 'service.example',
                'apple_team_id' => 'TEAM123',
                'apple_key_id' => 'KEY123',
                'redirect_uri' => 'https://teilnahme.example.test/auth/apple/callback',
                'private_key' => UploadedFile::fake()->createWithContent('AuthKey_KEY123.p8', $validPem),
            ],
        ])->assertRedirect(route('admin.config').'#social-login');

        $apple = SocialAuthProviderSetting::query()->where('provider', 'apple')->firstOrFail();
        $this->assertStringNotContainsString('PRIVATE KEY', (string) $apple->getRawOriginal('client_secret_encrypted'));
        $this->assertStringStartsWith('eyJ', Crypt::decryptString((string) $apple->getRawOriginal('client_secret_encrypted')));

        $this->actingAs($admin)->post(route('admin.social-auth.apple.save'), [
            'apple' => [
                'enabled' => '0',
                'private_key' => UploadedFile::fake()->createWithContent('AuthKey_missing.p8', $validPem),
            ],
        ])->assertSessionHasErrors(['apple.client_id', 'apple.apple_team_id', 'apple.apple_key_id'], null, 'appleSocial');

        $rsa = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->assertNotFalse($rsa);
        openssl_pkey_export($rsa, $rsaPem);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('P-256');
        app(AppleClientSecretFactory::class)->make('service.example', 'TEAM123', 'KEY123', $rsaPem);
    }

    /** @return array{User, User, PromotionCampaign, PromotionPrize, PromotionPrize, PromotionPrize} */
    private function promotionFixture(): array
    {
        $admin = $this->user('Volladmin', uniqid('admin').'@example.test', 'admin');
        $team = app(PromotionTeamService::class)->ensure($admin);
        $staff = $this->user('Promotion Mitarbeiter', uniqid('staff').'@example.test', 'staff');
        $staff->forceFill(['current_team_id' => $team->id])->save();
        $team->users()->attach($staff->id, ['role' => 'team_access']);

        $campaign = PromotionCampaign::query()->create([
            'name' => 'Glücksrad V2',
            'landing_headline' => 'Dein Dreh wartet',
            'landing_text' => 'Melde dich an und zeige dein Ticket.',
            'rules_text' => 'Eine Teilnahme pro Konto.',
            'code' => 'V2-'.str()->upper(str()->random(8)),
            'quota_exhaustion_policy' => 'block',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $prize = $this->field($campaign, 'GEWINN', 'Gewinn', 'prize', 1, PromotionPrize::FULFILLMENT_ONSITE, 10);
        $noWin = $this->field($campaign, 'NIETE', 'Leider kein Gewinn', 'no_win', 0, PromotionPrize::FULFILLMENT_ONSITE, 20);
        $retry = $this->field($campaign, 'ZUSATZDREH', 'Zusatzdreh', 'retry', 0, PromotionPrize::FULFILLMENT_ONSITE, 30);
        $audit = app(PromotionAuditChain::class);
        $audit->appendConfiguration($campaign, 'campaign.configured', $audit->configurationPayload($campaign), $admin);
        $campaign = app(PromotionTicketService::class)->publishCampaign($campaign, $admin);

        return [$admin, $staff, $campaign, $prize, $noWin, $retry];
    }

    private function field(PromotionCampaign $campaign, string $code, string $name, string $outcome, int $quota, string $mode, int $sort): PromotionPrize
    {
        return PromotionPrize::query()->create([
            'campaign_id' => $campaign->id,
            'code' => $code,
            'name' => $name,
            'outcome_type' => $outcome,
            'fulfillment_mode' => $mode,
            'quota' => $quota,
            'reserved_count' => 0,
            'awarded_count' => 0,
            'is_active' => true,
            'sort_order' => $sort,
        ]);
    }

    private function user(string $name, string $email, string $role = 'guest'): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'status' => true,
            'email_verified_at' => now(),
        ]);

        if ($role === 'guest') {
            $team = Team::query()->where('name', 'Benutzer')->first();
            if (! $team) {
                $team = new Team;
                $team->forceFill([
                    'user_id' => $user->id,
                    'name' => 'Benutzer',
                    'personal_team' => false,
                    'rbac_permissions' => null,
                ])->save();
            }
            $user->forceFill(['current_team_id' => $team->id])->save();
            $team->users()->syncWithoutDetaching([$user->id => ['role' => 'guest']]);
            Customer::query()->create(['user_id' => $user->id]);
        }

        return $user;
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
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('landing_headline')->nullable();
            $table->text('landing_text')->nullable();
            $table->text('rules_text')->nullable();
            $table->string('code')->unique();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('quota_exhaustion_policy', 32)->default('block');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_public')->default(false);
            $table->unsignedTinyInteger('public_slot')->nullable()->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('prizes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->string('code');
            $table->string('name');
            $table->string('outcome_type', 32)->default('prize');
            $table->string('fulfillment_mode');
            $table->unsignedInteger('quota');
            $table->unsignedInteger('reserved_count')->default(0);
            $table->unsignedInteger('awarded_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('configuration')->nullable();
            $table->timestamps();
        });
        Schema::create('participations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('public_id')->unique();
            $table->timestamps();
            $table->unique(['campaign_id', 'user_id']);
        });
        Schema::create('wins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('prize_id');
            $table->unsignedBigInteger('participation_id')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('fulfilled_by')->nullable();
            $table->string('status');
            $table->char('token_hash', 64)->unique();
            $table->char('claim_key', 64)->nullable()->unique();
            $table->string('prize_name_snapshot');
            $table->string('fulfillment_mode_snapshot')->nullable();
            foreach (['expires_at', 'consumed_at', 'bound_at', 'confirmed_at', 'disputed_at', 'fulfilled_at', 'expired_at', 'cancelled_at'] as $column) {
                $table->dateTime($column)->nullable();
            }
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('promotion_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('participation_id')->unique();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 20);
            $table->dateTime('issued_at');
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'user_id']);
        });
        Schema::create('promotion_turns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('started_by')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->string('status', 20);
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->string('release_reason', 120)->nullable();
            $table->timestamps();
        });
        Schema::create('promotion_campaign_states', function (Blueprint $table): void {
            $table->unsignedBigInteger('campaign_id')->primary();
            $table->unsignedBigInteger('active_turn_id')->nullable()->unique();
            $table->boolean('sticker_required')->default(false);
            $table->dateTime('sticker_acknowledged_at')->nullable();
            $table->unsignedBigInteger('sticker_acknowledged_by')->nullable();
            $table->timestamps();
        });
        Schema::create('promotion_spin_results', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('turn_id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('prize_id')->nullable();
            $table->unsignedSmallInteger('sequence');
            $table->string('outcome_type_snapshot', 32);
            $table->string('label_snapshot');
            $table->string('fulfillment_mode_snapshot', 32)->nullable();
            $table->boolean('is_final')->default(false);
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->dateTime('recorded_at');
            $table->unsignedBigInteger('corrects_result_id')->nullable();
            $table->dateTime('superseded_at')->nullable();
            $table->string('correction_reason', 255)->nullable();
            $table->string('mail_status', 20)->default('not_required');
            $table->dateTime('mail_sent_at')->nullable();
            $table->dateTime('mail_failed_at')->nullable();
            $table->dateTime('mail_last_attempted_at')->nullable();
            $table->char('mail_error_digest', 64)->nullable();
            $table->unsignedBigInteger('fulfilled_by')->nullable();
            $table->dateTime('fulfilled_at')->nullable();
            $table->timestamps();
            $table->unique(['turn_id', 'sequence']);
        });
        Schema::create('promotion_audit_heads', function (Blueprint $table): void {
            $table->unsignedBigInteger('campaign_id')->primary();
            $table->unsignedBigInteger('last_sequence')->default(0);
            $table->char('last_hash', 64);
            $table->timestamps();
        });
        Schema::create('win_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('sequence');
            $table->unsignedBigInteger('win_id')->nullable();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->unsignedBigInteger('turn_id')->nullable();
            $table->unsignedBigInteger('spin_result_id')->nullable();
            $table->unsignedBigInteger('participation_id')->nullable();
            $table->char('actor_ref', 64)->nullable();
            $table->string('event_type');
            $table->json('payload');
            $table->char('previous_hash', 64);
            $table->char('event_hash', 64)->unique();
            $table->dateTime('occurred_at');
        });
        Schema::create('promotion_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('public_campaign_id')->nullable();
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
        Schema::create('social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 32);
            $table->string('provider_user_id');
            $table->string('provider_email')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
        });
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('rental_id')->nullable();
            $table->dateTime('date');
            $table->decimal('sale_price', 8, 2);
            $table->decimal('net_sale_price', 10, 2)->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_followers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('follower_id');
            $table->dateTime('date')->nullable();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
        Schema::create('liked_products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
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

    private function fakeMail(): void
    {
        $this->app->forgetInstance('mail.manager');
        Mail::clearResolvedInstance('mail.manager');
        Mail::fake();
    }
}
