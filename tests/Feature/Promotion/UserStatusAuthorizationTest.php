<?php

namespace Tests\Feature\Promotion;

use App\Livewire\Admin\UserProfile;
use App\Livewire\Admin\Users;
use App\Models\Team;
use App\Models\User;
use App\Services\Admin\UserStatusService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Locked;
use ReflectionProperty;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserStatusAuthorizationTest extends TestCase
{
    private UserStatusService $statuses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->statuses = app(UserStatusService::class);
    }

    public function test_delegated_manager_can_manage_a_normal_user_through_both_components(): void
    {
        $manager = $this->delegatedManager();
        $customer = $this->user('Customer', 'customer@example.test');
        $this->actingAs($manager);

        (new Users)->deactivateUser($customer->id, $this->statuses);
        $this->assertFalse($customer->fresh()->isActive());

        $profile = new UserProfile;
        $profile->mount($customer->id);
        $profile->activateUser($this->statuses);

        $this->assertTrue($customer->fresh()->isActive());
    }

    public function test_delegated_manager_cannot_deactivate_or_activate_a_global_admin_from_users(): void
    {
        $manager = $this->delegatedManager();
        $activeAdmin = $this->user('Active Admin', 'active-admin@example.test', 'admin');
        $inactiveAdmin = $this->user('Inactive Admin', 'inactive-admin@example.test', 'admin', false);
        $component = new Users;
        $this->actingAs($manager);

        $this->assertForbidden(fn () => $component->deactivateUser($activeAdmin->id, $this->statuses));
        $this->assertForbidden(fn () => $component->activateUser($inactiveAdmin->id, $this->statuses));

        $this->assertTrue($activeAdmin->fresh()->isActive());
        $this->assertFalse($inactiveAdmin->fresh()->isActive());
    }

    public function test_delegated_manager_cannot_deactivate_or_activate_a_global_admin_from_profile(): void
    {
        $manager = $this->delegatedManager();
        $activeAdmin = $this->user('Active Admin', 'profile-active-admin@example.test', 'admin');
        $inactiveAdmin = $this->user('Inactive Admin', 'profile-inactive-admin@example.test', 'admin', false);
        $this->actingAs($manager);

        $activeProfile = new UserProfile;
        $activeProfile->mount($activeAdmin->id);
        $this->assertForbidden(fn () => $activeProfile->deactivateUser($this->statuses));

        $inactiveProfile = new UserProfile;
        $inactiveProfile->mount($inactiveAdmin->id);
        $this->assertForbidden(fn () => $inactiveProfile->activateUser($this->statuses));

        $this->assertTrue($activeAdmin->fresh()->isActive());
        $this->assertFalse($inactiveAdmin->fresh()->isActive());
    }

    public function test_delegated_bulk_mutation_containing_an_admin_is_rejected_atomically(): void
    {
        $manager = $this->delegatedManager();
        $customer = $this->user('Customer', 'bulk-customer@example.test');
        $admin = $this->user('Admin Target', 'bulk-admin@example.test', 'admin');
        $inactiveCustomer = $this->user('Inactive Customer', 'bulk-inactive-customer@example.test', 'guest', false);
        $inactiveAdmin = $this->user('Inactive Admin', 'bulk-inactive-admin@example.test', 'admin', false);
        $component = new Users;
        $component->selectedUsers = [$customer->id, (string) $admin->id, $customer->id];
        $this->actingAs($manager);

        $this->assertForbidden(fn () => $component->deactivateUsers($this->statuses));

        $this->assertTrue($customer->fresh()->isActive());
        $this->assertTrue($admin->fresh()->isActive());

        $component->selectedUsers = [$inactiveCustomer->id, (string) $inactiveAdmin->id];
        $this->assertForbidden(fn () => $component->activateUsers($this->statuses));

        $this->assertFalse($inactiveCustomer->fresh()->isActive());
        $this->assertFalse($inactiveAdmin->fresh()->isActive());
    }

    public function test_global_admin_cannot_deactivate_self_from_users_or_profile(): void
    {
        $admin = $this->user('Only Admin', 'only-admin@example.test', 'admin');
        $this->actingAs($admin);

        $this->assertForbidden(fn () => (new Users)->deactivateUser($admin->id, $this->statuses));

        $profile = new UserProfile;
        $profile->mount($admin->id);
        $this->assertForbidden(fn () => $profile->deactivateUser($this->statuses));

        $this->assertTrue($admin->fresh()->isActive());
        $this->assertSame(1, User::query()->where('role', 'admin')->where('status', true)->count());
    }

    public function test_global_admin_can_manage_another_admin_without_removing_the_last_active_admin(): void
    {
        $actor = $this->user('Admin Actor', 'admin-actor@example.test', 'admin');
        $peer = $this->user('Admin Peer', 'admin-peer@example.test', 'admin');
        $this->actingAs($actor);

        (new Users)->deactivateUser($peer->id, $this->statuses);
        $this->assertFalse($peer->fresh()->isActive());
        $this->assertTrue($actor->fresh()->isActive());
        $this->assertSame(1, User::query()->where('role', 'admin')->where('status', true)->count());

        $profile = new UserProfile;
        $profile->mount($peer->id);
        $profile->activateUser($this->statuses);

        $this->assertTrue($peer->fresh()->isActive());
        $this->assertSame(2, User::query()->where('role', 'admin')->where('status', true)->count());
    }

    public function test_profile_target_id_is_locked_against_livewire_snapshot_tampering(): void
    {
        $property = new ReflectionProperty(UserProfile::class, 'userId');

        $this->assertCount(1, $property->getAttributes(Locked::class));
    }

    private function delegatedManager(): User
    {
        $owner = $this->user('Team Owner', uniqid('owner-', true).'@example.test', 'admin');
        $team = new Team;
        $team->forceFill([
            'user_id' => $owner->id,
            'name' => uniqid('Support-', true),
            'personal_team' => false,
            'rbac_permissions' => ['users.manage' => true],
        ])->save();

        $manager = $this->user('User Manager', uniqid('manager-', true).'@example.test', 'staff');
        $manager->forceFill(['current_team_id' => $team->id])->save();
        $team->users()->attach($manager->id, ['role' => 'team_access']);

        return $manager->fresh();
    }

    private function user(string $name, string $email, string $role = 'guest', bool $active = true): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'status' => $active,
            'email_verified_at' => now(),
        ]);
    }

    private function assertForbidden(callable $action): void
    {
        try {
            $action();
            $this->fail('Expected the status mutation to be forbidden.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
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
    }
}
