<?php

namespace Tests\Feature;

use App\Livewire\Admin\StaffProfile;
use App\Livewire\Admin\UserProfile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class UserProfileRedesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('guest');
            $table->boolean('status')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->string('profile_photo_path')->nullable();
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
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            foreach (['first_name', 'last_name', 'phone_number', 'street', 'city', 'state', 'postal_code', 'country'] as $column) {
                $table->string($column)->nullable();
            }
            $table->softDeletes();
            $table->timestamps();
        });
        foreach (['insurances', 'insurance_types', 'insurance_subtypes'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
            });
        }
        Schema::create('claim_ratings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('insurance_id')->nullable();
            $table->unsignedBigInteger('insurance_type_id')->nullable();
            $table->unsignedBigInteger('insurance_subtype_id')->nullable();
            $table->string('status');
            $table->boolean('is_public')->default(false);
            $table->decimal('rating_score', 5, 4)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function test_reviews_are_scoped_to_the_profile_paginated_and_exclude_deleted_entries(): void
    {
        $admin = $this->user('Redaktion Demo', 'admin');
        $person = $this->user('Mara Beispiel');
        $other = $this->user('Andere Person');
        DB::table('insurances')->insert(['id' => 1, 'name' => 'Beispiel Versicherung']);
        DB::table('insurance_types')->insert(['id' => 1, 'name' => 'Sachversicherung']);
        DB::table('insurance_subtypes')->insert(['id' => 1, 'name' => 'Hausrat']);
        DB::table('customers')->insert([
            'user_id' => $person->id, 'first_name' => 'Mara', 'last_name' => 'Beispiel',
            'street' => 'Musterstraße 12', 'postal_code' => '10115', 'city' => 'Berlin',
            'country' => 'Deutschland',
        ]);
        for ($id = 1; $id <= 10; $id++) {
            $this->rating($id, $person, ['insurance_id' => 1, 'insurance_type_id' => 1, 'insurance_subtype_id' => 1]);
        }
        $this->rating(11, $other);
        $this->rating(12, $person, ['deleted_at' => now()]);

        $profile = Livewire::actingAs($admin)->test(UserProfile::class, ['userId' => $person->id])
            ->assertSee('Mara Beispiel')->assertSee('Personendaten')
            ->assertSee('Bewertungen des Benutzers')->assertSee('Beispiel Versicherung')
            ->assertSee('Hausrat')->assertSee('4,0 / 5')->assertSee('Öffentlich sichtbar')
            ->assertSee('Bewertung #10')->assertDontSee('Bewertung #11')->assertDontSee('Bewertung #12')
            ->assertSeeHtml(route('admin.reviews.show', ['ratingId' => 10]))
            ->assertDontSee('Follower')->assertDontSee('gefolgt')
            ->assertDontSee('Produkte geliked')->assertDontSee('Einkünfte')->assertDontSee('Buchungen')
            ->assertViewHas('ratings', fn ($ratings) => $ratings->total() === 10 && $ratings->count() === 8);

        $profile->call('nextPage', 'ratingsPage')
            ->assertViewHas('ratings', fn ($ratings) => $ratings->currentPage() === 2 && $ratings->count() === 2)
            ->assertSee('Bewertung #1')->assertDontSee('Bewertung #10');

        $profile->call('previousPage', 'ratingsPage')
            ->assertViewHas('ratings', fn ($ratings) => $ratings->currentPage() === 1 && $ratings->count() === 8)
            ->assertSee('Bewertung #10');
    }

    public function test_staff_profile_and_empty_profile_render_without_any_shop_tables(): void
    {
        $admin = $this->user('Redaktion Demo', 'admin');
        $staff = $this->user('Team Beispiel', 'staff');

        Livewire::actingAs($admin)->test(StaffProfile::class, ['userId' => $staff->id])
            ->assertSee('Mitarbeiterprofil')->assertSee('Kein Team zugeordnet')
            ->assertSee('Noch keine Bewertungen')->assertSee('Noch keine Promotion-Teilnahme')
            ->assertDontSee('Buchungen');
    }

    public function test_review_permission_is_checked_before_querying_and_after_revocation(): void
    {
        $owner = $this->user('Redaktion Demo', 'admin');
        $manager = $this->user('Support Demo', 'staff');
        $person = $this->user('Person Demo');
        $this->rating(1, $person);
        $team = new Team;
        $team->forceFill(['user_id' => $owner->id, 'name' => 'Support', 'personal_team' => false, 'rbac_permissions' => ['users.manage' => true]])->save();
        $team->users()->attach($manager->id, ['role' => 'team_access']);
        $manager->forceFill(['current_team_id' => $team->id])->save();

        DB::enableQueryLog();
        Livewire::actingAs($manager->fresh())->test(UserProfile::class, ['userId' => $person->id])
            ->assertSee('Bewertungen nicht freigegeben')->assertDontSee('Bewertung #1')
            ->assertViewHas('ratings', null);
        $this->assertFalse(collect(DB::getQueryLog())->contains(fn ($query) => str_contains($query['query'], 'from "claim_ratings"')));
        DB::disableQueryLog();

        $team->forceFill(['rbac_permissions' => ['users.manage' => true, 'reviews.manage' => true]])->save();
        $profile = Livewire::actingAs($manager->fresh())->test(UserProfile::class, ['userId' => $person->id])->assertSee('Bewertung #1');
        $team->forceFill(['rbac_permissions' => ['users.manage' => true]])->save();
        auth()->setUser($manager->fresh());
        $profile->call('$refresh')->assertSee('Bewertungen nicht freigegeben')->assertDontSee('Bewertung #1');
    }

    public function test_missing_insurance_and_pending_zero_scores_are_rendered_honestly(): void
    {
        $admin = $this->user('Redaktion Demo', 'admin');
        $person = $this->user('Person Demo');
        $this->rating(1, $person, ['rating_score' => null, 'status' => 'pending', 'is_public' => true, 'insurance_id' => 99]);
        $this->rating(2, $person, ['rating_score' => 0, 'status' => 'rejected']);

        Livewire::actingAs($admin)->test(UserProfile::class, ['userId' => $person->id])
            ->assertSee('Versicherung nicht mehr verfügbar')->assertSee('Noch offen')
            ->assertSee('0,0 / 5')->assertSee('Ausstehend')->assertSee('Abgelehnt')
            ->assertDontSee('Öffentlich sichtbar');
    }

    protected function user(string $name, string $role = 'guest'): User
    {
        return User::query()->create([
            'name' => $name, 'email' => str_replace(' ', '.', strtolower($name)).'@example.test',
            'password' => 'not-used-for-login', 'role' => $role, 'status' => true,
        ]);
    }

    protected function rating(int $id, User $user, array $attributes = []): void
    {
        DB::table('claim_ratings')->insert(array_merge([
            'id' => $id, 'user_id' => $user->id, 'status' => 'published',
            'is_public' => true, 'rating_score' => .8, 'created_at' => now(),
        ], $attributes));
    }
}
