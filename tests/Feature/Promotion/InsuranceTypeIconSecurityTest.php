<?php

namespace Tests\Feature\Promotion;

use App\Livewire\Admin\RatingStructure\InsuranceTypes\InsuranceTypesCreateEdit;
use App\Livewire\Admin\RatingStructure\InsuranceTypes\InsuranceTypesList;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class InsuranceTypeIconSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_live_svg_preview_never_emits_active_markup_and_save_rejects_it(): void
    {
        $admin = $this->admin();
        $payload = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">'
            .'<script>alert(2)</script><path fill="url(javascript:alert(3))" d="M1 1"/></svg>';

        $component = Livewire::actingAs($admin)
            ->test(InsuranceTypesCreateEdit::class)
            ->set('name', 'Unsicher')
            ->set('icon_type', 'svg')
            ->set('icon', $payload)
            ->assertDontSee('<script', false)
            ->assertDontSee('onload="alert', false)
            ->assertDontSee('fill="url(javascript:', false);

        $component
            ->call('save')
            ->assertHasErrors(['icon']);

        $this->assertDatabaseMissing('insurance_types', ['name' => 'Unsicher']);
    }

    public function test_fontawesome_preview_and_save_reject_attribute_injection(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(InsuranceTypesCreateEdit::class)
            ->set('name', 'Unsichere Klasse')
            ->set('icon_type', 'fontawesome')
            ->set('icon', 'fas fa-shield-alt" onmouseover="alert(1)')
            ->assertDontSee('onmouseover="alert', false);

        $component
            ->call('save')
            ->assertHasErrors(['icon']);

        $this->assertDatabaseMissing('insurance_types', ['name' => 'Unsichere Klasse']);
    }

    public function test_legacy_malicious_database_icons_are_fail_closed_in_the_raw_admin_list(): void
    {
        DB::table('insurance_types')->insert([
            [
                'name' => 'Legacy SVG',
                'slug' => 'legacy-svg',
                'icon_type' => 'svg',
                'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><script>alert(1)</script></foreignObject></svg>',
                'is_active' => true,
                'order_column' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Legacy Font',
                'slug' => 'legacy-font',
                'icon_type' => 'fontawesome',
                'icon_svg' => 'fas fa-shield-alt" onmouseover="alert(2)',
                'is_active' => true,
                'order_column' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Livewire::actingAs($this->admin())
            ->test(InsuranceTypesList::class)
            ->assertSee('Legacy SVG')
            ->assertSee('Legacy Font')
            ->assertDontSee('<foreignObject', false)
            ->assertDontSee('<script', false)
            ->assertDontSee('onmouseover="alert', false);
    }

    private function admin(): User
    {
        return User::create([
            'name' => uniqid('Icon Admin ', true),
            'email' => uniqid('icon-admin-', true).'@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
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

        Schema::create('insurance_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->longText('icon_svg')->nullable();
            $table->string('icon_type')->default('svg');
            $table->boolean('is_active')->default(true);
            $table->integer('order_column')->nullable();
            $table->timestamps();
        });

        Schema::create('insurances', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('insurance_subtypes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('insurance_insurance_type', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('insurance_id');
            $table->unsignedBigInteger('insurance_type_id');
            $table->integer('order_column')->default(0);
        });

        Schema::create('insurance_type_insurance_subtype', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('insurance_type_id');
            $table->unsignedBigInteger('insurance_subtype_id');
            $table->integer('order_id')->default(0);
        });
    }
}

