<?php

namespace Tests\Feature\Promotion;

use App\Models\PromotionCampaign;
use App\Models\PromotionPrize;
use App\Models\PromotionWinEvent;
use App\Models\User;
use App\Services\Promotion\PromotionWinService;
use App\Services\Promotion\PromotionAuditChain;
use App\Services\Promotion\PromotionSettingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class PromotionAuditIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        app(PromotionSettingsService::class)->save([
            'enabled' => true,
            'redemption_base_url' => 'https://base.example.test',
            'qr_ttl_minutes' => 30,
        ]);
    }

    public function test_append_fails_closed_after_key_rotation_or_existing_event_tampering(): void
    {
        [$campaign, $prize, $admin] = $this->promotion();
        $service = app(PromotionWinService::class);
        $service->issue($campaign, $prize, $admin);

        $originalSecret = DB::table('promotion_settings')->value('audit_secret_encrypted');
        DB::table('promotion_settings')->where('id', 1)->update(['audit_secret_encrypted' => 'rotated-outside-supported-flow']);

        try {
            $service->issue($campaign, $prize, $admin);
            $this->fail('Ein Append mit rotiertem Audit-Schluessel wurde akzeptiert.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('konfiguriert', $exception->getMessage());
        }

        $this->assertSame(1, DB::table('wins')->count());
        $this->assertSame(2, DB::table('win_events')->count());

        DB::table('promotion_settings')->where('id', 1)->update(['audit_secret_encrypted' => $originalSecret]);
        DB::table('win_events')->where('campaign_id', $campaign->id)->update(['event_type' => 'win.tampered']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->issue($campaign, $prize, $admin);
    }

    public function test_verify_detects_direct_win_state_and_irreversible_field_manipulation(): void
    {
        [$campaign, $prize, $admin] = $this->promotion();
        $participant = User::query()->create([
            'name' => 'Teilnehmer',
            'email' => 'participant@example.test',
            'password' => Hash::make('password'),
            'role' => 'guest',
            'status' => true,
            'email_verified_at' => now(),
        ]);
        $service = app(PromotionWinService::class);
        $issued = $service->issue($campaign, $prize, $admin);
        $participation = $service->bindToken($issued->plainToken, $participant);
        $service->confirmParticipation($participation, $participant);
        $win = $issued->win->fresh();
        $otherCampaign = PromotionCampaign::query()->create([
            'name' => 'Andere Kampagne', 'code' => 'ALT26', 'is_active' => true, 'created_by' => $admin->id,
        ]);
        $otherPrize = PromotionPrize::query()->create([
            'campaign_id' => $otherCampaign->id, 'code' => 'ALT', 'name' => 'Anderer Gewinn',
            'fulfillment_mode' => 'onsite_staff', 'quota' => 10, 'is_active' => true,
        ]);

        $this->assertTrue($service->verifyAuditChain($campaign)['valid']);

        $issuedEvent = \App\Models\PromotionWinEvent::query()->where('event_type', 'win.issued')->firstOrFail();
        $boundEvent = \App\Models\PromotionWinEvent::query()->where('event_type', 'win.bound')->firstOrFail();
        $confirmedEvent = \App\Models\PromotionWinEvent::query()->where('event_type', 'win.confirmed')->firstOrFail();
        $this->assertNull(data_get($issuedEvent->payload, 'win_state.claim_key_digest'));
        $this->assertNotNull(data_get($boundEvent->payload, 'win_state.claim_key_digest'));
        $this->assertSame(
            data_get($boundEvent->payload, 'win_state.claim_key_digest'),
            data_get($confirmedEvent->payload, 'win_state.claim_key_digest'),
        );
        $this->assertSame(
            data_get($boundEvent->payload, 'participation_state'),
            data_get($confirmedEvent->payload, 'participation_state'),
        );

        foreach ([
            'campaign_id' => $otherCampaign->id,
            'prize_id' => $otherPrize->id,
            'participation_id' => null,
            'status' => 'disputed',
            'fulfilled_by' => $admin->id,
            'claim_key' => str_repeat('b', 64),
            'fulfillment_mode_snapshot' => 'onsite_staff',
            'bound_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'confirmed_at' => null,
            'disputed_at' => now()->format('Y-m-d H:i:s'),
            'fulfilled_at' => now()->format('Y-m-d H:i:s'),
            'cancelled_at' => now()->format('Y-m-d H:i:s'),
        ] as $column => $tamperedValue) {
            $original = $win->getRawOriginal($column);
            DB::table('wins')->where('id', $win->id)->update([$column => $tamperedValue]);
            $this->assertFalse($service->verifyAuditChain($campaign)['valid'], "Manipulation von {$column} blieb unentdeckt.");
            DB::table('wins')->where('id', $win->id)->update([$column => $original]);
            $this->assertTrue($service->verifyAuditChain($campaign)['valid'], "Originalzustand von {$column} war nicht mehr gueltig.");
        }

        $originalPublicId = $participation->public_id;
        DB::table('participations')->where('id', $participation->id)->update(['public_id' => 'RC-TAMPERED-2345-6789-X']);
        $this->assertFalse($service->verifyAuditChain($campaign)['valid']);
        DB::table('participations')->where('id', $participation->id)->update(['public_id' => $originalPublicId]);
        $this->assertTrue($service->verifyAuditChain($campaign)['valid']);

        DB::table('participations')->where('id', $participation->id)->update(['user_id' => $admin->id]);
        $this->assertFalse($service->verifyAuditChain($campaign)['valid']);
        DB::table('participations')->where('id', $participation->id)->update(['user_id' => $participant->id]);
        $this->assertTrue($service->verifyAuditChain($campaign)['valid']);
    }

    public function test_issue_rejects_direct_configuration_tampering_before_counter_or_win_write(): void
    {
        [$campaign, $prize, $admin] = $this->promotion();
        DB::table('prizes')->where('id', $prize->id)->update(['quota' => 99]);

        try {
            app(PromotionWinService::class)->issue($campaign, $prize, $admin);
            $this->fail('Ein Gewinn wurde trotz unauditierter Kontingentmanipulation ausgegeben.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('prize', $exception->errors());
        }

        $this->assertSame(0, DB::table('wins')->count());
        $this->assertSame(0, (int) DB::table('prizes')->where('id', $prize->id)->value('reserved_count'));
    }

    public function test_win_event_timestamps_are_transition_bound_and_remain_unchanged(): void
    {
        [$campaign, $prize, $admin] = $this->promotion();
        $participant = User::query()->create([
            'name' => 'Zeit Teilnehmer',
            'email' => 'timestamp-participant@example.test',
            'password' => Hash::make('password'),
            'role' => 'guest',
            'status' => true,
            'email_verified_at' => now(),
        ]);
        $service = app(PromotionWinService::class);
        $issued = $service->issue($campaign, $prize, $admin);
        $participation = $service->bindToken($issued->plainToken, $participant);

        $boundState = PromotionWinEvent::query()->where('event_type', 'win.bound')->firstOrFail()->payload['win_state'];
        $this->assertNotNull($boundState['bound_at']);
        $this->assertNull($boundState['confirmed_at']);
        $this->assertNull($boundState['fulfilled_at']);

        $service->confirmParticipation($participation, $participant);
        $confirmedState = PromotionWinEvent::query()->where('event_type', 'win.confirmed')->firstOrFail()->payload['win_state'];
        $this->assertSame($boundState['bound_at'], $confirmedState['bound_at']);
        $this->assertNotNull($confirmedState['confirmed_at']);
        $this->assertNull($confirmedState['fulfilled_at']);

        $service->fulfill($issued->win, $admin);
        $fulfilledState = PromotionWinEvent::query()->where('event_type', 'win.fulfilled')->firstOrFail()->payload['win_state'];
        $this->assertSame($boundState['bound_at'], $fulfilledState['bound_at']);
        $this->assertSame($confirmedState['confirmed_at'], $fulfilledState['confirmed_at']);
        $this->assertNotNull($fulfilledState['fulfilled_at']);
        $this->assertTrue($service->verifyAuditChain($campaign)['valid']);
    }

    public function test_tampered_bound_state_cannot_be_rebound_and_tampered_confirmation_cannot_be_fulfilled(): void
    {
        [$campaign, $prize, $admin] = $this->promotion();
        $participant = User::query()->create([
            'name' => 'Manipulation Teilnehmer',
            'email' => 'tamper-transition@example.test',
            'password' => Hash::make('password'),
            'role' => 'guest',
            'status' => true,
            'email_verified_at' => now(),
        ]);
        $service = app(PromotionWinService::class);
        $issued = $service->issue($campaign, $prize, $admin);
        $participation = $service->bindToken($issued->plainToken, $participant);
        $boundWin = $issued->win->fresh();

        DB::table('wins')->where('id', $boundWin->id)->update([
            'participation_id' => null,
            'claim_key' => null,
            'status' => 'issued',
            'consumed_at' => null,
            'bound_at' => null,
        ]);

        try {
            $service->bindToken($issued->plainToken, $participant);
            $this->fail('Ein manipuliert zurueckgesetzter Token wurde erneut gebunden.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('token', $exception->errors());
        }

        DB::table('wins')->where('id', $boundWin->id)->update([
            'participation_id' => $participation->id,
            'claim_key' => $boundWin->getRawOriginal('claim_key'),
            'status' => 'bound',
            'consumed_at' => $boundWin->getRawOriginal('consumed_at'),
            'bound_at' => $boundWin->getRawOriginal('bound_at'),
        ]);

        DB::table('wins')->where('id', $boundWin->id)->update([
            'bound_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ]);
        try {
            $service->confirmParticipation($participation, $participant);
            $this->fail('Ein manipulierter Bindungszeitpunkt wurde vor der Bestaetigung akzeptiert.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('participation', $exception->errors());
        }
        $this->assertSame(0, PromotionWinEvent::query()->where('event_type', 'win.confirmed')->count());

        DB::table('wins')->where('id', $boundWin->id)->update([
            'bound_at' => $boundWin->getRawOriginal('bound_at'),
        ]);
        $service->confirmParticipation($participation, $participant);
        DB::table('wins')->where('id', $boundWin->id)->update([
            'confirmed_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ]);

        try {
            $service->fulfill($boundWin, $admin);
            $this->fail('Ein manipulierter Bestaetigungszeitpunkt wurde vor der Ausgabe akzeptiert.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('win', $exception->errors());
        }
        $this->assertSame(0, PromotionWinEvent::query()->where('event_type', 'win.fulfilled')->count());
    }

    /** @return array{PromotionCampaign, PromotionPrize, User} */
    private function promotion(): array
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => true,
            'email_verified_at' => now(),
        ]);
        $campaign = PromotionCampaign::query()->create([
            'name' => 'Strassenpromotion', 'code' => 'PROMO26', 'is_active' => true, 'created_by' => $admin->id,
        ]);
        $prize = PromotionPrize::query()->create([
            'campaign_id' => $campaign->id, 'code' => 'AMAZON20', 'name' => 'Amazon 20 Euro',
            'fulfillment_mode' => 'external_admin', 'quota' => 10, 'is_active' => true,
        ]);

        $this->appendConfigurationBaseline($campaign, $admin);

        return [$campaign, $prize, $admin];
    }

    private function appendConfigurationBaseline(PromotionCampaign $campaign, User $actor): void
    {
        app(PromotionAuditChain::class)->appendConfiguration($campaign, 'campaign.configured', [
            'campaign' => [
                'id' => (int) $campaign->id,
                'code' => (string) $campaign->code,
                'name_digest' => hash('sha256', (string) $campaign->name),
                'starts_at' => $campaign->getRawOriginal('starts_at'),
                'ends_at' => $campaign->getRawOriginal('ends_at'),
                'is_active' => (bool) $campaign->is_active,
            ],
            'prizes' => $campaign->prizes()->orderBy('id')->get()->map(static fn (PromotionPrize $prize): array => [
                'id' => (int) $prize->id,
                'code' => (string) $prize->code,
                'name_digest' => hash('sha256', (string) $prize->name),
                'fulfillment_mode' => (string) $prize->getRawOriginal('fulfillment_mode'),
                'quota' => (int) $prize->quota,
                'reserved_count' => (int) $prize->reserved_count,
                'is_active' => (bool) $prize->is_active,
                'sort_order' => (int) $prize->sort_order,
                'configuration_digest' => hash('sha256', json_encode($prize->configuration, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)),
            ])->all(),
        ], $actor);
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
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('prizes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->string('code');
            $table->string('name');
            $table->string('fulfillment_mode');
            $table->unsignedInteger('quota');
            $table->unsignedInteger('reserved_count')->default(0);
            $table->boolean('is_active');
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
            $table->string('fulfillment_mode_snapshot');
            foreach (['expires_at', 'consumed_at', 'bound_at', 'confirmed_at', 'disputed_at', 'fulfilled_at', 'expired_at', 'cancelled_at'] as $column) {
                $table->dateTime($column)->nullable();
            }
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
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
            $table->boolean('enabled')->default(false);
            $table->string('redemption_base_url', 2048)->nullable();
            $table->unsignedSmallInteger('qr_ttl_minutes')->default(30);
            $table->text('audit_secret_encrypted');
            $table->char('configuration_mac', 64);
            $table->timestamps();
        });
    }
}
