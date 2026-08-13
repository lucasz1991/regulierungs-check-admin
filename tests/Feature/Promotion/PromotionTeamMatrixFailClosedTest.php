<?php

namespace Tests\Feature\Promotion;

use App\Models\Team;
use App\Models\User;
use App\Support\Rbac\RbacCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PromotionTeamMatrixFailClosedTest extends TestCase
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

    public function test_additional_permission_blocks_every_promotion_permission_and_the_foreign_module(): void
    {
        $matrix = RbacCatalog::promotionTeamMatrix();
        $matrix['content.news.manage'] = true;
        $staff = $this->promotionStaff($matrix);

        foreach (RbacCatalog::promotionTeamPermissions() as $permission) {
            $this->assertFalse($staff->hasRbacPermission($permission));
        }

        $this->assertFalse($staff->hasRbacPermission('content.news.manage'));
    }

    public function test_missing_required_permission_blocks_every_promotion_permission(): void
    {
        $matrix = RbacCatalog::promotionTeamMatrix();
        unset($matrix['promotion.fulfillment.onsite']);
        $staff = $this->promotionStaff($matrix);

        foreach (RbacCatalog::promotionTeamPermissions() as $permission) {
            $this->assertFalse($staff->hasRbacPermission($permission));
        }

        $this->assertFalse($staff->hasRbacPermission('content.news.manage'));
    }

    /** @param array<string, bool> $matrix */
    private function promotionStaff(array $matrix): User
    {
        $owner = User::query()->create([
            'name' => 'Admin',
            'email' => uniqid('admin', true).'@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => true,
            'email_verified_at' => now(),
        ]);
        $team = new Team;
        $team->forceFill([
            'user_id' => $owner->id,
            'name' => RbacCatalog::PROMOTION_TEAM_NAME,
            'personal_team' => false,
            'rbac_permissions' => $matrix,
        ])->save();
        $staff = User::query()->create([
            'name' => 'Promotion Staff',
            'email' => uniqid('staff', true).'@example.test',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => true,
            'email_verified_at' => now(),
            'current_team_id' => $team->id,
        ]);
        $team->users()->attach($staff->id, ['role' => 'team_access']);

        return $staff;
    }
}
