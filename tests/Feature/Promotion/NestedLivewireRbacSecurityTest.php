<?php

namespace Tests\Feature\Promotion;

use App\Livewire\Admin\Charts\ActiveUsers;
use App\Livewire\Admin\Cms\ProjectSettingsManager;
use App\Livewire\Admin\Config\BasicSettings;
use App\Livewire\Admin\Config\GrapesJsSettings;
use App\Livewire\Admin\Contacts\SearchContactsForm;
use App\Livewire\Admin\RatingStructure\Insurance\CreateEdit as InsuranceCreateEdit;
use App\Livewire\Admin\RatingStructure\Insurance\ShowModal as InsuranceShowModal;
use App\Livewire\Admin\RatingStructure\InsuranceList;
use App\Livewire\Admin\RatingStructure\InsuranceSubtypes\InsuranceSubtypesCreateEdit;
use App\Livewire\Admin\RatingStructure\InsuranceSubtypes\InsuranceSubtypesList;
use App\Livewire\Admin\RatingStructure\InsuranceTypes\InsuranceTypesCreateEdit;
use App\Livewire\Admin\RatingStructure\InsuranceTypes\InsuranceTypesList;
use App\Livewire\Admin\RatingStructure\Questionnaire\QuestionnaireEdit;
use App\Livewire\Admin\RatingStructure\Questionnaire\QuestionnaireList;
use App\Livewire\Admin\RatingStructure\RatingQuestion\RatingQuestionCreateEdit;
use App\Livewire\Admin\RatingStructure\RatingQuestion\RatingQuestionList;
use App\Livewire\Admin\RatingStructure\Settings\AiScoringSettings;
use App\Livewire\Admin\RatingStructure\Settings\ScoringConfig;
use App\Livewire\Admin\Reviews\AnonymousReviewForm;
use App\Livewire\Admin\Reviews\EditClaimRatingModal;
use App\Livewire\Admin\UserProfile\ShelfRentals;
use App\Livewire\Concerns\RequiresRbacPermission;
use App\Livewire\Tools\FilePools\FilePreviewModal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class NestedLivewireRbacSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_every_sensitive_nested_component_declares_its_exact_permission(): void
    {
        $expected = [
            BasicSettings::class => 'settings.manage',
            GrapesJsSettings::class => 'settings.manage',
            ProjectSettingsManager::class => 'content.pagebuilder.manage',
            SearchContactsForm::class => 'contacts.manage',
            AnonymousReviewForm::class => 'reviews.manage',
            EditClaimRatingModal::class => 'reviews.manage',
            InsuranceList::class => 'ratings.structure.manage',
            InsuranceCreateEdit::class => 'ratings.structure.manage',
            InsuranceShowModal::class => 'ratings.structure.manage',
            InsuranceTypesList::class => 'ratings.structure.manage',
            InsuranceTypesCreateEdit::class => 'ratings.structure.manage',
            InsuranceSubtypesList::class => 'ratings.structure.manage',
            InsuranceSubtypesCreateEdit::class => 'ratings.structure.manage',
            RatingQuestionList::class => 'ratings.structure.manage',
            RatingQuestionCreateEdit::class => 'ratings.structure.manage',
            QuestionnaireList::class => 'ratings.structure.manage',
            QuestionnaireEdit::class => 'ratings.structure.manage',
            ScoringConfig::class => 'ratings.structure.manage',
            AiScoringSettings::class => 'ratings.structure.manage',
            FilePreviewModal::class => 'tasks.manage',
            ShelfRentals::class => 'users.manage',
            ActiveUsers::class => 'users.manage',
        ];

        foreach ($expected as $componentClass => $permission) {
            $this->assertContains(
                RequiresRbacPermission::class,
                class_uses_recursive($componentClass),
                $componentClass.' must authorize before mount and on every hydration.'
            );

            $method = new ReflectionMethod($componentClass, 'requiredRbacPermission');
            $this->assertSame($permission, $method->invoke(new $componentClass), $componentClass);
        }
    }

    public function test_nested_components_reject_an_unauthorized_initial_mount_before_their_own_queries(): void
    {
        [, $staff] = $this->staffWithPermissions([]);

        $components = [
            BasicSettings::class,
            GrapesJsSettings::class,
            ProjectSettingsManager::class,
            SearchContactsForm::class,
            AnonymousReviewForm::class,
            EditClaimRatingModal::class,
            InsuranceList::class,
            InsuranceCreateEdit::class,
            InsuranceShowModal::class,
            InsuranceTypesList::class,
            InsuranceTypesCreateEdit::class,
            InsuranceSubtypesList::class,
            InsuranceSubtypesCreateEdit::class,
            RatingQuestionList::class,
            RatingQuestionCreateEdit::class,
            QuestionnaireList::class,
            QuestionnaireEdit::class,
            ScoringConfig::class,
            AiScoringSettings::class,
            FilePreviewModal::class,
            ShelfRentals::class,
            ActiveUsers::class,
        ];

        foreach ($components as $componentClass) {
            Livewire::actingAs($staff)
                ->test($componentClass)
                ->assertForbidden();
        }
    }

    public function test_revocation_blocks_real_existing_nested_livewire_snapshots(): void
    {
        $snapshots = [
            [SearchContactsForm::class, 'contacts.manage', 'showModal'],
            [RatingQuestionCreateEdit::class, 'ratings.structure.manage', 'showModal'],
            [FilePreviewModal::class, 'tasks.manage', 'open'],
        ];

        foreach ($snapshots as [$componentClass, $permission, $property]) {
            [$team, $staff] = $this->staffWithPermissions([$permission]);
            $component = Livewire::actingAs($staff)
                ->test($componentClass)
                ->assertOk();

            $team->forceFill(['rbac_permissions' => []])->save();
            $staff->unsetRelation('currentTeam');
            $staff->unsetRelation('teams');
            auth()->setUser($staff);

            $component->set($property, true)->assertForbidden();
        }
    }

    public function test_inactive_global_admin_cannot_reuse_a_settings_child_snapshot(): void
    {
        $admin = $this->user('Admin', 'nested-admin@example.test', 'admin');
        $component = Livewire::actingAs($admin)
            ->test(BasicSettings::class)
            ->assertOk();

        $admin->forceFill(['status' => false])->save();
        auth()->setUser($admin);

        $component->set('companyName', 'Unauthorized change')->assertForbidden();

        $this->assertDatabaseMissing('settings', [
            'type' => 'base',
            'key' => 'company_name',
        ]);
    }

    /** @param list<string> $permissions
     * @return array{Team, User}
     */
    private function staffWithPermissions(array $permissions): array
    {
        $owner = $this->user('Owner', uniqid('nested-owner-', true).'@example.test', 'admin');
        $team = new Team;
        $team->forceFill([
            'user_id' => $owner->id,
            'name' => uniqid('Nested editorial ', true),
            'personal_team' => false,
            'rbac_permissions' => array_fill_keys($permissions, true),
        ])->save();

        $staff = $this->user('Staff', uniqid('nested-staff-', true).'@example.test', 'staff');
        $staff->forceFill(['current_team_id' => $team->id])->save();
        $team->users()->attach($staff->id, ['role' => 'team_access']);

        return [$team, $staff->fresh()];
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

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['type', 'key']);
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

