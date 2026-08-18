<?php

namespace Tests\Feature\Promotion;

use App\Livewire\Admin\PromotionAdministration;
use App\Livewire\Promotion\PromotionConsole;
use App\Models\Customer;
use App\Models\PromotionCampaign;
use App\Models\PromotionPrize;
use App\Models\PromotionWin;
use App\Models\PromotionWinEvent;
use App\Models\Team;
use App\Models\User;
use App\Services\Promotion\PromotionSettingsService;
use App\Services\Promotion\PromotionTicketQrSigner;
use App\Services\Promotion\PromotionTicketService;
use App\Services\Promotion\PromotionTurnService;
use App\Services\Promotion\PromotionWinService;
use App\Support\Rbac\PromotionTeamService;
use App\Support\Rbac\RbacCatalog;
use App\Support\Rbac\StaffInvitationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPromotionSecurityTest extends TestCase
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

    public function test_shared_promotion_timestamps_use_the_public_app_timezone(): void
    {
        $this->assertSame('Europe/Berlin', config('app.timezone'));
    }

    public function test_staff_permissions_require_current_team_membership_and_are_exact(): void
    {
        $admin = $this->admin();
        $team = app(PromotionTeamService::class)->ensure($admin);
        $staff = $this->staff($team, attach: false);

        $this->assertFalse($staff->hasRbacPermission('promotion.wins.record'));
        $team->users()->attach($staff->id, ['role' => 'team_access']);
        $staff->unsetRelation('teams');

        $this->assertTrue($staff->hasRbacPermission('promotion.wins.record'));
        $this->assertTrue($staff->hasRbacPermission('promotion.wins.view_all'));
        $this->assertTrue($staff->hasRbacPermission('promotion.fulfillment.onsite'));
        $this->assertFalse($staff->hasRbacPermission('promotion.fulfillment.external'));
        $this->assertFalse($staff->hasRbacPermission('roles.manage'));
        $this->assertSame(RbacCatalog::promotionTeamMatrix(), $team->fresh()->permissionMatrix());

        $guest = User::query()->create([
            'name' => 'Guest in Promotion Team',
            'email' => 'guest-promotion-team@example.test',
            'password' => Hash::make('password'),
            'role' => 'guest',
            'status' => true,
            'current_team_id' => $team->id,
        ]);
        $team->users()->attach($guest->id, ['role' => 'team_access']);
        $this->assertFalse($guest->hasRbacPermission('promotion.wins.record'));
    }

    public function test_staff_can_only_open_promotion_console_and_admin_remains_global(): void
    {
        $admin = $this->admin();
        $team = app(PromotionTeamService::class)->ensure($admin);
        $staff = $this->staff($team);
        $this->publishV2Campaign($admin);

        $this->actingAs($staff)->get('/promotion')->assertOk();
        $this->actingAs($staff)->get('/admin')->assertForbidden();
        $this->actingAs($staff)->get('/admin/employees')->assertForbidden();
        $this->assertTrue(Gate::forUser($admin)->allows('roles.manage'));
    }

    public function test_promotion_staff_cannot_open_jetstream_profile_or_api_tokens(): void
    {
        $admin = $this->admin();
        $team = app(PromotionTeamService::class)->ensure($admin);
        $staff = $this->staff($team);

        $this->actingAs($staff)->get('/user/profile')->assertForbidden();
        $this->actingAs($staff)->get('/user/api-tokens')->assertNotFound();
        $this->actingAs($staff)->put('/user/profile-information', [
            'name' => 'Escalated',
            'email' => $staff->email,
        ])->assertForbidden();
        $this->actingAs($staff)->put('/user/password', [
            'current_password' => 'password',
            'password' => 'AnotherPassword!123',
            'password_confirmation' => 'AnotherPassword!123',
        ])->assertForbidden();
    }

    public function test_invitation_page_is_script_free_and_private(): void
    {
        $admin = $this->admin();
        app(PromotionTeamService::class)->ensure($admin);
        $issued = app(StaffInvitationService::class)->issue($admin, 'private-invite@example.test');

        $this->get(route('staff-invitations.show', ['token' => $issued['token']]))
            ->assertRedirect(route('staff-invitations.accept'))
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('Content-Security-Policy');
        $this->get(route('staff-invitations.accept'))
            ->assertOk()
            ->assertDontSee('<script', false)
            ->assertDontSee('uicdn.toast.com', false)
            ->assertDontSee($issued['token']);
    }

    public function test_unverified_promoted_staff_can_receive_admin_verification_link(): void
    {
        Notification::fake();
        $staff = User::create([
            'name' => 'Unverified',
            'email' => 'unverified-staff@example.test',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => true,
            'email_verified_at' => null,
        ]);

        $staff->sendEmailVerificationNotification();

        Notification::assertSentTo($staff, \App\Notifications\CustomVerifyEmail::class);
    }

    public function test_plain_token_is_never_stored_and_duplicate_issue_respects_quota(): void
    {
        $admin = $this->admin();
        $campaign = PromotionCampaign::create([
            'name' => 'Strassenpromotion', 'code' => 'PROMO26', 'is_active' => true, 'created_by' => $admin->id,
        ]);
        $prize = $this->prize($campaign, 'AMAZON20', PromotionPrize::FULFILLMENT_EXTERNAL, 1);

        $issued = app(PromotionWinService::class)->issue($campaign, $prize, $admin);

        $this->assertNotSame($issued->plainToken, $issued->win->token_hash);
        $this->assertDatabaseHas('wins', ['id' => $issued->win->id, 'token_hash' => hash('sha256', $issued->plainToken)]);
        $this->assertDatabaseMissing('wins', ['token_hash' => $issued->plainToken]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(PromotionWinService::class)->issue($campaign, $prize, $admin);
    }

    public function test_personal_ticket_scan_lists_the_participant_masked_for_staff(): void
    {
        $admin = $this->admin();
        $team = app(PromotionTeamService::class)->ensure($admin);
        $staff = $this->staff($team);
        $participant = $this->participant($admin, [
            'name' => 'Max Mustermann', 'email' => 'max.mustermann@example.test',
        ]);
        $campaign = $this->publishV2Campaign($admin);
        $ticket = app(PromotionTicketService::class)->ensureTicket($participant, $campaign);
        $payload = app(PromotionTicketQrSigner::class)->payload($ticket);
        $this->assertStringNotContainsString($participant->name, $payload);
        $this->assertStringNotContainsString($participant->email, $payload);

        $turns = app(PromotionTurnService::class);
        $turn = $turns->scanTicket($payload, $staff);
        $field = $campaign->prizes()->where('outcome_type', 'no_win')->firstOrFail();
        $turns->recordResult($turn, $field, 'no_win', $staff);

        Livewire::actingAs($staff)->test(PromotionConsole::class)
            ->assertSee($ticket->participation->public_id)
            ->assertSee('M** M*********')
            ->assertDontSee('max.mustermann@example.test');
    }

    public function test_verified_confirmed_onsite_win_renders_fulfillment_action_without_blade_artifacts(): void
    {
        [$admin, $staff, $participant, $campaign] = $this->promotionActors();
        $prize = $this->prize($campaign, 'ONSITE', PromotionPrize::FULFILLMENT_ONSITE, 1);
        app(PromotionTicketService::class)->publishCampaign($campaign, $admin);
        $ticket = app(PromotionTicketService::class)->ensureTicket($participant, $campaign);
        $turns = app(PromotionTurnService::class);
        $turn = $turns->scanTicket(app(PromotionTicketQrSigner::class)->payload($ticket), $staff);
        $result = $turns->recordResult($turn, $prize, 'prize', $staff);
        $snapshot = (string) $result->label_snapshot;
        $renamedPrize = 'Nachtraeglich umbenannter Gewinn';
        $prize->update(['name' => $renamedPrize]);

        Livewire::actingAs($staff)
            ->test(PromotionConsole::class)
            ->assertSee($snapshot)
            ->assertSeeHtml('<p class="text-sm font-bold text-slate-900">'.$snapshot.'</p>')
            ->assertSee('Als ausgehändigt markieren')
            ->assertDontSee('@elseGesperrt');
    }

    public function test_invitation_is_hash_only_72_hours_and_acceptance_is_atomic(): void
    {
        $admin = $this->admin();
        $issued = app(StaffInvitationService::class)->issue($admin, 'staff.new@example.test', 'Promotion');
        $invitation = $issued['invitation'];
        $team = Team::query()->whereRaw('LOWER(name) = ?', ['promotion'])->sole();

        $this->assertSame(hash('sha256', $issued['token']), $invitation->token_hash);
        $this->assertNotSame($issued['token'], $invitation->token_hash);
        $this->assertTrue($invitation->expires_at->between(now()->addHours(71), now()->addHours(73)));
        $this->assertSame(RbacCatalog::promotionTeamMatrix(), $team->permissionMatrix());

        $this->get(route('staff-invitations.show', ['token' => $issued['token']]))->assertRedirect(route('staff-invitations.accept'));
        $this->post(route('staff-invitations.store'), [
            'name' => 'Neue Mitarbeiterin',
            'password' => 'Strong-password-123!',
            'password_confirmation' => 'Strong-password-123!',
        ])->assertRedirect(route('promotion.console'));

        $staff = User::query()->where('email', 'staff.new@example.test')->firstOrFail();
        $this->assertSame('staff', $staff->role);
        $this->assertTrue($staff->hasVerifiedEmail());
        $this->assertSame($team->id, $staff->current_team_id);
        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $staff->id, 'role' => 'team_access']);
        $this->assertNotNull($invitation->fresh()->accepted_at);

        auth('web')->logout();
        $this->withSession(['staff_invitation_token' => $issued['token']])->post(route('staff-invitations.store'), [
            'name' => 'Doppelt', 'password' => 'Strong-password-123!', 'password_confirmation' => 'Strong-password-123!',
        ])->assertSessionHasErrors('email');
        $this->assertSame(1, User::query()->where('email', 'staff.new@example.test')->count());
    }

    public function test_first_employee_invitation_web_action_creates_exact_promotion_team(): void
    {
        Mail::fake();
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Employees::class)
            ->assertSee('automatisch angelegt oder auf den verbindlichen Rechtestand gebracht')
            ->assertDontSee('promotion:ensure-team')
            ->set('email', 'web-invited@example.test')
            ->set('position', 'Promotion')
            ->call('invite')
            ->assertHasNoErrors();

        $team = Team::query()->whereRaw('LOWER(name) = ?', ['promotion'])->sole();
        $this->assertSame(RbacCatalog::promotionTeamMatrix(), $team->permissionMatrix());
        $this->assertDatabaseHas('staff_invitations', [
            'email' => 'web-invited@example.test',
            'team_id' => $team->id,
            'role' => 'staff',
        ]);
        Mail::assertSent(\App\Mail\StaffInvitationMail::class, 1);
    }

    public function test_only_admin_can_fulfill_external_and_onsite_is_idempotent(): void
    {
        [$admin, $staff, $participant, $campaign] = $this->promotionActors();
        $service = app(PromotionWinService::class);
        $onsite = $this->prize($campaign, 'ON', PromotionPrize::FULFILLMENT_ONSITE, 2);
        $external = $this->prize($campaign, 'EX', PromotionPrize::FULFILLMENT_EXTERNAL, 2);

        $issued = $service->issue($campaign, $onsite, $staff);
        $participation = $service->bindToken($issued->plainToken, $participant);
        $service->confirmParticipation($participation, $participant);
        $fulfilled = $service->fulfill($issued->win, $staff);
        $eventCount = $fulfilled->events()->count();
        $this->assertSame(PromotionWin::STATUS_FULFILLED, $fulfilled->status);
        $this->assertSame($fulfilled->id, $service->fulfill($fulfilled, $staff)->id);
        $this->assertSame($eventCount, $fulfilled->events()->count());

        $issuedExternal = $service->issue($campaign, $external, $staff);
        $this->assertSame(PromotionPrize::FULFILLMENT_EXTERNAL, $issuedExternal->win->fulfillment_mode_snapshot);
        // Later prize edits must never downgrade an already issued digital
        // claim to staff-controlled onsite fulfillment.
        $this->savePrizeThroughLivewire(
            $admin,
            $external,
            quota: 2,
            mode: PromotionPrize::FULFILLMENT_ONSITE,
        )->assertHasNoErrors();
        $otherParticipant = User::create([
            'name' => 'Other',
            'email' => 'other@example.test',
            'password' => Hash::make('password'),
            'role' => 'guest',
            'status' => true,
            'email_verified_at' => now(),
        ]);
        $externalParticipation = $service->bindToken($issuedExternal->plainToken, $otherParticipant);
        $service->confirmParticipation($externalParticipation, $otherParticipant);

        try {
            $service->fulfill($issuedExternal->win, $staff);
            $this->fail('Staff durfte externen Gewinn ausgeben.');
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            $this->assertTrue(true);
        }

        $this->assertSame(PromotionWin::STATUS_FULFILLED, $service->fulfill($issuedExternal->win, $admin)->status);
    }

    public function test_expired_win_can_be_cancelled_by_admin_and_audit_chain_stays_valid(): void
    {
        [$admin, $staff, , $campaign] = $this->promotionActors();
        $prize = $this->prize($campaign, 'EXP', PromotionPrize::FULFILLMENT_ONSITE, 2);
        $service = app(PromotionWinService::class);
        $issued = $service->issue($campaign, $prize, $staff);
        $this->travel(app(PromotionSettingsService::class)->qrTtlMinutes() + 1)->minutes();
        $expired = $service->expire($issued->win);
        $cancelled = $service->cancel($expired, $admin, 'expired_reservation_released');
        $cancelledAgain = $service->cancel($cancelled, $admin, 'expired_reservation_released');

        $this->assertSame(PromotionWin::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame($cancelled->id, $cancelledAgain->id);
        $this->assertSame(0, $prize->fresh()->reserved_count);
        $this->assertTrue($service->verifyAuditChain($campaign)['valid']);
    }

    public function test_named_admin_routes_have_the_expected_permission_middleware(): void
    {
        $expected = [
            'admin.index' => 'admin.dashboard.view', 'admin.config' => 'settings.manage',
            'admin.webcontentmanager' => 'content.web.manage', 'admin.webcontent.news' => 'content.news.manage',
            'admin.ratingstructure.index' => 'ratings.structure.manage', 'admin.messages' => 'messages.manage',
            'admin.tasks' => 'tasks.manage', 'admin.exports' => 'exports.manage', 'admin.users' => 'users.manage',
            'admin.safety' => 'audit.view', 'admin.employees' => 'staff.manage', 'admin.team-permissions' => 'roles.manage',
            'admin.mails' => 'mails.manage', 'admin.contacts' => 'contacts.manage',
            'admin.reviews.claim-ratings' => 'reviews.manage', 'admin.promotion' => 'promotion.campaigns.manage',
            'promotion.console' => 'promotion.wins.record',
        ];

        foreach ($expected as $routeName => $permission) {
            $middleware = app('router')->getRoutes()->getByName($routeName)->gatherMiddleware();
            $this->assertContains('can:'.$permission, $middleware, $routeName);
            $this->assertNotContains('verified', $middleware, $routeName.' must not require email verification');
        }
    }

    public function test_staff_cannot_invoke_admin_livewire_mutations(): void
    {
        $admin = $this->admin();
        $team = app(PromotionTeamService::class)->ensure($admin);
        $staff = $this->staff($team);

        Livewire::actingAs($staff)->test(\App\Livewire\Admin\TeamPermissions::class)->assertForbidden();
        Livewire::actingAs($staff)->test(\App\Livewire\Admin\Employees::class)->assertForbidden();
        Livewire::actingAs($staff)->test(\App\Livewire\Admin\PromotionAdministration::class)->assertForbidden();
    }

    public function test_web_and_news_livewire_permissions_are_isolated(): void
    {
        $admin = $this->admin();
        $newsTeam = new Team;
        $newsTeam->forceFill([
            'user_id' => $admin->id,
            'name' => 'News Redaktion',
            'personal_team' => false,
            'rbac_permissions' => ['content.news.manage' => true],
        ])->save();
        $newsUser = $this->staff($newsTeam);

        $this->actingAs($newsUser)->get(route('admin.webcontentmanager'))->assertForbidden();

        $webTeam = new Team;
        $webTeam->forceFill([
            'user_id' => $admin->id,
            'name' => 'Web Redaktion',
            'personal_team' => false,
            'rbac_permissions' => ['content.web.manage' => true],
        ])->save();
        $webUser = $this->staff($webTeam);

        // AuthenticateSession remembers the first user's password hash. Start
        // the second independent authorization request with a clean session.
        $this->flushSession();
        $this->actingAs($webUser)->get(route('admin.webcontent.news'))->assertForbidden();
        Livewire::actingAs($newsUser)
            ->test(\App\Livewire\Admin\Cms\Webpages\WebpagesList::class)
            ->assertForbidden();
        Livewire::actingAs($webUser)
            ->test(\App\Livewire\Admin\Cms\WebContent\News\NewsList::class)
            ->assertForbidden();
    }

    public function test_unverified_staff_can_use_admin_login_but_regular_user_cannot(): void
    {
        $admin = $this->admin();
        $team = app(PromotionTeamService::class)->ensure($admin);
        $staff = $this->staff($team);
        $this->publishV2Campaign($admin);
        $staff->forceFill(['email_verified_at' => null])->save();

        $this->post('/login', ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect(route('promotion.console'));
        $this->assertAuthenticatedAs($staff);
        $this->get(route('promotion.console'))->assertOk();

        auth('web')->logout();
        app('auth')->forgetGuards();
        $this->flushSession();
        $guest = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => Hash::make('password'), 'role' => 'guest', 'status' => true, 'email_verified_at' => now()]);
        $this->post('/login', ['email' => $guest->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unverified_admin_login_does_not_redirect_to_email_verification(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['email_verified_at' => null])->save();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.index'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_only_admin_can_promote_an_existing_account_and_verification_is_not_forged(): void
    {
        $admin = $this->admin();
        $existing = User::create(['name' => 'Existing', 'email' => 'existing@example.test', 'password' => Hash::make('password'), 'role' => 'guest', 'status' => true, 'email_verified_at' => null]);

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\Employees::class)
            ->set('existingEmail', $existing->email)
            ->call('promoteExisting')
            ->assertHasNoErrors();

        $existing->refresh();
        $team = Team::query()->whereRaw('LOWER(name) = ?', ['promotion'])->sole();
        $this->assertSame('staff', $existing->role);
        $this->assertSame($team->id, $existing->current_team_id);
        $this->assertSame(RbacCatalog::promotionTeamMatrix(), $team->permissionMatrix());
        $this->assertNull($existing->email_verified_at);
        $this->assertDatabaseHas('team_user', ['team_id' => $team->id, 'user_id' => $existing->id]);
    }

    public function test_new_admin_surfaces_render_for_full_admin(): void
    {
        $admin = $this->admin();
        app(PromotionTeamService::class)->ensure($admin);

        $this->actingAs($admin)->get(route('admin.employees'))->assertOk();
        $this->actingAs($admin)->get(route('admin.team-permissions'))->assertOk();
        $this->actingAs($admin)->get(route('admin.promotion'))->assertOk();
    }

    public function test_full_admin_can_prepare_campaigns_while_public_feature_is_disabled(): void
    {
        app(PromotionSettingsService::class)->save([
            'enabled' => false,
            'redemption_base_url' => 'https://base.example.test',
            'qr_ttl_minutes' => 30,
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.promotion'))->assertOk();
        $staff = $this->staff(app(PromotionTeamService::class)->ensure($admin));
        $this->flushSession();
        $this->actingAs($staff)->get(route('promotion.console'))
            ->assertOk()
            ->assertSee('Teilnehmer aufrufen');
    }

    public function test_first_invitation_creates_and_hardens_promotion_team_without_command(): void
    {
        $admin = $this->admin();

        $first = app(StaffInvitationService::class)->issue($admin, 'first-staff@example.test');
        $team = Team::query()->whereRaw('LOWER(name) = ?', ['promotion'])->sole();
        $team->forceFill(['rbac_permissions' => ['promotion.wins.record' => true]])->save();
        $second = app(StaffInvitationService::class)->issue($admin, 'second-staff@example.test');

        $this->assertSame(1, Team::query()->whereRaw('LOWER(name) = ?', ['promotion'])->count());
        $this->assertSame(RbacCatalog::promotionTeamMatrix(), $team->fresh()->permissionMatrix());
        $this->assertSame(0, $team->users()->count());
        $this->assertSame($team->id, $first['invitation']->team_id);
        $this->assertSame($team->id, $second['invitation']->team_id);
    }

    public function test_no_promotion_artisan_commands_are_registered(): void
    {
        $promotionCommands = array_filter(
            array_keys(Artisan::all()),
            static fn (string $command): bool => str_starts_with($command, 'promotion:'),
        );

        $this->assertSame([], array_values($promotionCommands));
    }

    public function test_promotion_permissions_do_not_work_from_an_unrelated_team(): void
    {
        $admin = $this->admin();
        $otherTeam = new Team;
        $otherTeam->forceFill([
            'user_id' => $admin->id,
            'name' => 'Redaktion',
            'personal_team' => false,
            'rbac_permissions' => RbacCatalog::promotionTeamMatrix(),
        ])->save();
        $staff = $this->staff($otherTeam);

        $this->assertFalse($staff->hasRbacPermission('promotion.wins.record'));
        $this->actingAs($staff)->get(route('promotion.console'))->assertForbidden();
    }

    public function test_campaign_starts_without_configurable_prizes_and_keeps_operational_results_internal(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\PromotionAdministration::class)
            ->set('campaignCode', 'STRASSE26')
            ->set('campaignName', 'Strassenpromotion 2026')
            ->set('campaignIsActive', false)
            ->call('saveCampaign')
            ->assertHasNoErrors();

        $campaign = PromotionCampaign::query()->where('code', 'STRASSE26')->firstOrFail();
        $this->assertFalse($campaign->is_active);
        $this->assertFalse($campaign->is_public);
        $this->assertCount(0, $campaign->prizes->where('outcome_type.value', 'prize'));
        $this->assertCount(1, $campaign->prizes->where('outcome_type.value', 'no_win'));
        $this->assertCount(1, $campaign->prizes->where('outcome_type.value', 'retry'));

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->set('campaignLandingHeadline', 'Drehen und gewinnen')
            ->set('campaignLandingText', 'Melde dich an und zeige dein Ticket.')
            ->set('campaignRulesText', 'Eine Teilnahme je Konto.')
            ->set('campaignIsActive', true)
            ->set('campaignIsPublic', true)
            ->call('saveCampaign')
            ->assertHasErrors(['campaignIsPublic'])
            ->assertSee('Legen Sie vor der Veröffentlichung mindestens einen Gewinn mit Menge an.');

        $this->assertFalse($campaign->fresh()->is_public);
    }

    public function test_campaign_and_prize_configuration_are_hmac_audited(): void
    {
        $admin = $this->admin();
        $component = Livewire::actingAs($admin)->test(\App\Livewire\Admin\PromotionAdministration::class)
            ->set('campaignCode', 'AUDIT26')
            ->set('campaignName', 'Audit Promotion')
            ->set('campaignIsActive', false)
            ->call('saveCampaign')
            ->assertHasNoErrors();

        $campaign = PromotionCampaign::query()->where('code', 'AUDIT26')->firstOrFail();
        $component
            ->set('prizeName', 'Frei konfigurierter Gewinn')
            ->set('prizeQuota', 5)
            ->call('savePrize')
            ->assertHasNoErrors();
        $surprise = $campaign->prizes()->where('name', 'Frei konfigurierter Gewinn')->firstOrFail();
        $this->assertSame('FREI-KONFIGURIERTER-GEWINN', $surprise->code);
        $this->assertSame('prize', $surprise->outcome_type->value);
        $this->assertSame(PromotionPrize::FULFILLMENT_ONSITE, $surprise->fulfillment_mode);
        $this->assertTrue($surprise->is_active);

        $service = app(PromotionWinService::class);
        $this->assertTrue($service->verifyAuditChain($campaign)['valid']);

        DB::table('prizes')->where('id', $surprise->id)->update(['quota' => 99]);
        $this->assertFalse($service->verifyAuditChain($campaign)['valid']);

        $eventCount = PromotionWinEvent::query()->where('campaign_id', $campaign->id)->count();

        try {
            Livewire::actingAs($admin)->test(PromotionAdministration::class)
                ->call('editCampaign', $campaign->id)
                ->set('campaignName', 'Manipulation legitimieren')
                ->call('saveCampaign');
            $this->fail('Eine manipulierte Preisquote wurde durch Speichern der Kampagne legitimiert.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('nicht verifiziert', $exception->getMessage());
        }

        $this->assertSame('Audit Promotion', $campaign->fresh()->name);
        $this->assertSame(99, $surprise->fresh()->quota);
        $this->assertSame($eventCount, PromotionWinEvent::query()->where('campaign_id', $campaign->id)->count());
        $this->assertFalse($service->verifyAuditChain($campaign)['valid']);

        try {
            Livewire::actingAs($admin)->test(PromotionAdministration::class)
                ->set('campaignId', $campaign->id)
                ->call('editPrize', $surprise->id)
                ->set('prizeName', 'Manipulation legitimieren')
                ->call('savePrize');
            $this->fail('Eine manipulierte Preisquote wurde durch erneutes Speichern des Preises legitimiert.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('nicht verifiziert', $exception->getMessage());
        }

        $this->assertSame('Frei konfigurierter Gewinn', $surprise->fresh()->name);
        $this->assertSame(99, $surprise->fresh()->quota);
        $this->assertSame($eventCount, PromotionWinEvent::query()->where('campaign_id', $campaign->id)->count());
        $this->assertFalse($service->verifyAuditChain($campaign)['valid']);
    }

    public function test_saving_a_prize_never_overwrites_a_concurrently_incremented_reservation_counter(): void
    {
        $admin = $this->admin();
        $campaign = PromotionCampaign::create(['name' => 'Promo', 'code' => 'RACE', 'is_active' => false, 'created_by' => $admin->id]);
        $prize = $this->prize($campaign, 'RACEPRIZE', PromotionPrize::FULFILLMENT_ONSITE, 5);
        $eventName = 'eloquent.retrieved: '.PromotionPrize::class;
        $interleaved = false;

        Event::listen($eventName, function (PromotionPrize $retrieved) use (&$interleaved, $prize): void {
            if ($interleaved || (int) $retrieved->id !== (int) $prize->id) {
                return;
            }

            $interleaved = true;
            DB::table('prizes')->where('id', $prize->id)->update(['reserved_count' => 1]);
            DB::table('wins')->insert([
                'campaign_id' => $prize->campaign_id,
                'prize_id' => $prize->id,
                'token_hash' => hash('sha256', 'interleaved-reservation'),
                'status' => PromotionWin::STATUS_ISSUED,
                'prize_name_snapshot' => $prize->name,
                'fulfillment_mode_snapshot' => $prize->fulfillment_mode,
                'expires_at' => now()->addMinutes(30),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        try {
            Livewire::actingAs($admin)->test(PromotionAdministration::class)
                ->set('campaignId', $campaign->id)
                ->set('prizeId', $prize->id)
                ->set('prizeCode', $prize->code)
                ->set('prizeName', 'Aktualisierter Preis')
                ->set('prizeFulfillmentMode', PromotionPrize::FULFILLMENT_ONSITE)
                ->set('prizeQuota', 5)
                ->set('prizeIsActive', true)
                ->set('prizeSortOrder', 2)
                ->call('savePrize')
                ->assertHasNoErrors();
        } finally {
            Event::forget($eventName);
        }

        $this->assertTrue($interleaved);
        $this->assertSame(1, (int) DB::table('prizes')->where('id', $prize->id)->value('reserved_count'));
    }

    public function test_stale_counter_is_reconciled_but_quota_cannot_be_reduced_below_actual_wins(): void
    {
        $admin = $this->admin();
        $campaign = PromotionCampaign::create(['name' => 'Promo', 'code' => 'QUOTA', 'is_active' => false, 'created_by' => $admin->id]);
        $counterPrize = $this->prize($campaign, 'COUNTER', PromotionPrize::FULFILLMENT_ONSITE, 5);
        $counterPrize->forceFill(['reserved_count' => 2])->save();

        $this->savePrizeThroughLivewire($admin, $counterPrize, quota: 1)
            ->assertHasNoErrors();
        $this->assertSame(1, $counterPrize->fresh()->quota);
        $this->assertSame(0, $counterPrize->fresh()->reserved_count);

        $actualCampaign = PromotionCampaign::create(['name' => 'Actual', 'code' => 'ACTUALQUOTA', 'is_active' => false, 'created_by' => $admin->id]);
        $actualPrize = $this->prize($actualCampaign, 'ACTUAL', PromotionPrize::FULFILLMENT_ONSITE, 5);
        foreach (range(1, 2) as $index) {
            PromotionWin::create([
                'campaign_id' => $actualCampaign->id,
                'prize_id' => $actualPrize->id,
                'token_hash' => hash('sha256', 'actual-reservation-'.$index),
                'status' => PromotionWin::STATUS_EXPIRED,
                'prize_name_snapshot' => $actualPrize->name,
                'expires_at' => now()->subMinute(),
                'expired_at' => now(),
            ]);
        }

        $this->savePrizeThroughLivewire($admin, $actualPrize, quota: 1)
            ->assertHasErrors(['prizeQuota']);
        $this->assertSame(5, $actualPrize->fresh()->quota);
    }

    public function test_historical_wins_reconcile_counter_and_leave_only_the_real_remaining_quota(): void
    {
        $admin = $this->admin();
        $campaign = PromotionCampaign::create(['name' => 'Historisch', 'code' => 'HISTORY', 'is_active' => false, 'created_by' => $admin->id]);
        $prize = $this->prize($campaign, 'HISTORICAL', PromotionPrize::FULFILLMENT_ONSITE, 5);

        foreach (range(1, 4) as $index) {
            PromotionWin::create([
                'campaign_id' => $campaign->id,
                'prize_id' => $prize->id,
                'token_hash' => hash('sha256', 'historical-reservation-'.$index),
                'status' => PromotionWin::STATUS_EXPIRED,
                'prize_name_snapshot' => $prize->name,
                'fulfillment_mode_snapshot' => $prize->fulfillment_mode,
                'expires_at' => now()->subMinute(),
                'expired_at' => now(),
            ]);
        }

        $this->assertSame(0, $prize->fresh()->reserved_count);
        $this->savePrizeThroughLivewire($admin, $prize, quota: 5)->assertHasNoErrors();
        $this->assertSame(4, $prize->fresh()->reserved_count);

        // Manuell eingespielte historische Wins besitzen absichtlich keine
        // HMAC-Ereignisse. Der Counter wird nur für die eventlose, inaktive
        // Legacy-Kampagne repariert; eine Ausgabe bleibt bis zur fachlichen
        // Migration beziehungsweise Aktivierung fail-closed.

        try {
            app(PromotionWinService::class)->issue($campaign, $prize, $admin);
            $this->fail('Eine Ausgabe mit unsigniertem historischem Altbestand wurde akzeptiert.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('prize', $exception->errors());
        }

        $this->assertSame(4, $prize->fresh()->reserved_count);
        $this->assertSame(4, PromotionWin::query()->where('prize_id', $prize->id)->count());
    }

    public function test_activation_atomically_creates_missing_campaign_and_prize_configuration_events(): void
    {
        $admin = $this->admin();
        $campaign = PromotionCampaign::create(['name' => 'Altbestand', 'code' => 'BASELINE', 'is_active' => false, 'created_by' => $admin->id]);
        $this->prize($campaign, 'AMAZON20', PromotionPrize::FULFILLMENT_EXTERNAL, 5);
        $this->prize($campaign, 'AMAZON5', PromotionPrize::FULFILLMENT_EXTERNAL, 5);
        $surprise = $this->prize($campaign, 'SURPRISE', PromotionPrize::FULFILLMENT_ONSITE, 5);
        $surprise->forceFill(['configuration' => ['mode_confirmed' => true]])->save();

        $this->assertDatabaseCount('win_events', 0);

        Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->set('campaignIsActive', true)
            ->call('saveCampaign')
            ->assertHasNoErrors();

        $this->assertTrue($campaign->fresh()->is_active);
        $this->assertSame(1, PromotionWinEvent::query()->where('campaign_id', $campaign->id)->where('event_type', 'campaign.configured')->count());
        $this->assertSame(0, PromotionWinEvent::query()->where('campaign_id', $campaign->id)->where('event_type', 'prize.configured')->count());
        $this->assertTrue(app(\App\Services\Promotion\PromotionAuditChain::class)->verify($campaign->fresh()));
    }

    public function test_configuration_append_failure_rolls_back_campaign_and_prize_changes(): void
    {
        $admin = $this->admin();
        $campaign = PromotionCampaign::create(['name' => 'Vorher', 'code' => 'ROLLBACK', 'is_active' => false, 'created_by' => $admin->id]);
        $prize = $this->prize($campaign, 'ROLLBACKPRIZE', PromotionPrize::FULFILLMENT_ONSITE, 5);
        DB::table('promotion_settings')->where('id', 1)->update(['audit_secret_encrypted' => 'corrupt']);

        try {
            Livewire::actingAs($admin)->test(PromotionAdministration::class)
                ->call('editCampaign', $campaign->id)
                ->set('campaignName', 'Nachher')
                ->call('saveCampaign');
            $this->fail('Campaign-Änderung ohne Audit-Append wurde akzeptiert.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('entschluesselt', $exception->getMessage());
        }

        $this->assertSame('Vorher', $campaign->fresh()->name);

        try {
            $this->savePrizeThroughLivewire($admin, $prize, quota: 7);
            $this->fail('Prize-Änderung ohne Audit-Append wurde akzeptiert.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('entschluesselt', $exception->getMessage());
        }

        $this->assertSame(5, $prize->fresh()->quota);
        $this->assertDatabaseCount('win_events', 0);
    }

    public function test_hidden_technical_prize_fields_cannot_be_changed_and_publication_requires_complete_landing_content(): void
    {
        $admin = $this->admin();
        $campaign = PromotionCampaign::create(['name' => 'Promo', 'code' => 'ARBITRARY', 'is_active' => false, 'created_by' => $admin->id]);
        $amazon20 = $this->prize($campaign, 'FREIE-WAHL', PromotionPrize::FULFILLMENT_EXTERNAL, 5);

        $this->savePrizeThroughLivewire($admin, $amazon20, quota: 5, mode: PromotionPrize::FULFILLMENT_ONSITE)
            ->assertHasNoErrors();
        $this->assertSame(PromotionPrize::FULFILLMENT_EXTERNAL, $amazon20->fresh()->fulfillment_mode);

        $component = Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->call('editCampaign', $campaign->id)
            ->set('campaignIsActive', true)
            ->set('campaignIsPublic', true)
            ->call('saveCampaign')
            ->assertHasErrors(['campaignIsPublic']);

        $this->assertFalse($campaign->fresh()->is_active);

        $component
            ->set('campaignLandingHeadline', 'Drehen und gewinnen')
            ->set('campaignLandingText', 'Erklärung der Kampagne')
            ->set('campaignRulesText', 'Eine Teilnahme je Konto')
            ->call('saveCampaign')
            ->assertHasNoErrors();

        $this->assertTrue($campaign->fresh()->is_active);
        $this->assertTrue($campaign->fresh()->is_public);
    }

    public function test_domain_publication_requires_a_real_prize_with_quantity_and_public_campaign_keeps_its_last_prize(): void
    {
        $admin = $this->admin();
        $campaign = PromotionCampaign::create([
            'name' => 'Vollständige Kampagne',
            'code' => 'DOMAIN-PUBLISH',
            'landing_headline' => 'Jetzt gewinnen',
            'landing_text' => 'Zeige dein Ticket und drehe am Glücksrad.',
            'rules_text' => 'Eine Teilnahme je Konto.',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $zeroQuotaPrize = PromotionPrize::query()->create([
            'campaign_id' => $campaign->id,
            'code' => 'OHNE-MENGE',
            'name' => 'Gewinn ohne Menge',
            'outcome_type' => 'prize',
            'fulfillment_mode' => PromotionPrize::FULFILLMENT_ONSITE,
            'quota' => 0,
            'reserved_count' => 0,
            'awarded_count' => 0,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        try {
            app(PromotionTicketService::class)->publishCampaign($campaign, $admin);
            $this->fail('Eine Kampagne mit einem Gewinn ohne Menge wurde veröffentlicht.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('aktiver Gewinn mit Menge', $exception->getMessage());
        }

        $zeroQuotaPrize->delete();
        $prize = $this->prize($campaign, 'ECHTER-GEWINN', PromotionPrize::FULFILLMENT_ONSITE, 5);
        app(PromotionTicketService::class)->publishCampaign($campaign->fresh(), $admin);

        Livewire::actingAs($admin)
            ->test(PromotionAdministration::class)
            ->call('deletePrize', $prize->id)
            ->assertHasErrors(['prize'])
            ->assertSee('Der letzte Gewinn einer veröffentlichten Kampagne kann nicht gelöscht werden.');

        $this->assertTrue($campaign->fresh()->is_public);
        $this->assertDatabaseHas('prizes', ['id' => $prize->id]);
    }

    /** @return array{User, User, User, PromotionCampaign} */
    private function promotionActors(): array
    {
        $admin = $this->admin();
        $team = app(PromotionTeamService::class)->ensure($admin);
        $staff = $this->staff($team);
        $participant = $this->participant($admin);
        $campaign = PromotionCampaign::create([
            'name' => 'Promo',
            'code' => 'PROMO',
            'landing_headline' => 'Drehen und gewinnen',
            'landing_text' => 'Melde dich an und zeige dein persönliches Ticket.',
            'rules_text' => 'Eine Teilnahme je Konto.',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        return [$admin, $staff, $participant, $campaign];
    }

    /** @param array<string, mixed> $attributes */
    private function participant(User $owner, array $attributes = []): User
    {
        $team = Team::query()->where('name', 'Benutzer')->first();
        if (! $team) {
            $team = new Team;
            $team->forceFill([
                'user_id' => $owner->id,
                'name' => 'Benutzer',
                'personal_team' => false,
                'rbac_permissions' => [],
            ])->save();
        }
        $user = User::query()->create(array_merge([
            'name' => 'Participant',
            'email' => uniqid('participant-', true).'@example.test',
            'password' => Hash::make('password'),
            'role' => 'guest',
            'status' => true,
            'email_verified_at' => now(),
            'current_team_id' => $team->id,
        ], $attributes));
        Customer::query()->create([
            'user_id' => $user->id,
            'first_name' => '',
            'last_name' => '',
            'username' => $user->name,
        ]);
        $team->users()->attach($user->id, ['role' => 'guest']);

        return $user->fresh();
    }

    private function prize(PromotionCampaign $campaign, string $code, string $mode, int $quota): PromotionPrize
    {
        $prize = PromotionPrize::create(['campaign_id' => $campaign->id, 'code' => $code, 'name' => $code, 'fulfillment_mode' => $mode, 'quota' => $quota, 'reserved_count' => 0, 'is_active' => true, 'sort_order' => 1]);

        if ($campaign->is_active && ! PromotionWinEvent::query()->where('campaign_id', $campaign->id)->where('event_type', 'campaign.configured')->exists()) {
            app(\App\Services\Promotion\PromotionAuditChain::class)->appendConfiguration($campaign, 'campaign.configured', [
                'campaign' => $this->campaignAuditState($campaign),
                'prizes' => [$this->prizeAuditState($prize)],
            ], User::query()->findOrFail($campaign->created_by));
        } elseif ($campaign->is_active) {
            app(\App\Services\Promotion\PromotionAuditChain::class)->appendConfiguration($campaign, 'prize.configured', [
                'prize' => $this->prizeAuditState($prize),
            ], User::query()->findOrFail($campaign->created_by));
        }

        return $prize;
    }

    private function campaignAuditState(PromotionCampaign $campaign): array
    {
        return [
            'id' => (int) $campaign->id,
            'code' => (string) $campaign->code,
            'name_digest' => hash('sha256', (string) $campaign->name),
            'starts_at' => $campaign->getRawOriginal('starts_at'),
            'ends_at' => $campaign->getRawOriginal('ends_at'),
            'is_active' => (bool) $campaign->is_active,
        ];
    }

    private function prizeAuditState(PromotionPrize $prize): array
    {
        return [
            'id' => (int) $prize->id,
            'code' => (string) $prize->code,
            'name_digest' => hash('sha256', (string) $prize->name),
            'fulfillment_mode' => (string) $prize->fulfillment_mode,
            'quota' => (int) $prize->quota,
            'reserved_count' => (int) $prize->reserved_count,
            'is_active' => (bool) $prize->is_active,
            'sort_order' => (int) $prize->sort_order,
            'configuration_digest' => hash('sha256', json_encode($prize->configuration, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)),
        ];
    }

    private function savePrizeThroughLivewire(User $admin, PromotionPrize $prize, int $quota, ?string $mode = null): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::actingAs($admin)->test(PromotionAdministration::class)
            ->set('campaignId', $prize->campaign_id)
            ->set('prizeId', $prize->id)
            ->set('prizeCode', $prize->code)
            ->set('prizeName', $prize->name)
            ->set('prizeFulfillmentMode', $mode ?? $prize->fulfillment_mode)
            ->set('prizeQuota', $quota)
            ->set('prizeIsActive', $prize->is_active)
            ->set('prizeSortOrder', $prize->sort_order)
            ->call('savePrize');
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => uniqid('admin').'@example.test', 'password' => Hash::make('password'), 'role' => 'admin', 'status' => true, 'email_verified_at' => now()]);
    }

    private function staff(Team $team, bool $attach = true): User
    {
        $staff = User::create(['name' => 'Promo Staff', 'email' => uniqid('staff').'@example.test', 'password' => Hash::make('password'), 'role' => 'staff', 'status' => true, 'email_verified_at' => now(), 'current_team_id' => $team->id]);
        if ($attach) {
            $team->users()->attach($staff->id, ['role' => 'team_access']);
        }

        return $staff;
    }

    private function publishV2Campaign(User $admin): PromotionCampaign
    {
        $campaign = PromotionCampaign::create([
            'name' => 'Öffentliche Testkampagne',
            'landing_headline' => 'Drehe am Glücksrad',
            'landing_text' => 'Melde dich an und zeige dein persönliches Ticket vor.',
            'rules_text' => 'Pro Konto und Kampagne ist genau eine Teilnahme möglich.',
            'code' => 'V2-'.str()->upper(str()->random(8)),
            'quota_exhaustion_policy' => 'block',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $this->prize($campaign, 'TEST-GEWINN', PromotionPrize::FULFILLMENT_ONSITE, 10);
        $this->prize($campaign, 'NO-WIN', PromotionPrize::FULFILLMENT_ONSITE, 0)
            ->forceFill(['outcome_type' => 'no_win', 'awarded_count' => 0])
            ->save();

        $audit = app(\App\Services\Promotion\PromotionAuditChain::class);
        $audit->appendConfiguration($campaign, 'campaign.configured', $audit->configurationPayload($campaign), $admin);

        return app(PromotionTicketService::class)->publishCampaign($campaign, $admin);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->string('role')->default('guest');
            $t->boolean('status')->default(true);
            $t->unsignedBigInteger('current_team_id')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('teams', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('name');
            $t->boolean('personal_team');
            $t->json('rbac_permissions')->nullable();
            $t->timestamps();
        });
        Schema::create('team_user', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('team_id');
            $t->unsignedBigInteger('user_id');
            $t->string('role')->nullable();
            $t->timestamps();
            $t->unique(['team_id', 'user_id']);
        });
        Schema::create('customers', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('first_name');
            $t->string('last_name');
            $t->string('username');
            $t->string('profile_picture')->nullable();
            $t->string('phone_number')->nullable();
            $t->string('street')->nullable();
            $t->string('city')->nullable();
            $t->string('state')->nullable();
            $t->string('postal_code')->nullable();
            $t->string('country')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('staff_invitations', function (Blueprint $t): void {
            $t->id();
            $t->string('email');
            $t->char('token_hash', 64)->unique();
            $t->unsignedBigInteger('team_id');
            $t->unsignedBigInteger('invited_by');
            $t->string('role')->default('staff');
            $t->string('position')->nullable();
            $t->dateTime('expires_at');
            $t->dateTime('accepted_at')->nullable();
            $t->timestamps();
        });
        Schema::create('campaigns', function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->string('landing_headline')->nullable();
            $t->text('landing_text')->nullable();
            $t->text('rules_text')->nullable();
            $t->string('code')->unique();
            $t->dateTime('starts_at')->nullable();
            $t->dateTime('ends_at')->nullable();
            $t->string('quota_exhaustion_policy', 32)->default('block');
            $t->boolean('is_active');
            $t->boolean('is_public')->default(false);
            $t->unsignedTinyInteger('public_slot')->nullable()->unique();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
        Schema::create('prizes', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('campaign_id');
            $t->string('code');
            $t->string('name');
            $t->string('outcome_type', 32)->default('prize');
            $t->string('fulfillment_mode');
            $t->unsignedInteger('quota');
            $t->unsignedInteger('reserved_count')->default(0);
            $t->unsignedInteger('awarded_count')->default(0);
            $t->boolean('is_active');
            $t->unsignedInteger('sort_order')->default(0);
            $t->json('configuration')->nullable();
            $t->timestamps();
        });
        Schema::create('participations', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('campaign_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('public_id')->unique();
            $t->timestamps();
            $t->unique(['campaign_id', 'user_id']);
        });
        Schema::create('wins', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('campaign_id');
            $t->unsignedBigInteger('prize_id');
            $t->unsignedBigInteger('participation_id')->nullable();
            $t->unsignedBigInteger('issued_by')->nullable();
            $t->unsignedBigInteger('fulfilled_by')->nullable();
            $t->string('status');
            $t->char('token_hash', 64)->unique();
            $t->char('claim_key', 64)->nullable()->unique();
            $t->string('prize_name_snapshot');
            $t->string('fulfillment_mode_snapshot')->nullable();
            foreach (['expires_at', 'consumed_at', 'bound_at', 'confirmed_at', 'disputed_at', 'fulfilled_at', 'expired_at', 'cancelled_at'] as $column) {
                $t->dateTime($column)->nullable();
            } $t->text('cancellation_reason')->nullable();
            $t->timestamps();
        });
        Schema::create('promotion_tickets', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('participation_id')->unique();
            $t->unsignedBigInteger('campaign_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('status', 20);
            $t->dateTime('issued_at');
            $t->dateTime('activated_at')->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->dateTime('cancelled_at')->nullable();
            $t->timestamps();
            $t->unique(['campaign_id', 'user_id']);
        });
        Schema::create('promotion_turns', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('campaign_id');
            $t->unsignedBigInteger('started_by')->nullable();
            $t->unsignedBigInteger('completed_by')->nullable();
            $t->unsignedBigInteger('released_by')->nullable();
            $t->string('status', 20);
            $t->dateTime('started_at');
            $t->dateTime('completed_at')->nullable();
            $t->dateTime('released_at')->nullable();
            $t->string('release_reason', 120)->nullable();
            $t->timestamps();
        });
        Schema::create('promotion_campaign_states', function (Blueprint $t): void {
            $t->unsignedBigInteger('campaign_id')->primary();
            $t->unsignedBigInteger('active_turn_id')->nullable()->unique();
            $t->boolean('sticker_required')->default(false);
            $t->dateTime('sticker_acknowledged_at')->nullable();
            $t->unsignedBigInteger('sticker_acknowledged_by')->nullable();
            $t->timestamps();
        });
        Schema::create('promotion_spin_results', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('turn_id');
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('campaign_id');
            $t->unsignedBigInteger('prize_id')->nullable();
            $t->unsignedSmallInteger('sequence');
            $t->string('outcome_type_snapshot', 32);
            $t->string('label_snapshot');
            $t->string('fulfillment_mode_snapshot', 32)->nullable();
            $t->boolean('is_final')->default(false);
            $t->unsignedBigInteger('recorded_by')->nullable();
            $t->dateTime('recorded_at');
            $t->unsignedBigInteger('corrects_result_id')->nullable();
            $t->dateTime('superseded_at')->nullable();
            $t->string('correction_reason', 255)->nullable();
            $t->string('mail_status', 20)->default('not_required');
            $t->dateTime('mail_sent_at')->nullable();
            $t->dateTime('mail_failed_at')->nullable();
            $t->dateTime('mail_last_attempted_at')->nullable();
            $t->char('mail_error_digest', 64)->nullable();
            $t->unsignedBigInteger('fulfilled_by')->nullable();
            $t->dateTime('fulfilled_at')->nullable();
            $t->timestamps();
            $t->unique(['turn_id', 'sequence']);
        });
        Schema::create('promotion_audit_heads', function (Blueprint $t): void {
            $t->unsignedBigInteger('campaign_id')->primary();
            $t->unsignedBigInteger('last_sequence')->default(0);
            $t->char('last_hash', 64);
            $t->timestamps();
        });
        Schema::create('win_events', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('campaign_id');
            $t->unsignedBigInteger('sequence');
            $t->unsignedBigInteger('win_id')->nullable();
            $t->unsignedBigInteger('ticket_id')->nullable();
            $t->unsignedBigInteger('turn_id')->nullable();
            $t->unsignedBigInteger('spin_result_id')->nullable();
            $t->unsignedBigInteger('participation_id')->nullable();
            $t->char('actor_ref', 64)->nullable();
            $t->string('event_type');
            $t->json('payload');
            $t->char('previous_hash', 64);
            $t->char('event_hash', 64)->unique();
            $t->dateTime('occurred_at');
        });
        Schema::create('promotion_settings', function (Blueprint $t): void {
            $t->unsignedTinyInteger('id')->primary();
            $t->unsignedBigInteger('public_campaign_id')->nullable();
            $t->boolean('enabled')->default(false);
            $t->string('redemption_base_url', 2048)->nullable();
            $t->unsignedSmallInteger('qr_ttl_minutes')->default(30);
            $t->text('audit_secret_encrypted');
            $t->char('configuration_mac', 64);
            $t->timestamps();
        });
        Schema::create('social_auth_provider_settings', function (Blueprint $t): void {
            $t->id();
            $t->string('provider', 32)->unique();
            $t->boolean('enabled')->default(false);
            $t->string('client_id')->nullable();
            $t->text('client_secret_encrypted')->nullable();
            $t->string('redirect_uri', 2048)->nullable();
            $t->string('apple_team_id', 64)->nullable();
            $t->string('apple_key_id', 64)->nullable();
            $t->dateTime('client_secret_expires_at')->nullable();
            $t->char('configuration_mac', 64)->nullable();
            $t->timestamps();
        });
        Schema::create('social_accounts', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('provider', 32);
            $t->string('provider_user_id');
            $t->string('provider_email')->nullable();
            $t->timestamps();
            $t->unique(['provider', 'provider_user_id']);
            $t->unique(['user_id', 'provider']);
        });
        Schema::create('activity_log', function (Blueprint $t): void {
            $t->bigIncrements('id');
            $t->string('log_name')->nullable();
            $t->text('description');
            $t->nullableMorphs('subject', 'subject');
            $t->nullableMorphs('causer', 'causer');
            $t->json('properties')->nullable();
            $t->uuid('batch_uuid')->nullable();
            $t->timestamps();
        });
    }
}
