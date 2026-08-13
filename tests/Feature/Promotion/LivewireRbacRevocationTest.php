<?php

namespace Tests\Feature\Promotion;

use App\Livewire\Admin\AdminTasksList;
use App\Livewire\Admin\Cms\EditProject;
use App\Livewire\Admin\Employees;
use App\Livewire\Admin\Exports;
use App\Livewire\Admin\MailManagement;
use App\Livewire\Admin\ManageContacts;
use App\Livewire\Admin\PromotionAdministration;
use App\Livewire\Admin\RatingStructure\Index as RatingStructure;
use App\Livewire\Admin\Reviews\ClaimRatingList;
use App\Livewire\Admin\Reviews\ShowClaimRating;
use App\Livewire\Admin\Safety;
use App\Livewire\Admin\TeamPermissions;
use App\Livewire\Admin\UserProfile;
use App\Livewire\Admin\Users;
use App\Livewire\AdminConfig;
use App\Livewire\AdminDashboard;
use App\Livewire\AdminMessageBox;
use App\Livewire\Concerns\RequiresRbacPermission;
use App\Livewire\Promotion\PromotionConsole;
use App\Livewire\WebContentManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class LivewireRbacRevocationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_every_routed_admin_livewire_component_matches_its_route_permission(): void
    {
        $expected = [
            'admin.index' => [AdminDashboard::class, 'admin.dashboard.view'],
            'admin.config' => [AdminConfig::class, 'settings.manage'],
            'admin.cms.edit-project' => [EditProject::class, 'content.pagebuilder.manage'],
            'admin.ratingstructure.index' => [RatingStructure::class, 'ratings.structure.manage'],
            'admin.messages' => [AdminMessageBox::class, 'messages.manage'],
            'admin.tasks' => [AdminTasksList::class, 'tasks.manage'],
            'admin.exports' => [Exports::class, 'exports.manage'],
            'admin.users' => [Users::class, 'users.manage'],
            'admin.user-profile' => [UserProfile::class, 'users.manage'],
            'admin.safety' => [Safety::class, 'audit.view'],
            'admin.employees' => [Employees::class, 'staff.manage'],
            'admin.team-permissions' => [TeamPermissions::class, 'roles.manage'],
            'admin.promotion' => [PromotionAdministration::class, 'promotion.campaigns.manage'],
            'admin.mails' => [MailManagement::class, 'mails.manage'],
            'admin.contacts' => [ManageContacts::class, 'contacts.manage'],
            'admin.reviews.claim-ratings' => [ClaimRatingList::class, 'reviews.manage'],
            'admin.reviews.show' => [ShowClaimRating::class, 'reviews.manage'],
            'promotion.console' => [PromotionConsole::class, 'promotion.wins.record'],
        ];

        $dynamic = [
            'admin.webcontentmanager' => [WebContentManager::class, 'content.web.manage'],
            'admin.webcontent.news' => [WebContentManager::class, 'content.news.manage'],
        ];

        $routedLivewire = collect(app('router')->getRoutes()->getRoutes())
            ->filter(function ($route): bool {
                $name = (string) $route->getName();
                $action = $route->getActionName();

                return ($name === 'promotion.console' || str_starts_with($name, 'admin.'))
                    && class_exists($action)
                    && is_subclass_of($action, Component::class);
            })
            ->mapWithKeys(fn ($route): array => [(string) $route->getName() => $route->getActionName()])
            ->sortKeys()
            ->all();

        $expectedActions = collect($expected + $dynamic)
            ->map(fn (array $definition): string => $definition[0])
            ->sortKeys()
            ->all();

        $this->assertSame($expectedActions, $routedLivewire, 'Neue Admin-Livewire-Route braucht eine explizite RBAC-Zuordnung.');

        foreach ($expected as $routeName => [$componentClass, $permission]) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertSame($componentClass, $route->getActionName(), $routeName);
            $this->assertContains('can:'.$permission, $route->gatherMiddleware(), $routeName);
            $this->assertContains(RequiresRbacPermission::class, class_uses_recursive($componentClass), $componentClass);

            $method = new ReflectionMethod($componentClass, 'requiredRbacPermission');
            $this->assertSame($permission, $method->invoke(new $componentClass), $componentClass);
        }

        foreach ($dynamic as $routeName => [$componentClass, $permission]) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertSame($componentClass, $route->getActionName(), $routeName);
            $this->assertContains('can:'.$permission, $route->gatherMiddleware(), $routeName);
        }

        $permissionForTab = new ReflectionMethod(WebContentManager::class, 'permissionForTab');
        $manager = new WebContentManager;

        $this->assertSame('content.web.manage', $permissionForTab->invoke($manager, 'webpages'));
        $this->assertSame('content.web.manage', $permissionForTab->invoke($manager, 'faq'));
        $this->assertSame('content.web.manage', $permissionForTab->invoke($manager, 'blog'));
        $this->assertSame('content.pagebuilder.manage', $permissionForTab->invoke($manager, 'module'));
        $this->assertSame('content.news.manage', $permissionForTab->invoke($manager, 'news'));
        $this->assertSame('settings.manage', $permissionForTab->invoke($manager, 'tools'));
        $this->assertSame(WebContentManager::class, (new ReflectionMethod(WebContentManager::class, 'hydrate'))->getDeclaringClass()->getName());
    }

    public function test_sensitive_admin_components_fail_closed_during_livewire_mount(): void
    {
        [, $staff] = $this->staffWithPermissions([]);

        $components = [
            [Users::class, []],
            [AdminConfig::class, []],
            [AdminTasksList::class, []],
            [Exports::class, []],
            [UserProfile::class, ['userId' => $staff->id]],
            [MailManagement::class, []],
            [ManageContacts::class, []],
            [ClaimRatingList::class, []],
            [ShowClaimRating::class, ['ratingId' => 1]],
        ];

        foreach ($components as [$componentClass, $parameters]) {
            Livewire::actingAs($staff)
                ->test($componentClass, $parameters)
                ->assertForbidden();
        }
    }

    public function test_dynamic_web_content_snapshot_checks_its_hydrated_tab_after_revocation(): void
    {
        [, $staff] = $this->staffWithPermissions(['content.news.manage']);
        $this->actingAs($staff);
        $manager = new WebContentManager;
        $manager->selectedTab = 'news';

        // A news-only editor must not be checked against the pre-hydration
        // default tab (webpages).
        $manager->hydrate();

        $staff->currentTeam->forceFill(['rbac_permissions' => []])->save();
        $staff->unsetRelation('currentTeam');
        auth()->setUser($staff);

        $this->expectException(AuthorizationException::class);
        $manager->hydrate();
    }

    public function test_revoked_permission_blocks_an_existing_livewire_snapshot_before_mutation(): void
    {
        [$team, $staff] = $this->staffWithPermissions(['users.manage']);
        $victim = $this->user('Victim', 'victim@example.test');
        $component = Livewire::actingAs($staff)->test(Users::class)->assertOk();

        $team->forceFill(['rbac_permissions' => []])->save();
        $staff->unsetRelation('currentTeam');
        auth()->setUser($staff);

        $component->call('deactivateUser', $victim->id)->assertForbidden();

        $this->assertTrue($victim->fresh()->isActive());
    }

    public function test_current_team_change_blocks_an_existing_livewire_snapshot_before_mutation(): void
    {
        [, $staff] = $this->staffWithPermissions(['users.manage']);
        $victim = $this->user('Victim', 'victim-team@example.test');
        $component = Livewire::actingAs($staff)->test(Users::class)->assertOk();
        $otherTeam = $this->team([]);
        $otherTeam->users()->attach($staff->id, ['role' => 'team_access']);

        $staff->forceFill(['current_team_id' => $otherTeam->id])->save();
        $staff->unsetRelation('currentTeam');
        $staff->unsetRelation('teams');
        auth()->setUser($staff);

        $component->call('deactivateUser', $victim->id)->assertForbidden();

        $this->assertTrue($victim->fresh()->isActive());
    }

    public function test_inactive_user_blocks_an_existing_livewire_snapshot_before_mutation(): void
    {
        [, $staff] = $this->staffWithPermissions(['users.manage']);
        $victim = $this->user('Victim', 'victim-status@example.test');
        $component = Livewire::actingAs($staff)->test(Users::class)->assertOk();

        $staff->forceFill(['status' => false])->save();
        auth()->setUser($staff);

        $component->call('deactivateUser', $victim->id)->assertForbidden();

        $this->assertTrue($victim->fresh()->isActive());
    }

    /** @param list<string> $permissions
     * @return array{Team, User}
     */
    private function staffWithPermissions(array $permissions): array
    {
        $team = $this->team($permissions);
        $staff = $this->user('Staff', uniqid('staff-', true).'@example.test', 'staff');
        $staff->forceFill(['current_team_id' => $team->id])->save();
        $team->users()->attach($staff->id, ['role' => 'team_access']);

        return [$team, $staff->fresh()];
    }

    /** @param list<string> $permissions */
    private function team(array $permissions): Team
    {
        $owner = $this->user('Owner', uniqid('owner-', true).'@example.test', 'admin');
        $team = new Team;
        $team->forceFill([
            'user_id' => $owner->id,
            'name' => uniqid('Editorial-', true),
            'personal_team' => false,
            'rbac_permissions' => array_fill_keys($permissions, true),
        ])->save();

        return $team;
    }

    private function user(string $name, string $email, string $role = 'guest'): User
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

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
