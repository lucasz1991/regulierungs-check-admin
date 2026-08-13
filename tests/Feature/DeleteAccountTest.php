<?php

namespace Tests\Feature;

use App\Actions\Jetstream\DeleteUser;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Jetstream\Features;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
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
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
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
        });
    }

    public function test_account_deletion_feature_and_profile_ui_are_disabled(): void
    {
        $this->assertFalse(Features::hasAccountDeletionFeatures());

        $admin = $this->user('admin');

        $response = $this->actingAs($admin)->get(route('profile.show'));

        $response->assertOk();
        $response->assertDontSee('Delete Account');
        $response->assertDontSee('Konto l');
        $response->assertDontSee('profile.delete-user-form', false);
    }

    public function test_staff_cannot_open_the_admin_profile_self_service(): void
    {
        $staff = $this->user('staff');

        $this->actingAs($staff)
            ->get(route('profile.show'))
            ->assertForbidden();
    }

    public function test_server_side_deleter_rejects_admin_and_staff_accounts(): void
    {
        foreach (['admin', 'staff'] as $role) {
            $user = $this->user($role, $role.'-delete@example.test');

            try {
                app(DeleteUser::class)->delete($user);
                $this->fail('Der Jetstream-Loeschdienst muss fail-closed abbrechen.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }

            $this->assertNotNull($user->fresh());
        }
    }

    private function user(string $role, ?string $email = null): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email ?? $role.'@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
            'status' => true,
            'email_verified_at' => now(),
        ]);
    }
}
