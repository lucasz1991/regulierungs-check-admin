<?php

namespace Tests\Feature;

use App\Livewire\Admin\NewsletterPreference;
use App\Livewire\Admin\NewsletterSubscribers;
use App\Models\User;
use App\Services\NewsletterSubscriptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNewsletterPreferencesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, email_verified_at TEXT, password TEXT, status INTEGER, role TEXT, deleted_at TEXT, created_at TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE contacts (id INTEGER PRIMARY KEY, name TEXT, email TEXT, mail_permission TEXT)');
        DB::statement('CREATE TABLE newsletter_subscriptions (id INTEGER PRIMARY KEY, source_key TEXT UNIQUE, source_type TEXT, source_id INTEGER, email TEXT, address_token TEXT, enabled INTEGER DEFAULT 1, neutral_greeting INTEGER DEFAULT 0, consented_at TEXT, consent_source TEXT, consent_note TEXT, version INTEGER DEFAULT 1, created_at TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE newsletter_suppressions (email TEXT PRIMARY KEY, created_at TEXT)');
        DB::statement('CREATE TABLE newsletter_preference_events (id INTEGER PRIMARY KEY, subscription_id INTEGER, email TEXT, event TEXT, source TEXT, actor_id INTEGER, note TEXT, created_at TEXT)');
        DB::table('contacts')->insert(['id' => 12, 'name' => 'Kim', 'email' => 'kim@example.test', 'mail_permission' => 'allowed']);
    }

    public function test_admin_can_document_consent_in_contact_profile_and_filter_distributions(): void
    {
        $actor = User::create(['name' => 'Team', 'email' => 'admin@example.test', 'role' => 'admin', 'password' => 'unused', 'status' => true]);
        $this->actingAs($actor);
        $component = Livewire::test(NewsletterPreference::class, ['type' => 'contact', 'recordId' => 12]);
        $component->assertSee('Keine Versandfreigabe')->call('change', 'allow')->assertHasErrors('proof')
            ->set('proof', 'Ausdrückliche Bestellung am 27.11.2026 per E-Mail')->call('change', 'allow')->assertHasNoErrors()->assertSee('Versand freigegeben');
        Livewire::test(NewsletterSubscribers::class)->set('status', 'allowed')->assertSee('kim@example.test')->assertDontSee('admin@example.test');
        $component->call('change', 'toggle')->assertSee('Abgemeldet');
        $this->assertDatabaseHas('newsletter_suppressions', ['email' => 'kim@example.test']);
        $this->assertSame(0, app(NewsletterSubscriptions::class)->eligibleQuery()->count());
    }

    public function test_viewer_cannot_mutate_subscription_by_calling_livewire_action(): void
    {
        $user = User::create(['name' => 'Leser', 'email' => 'leser@example.test', 'role' => 'guest', 'password' => 'unused', 'status' => true]);
        Gate::before(fn ($actor, $ability) => in_array($ability, ['newsletter.view', 'contacts.manage']) ? true : null);
        $this->actingAs($user);
        Livewire::test(NewsletterPreference::class, ['type' => 'contact', 'recordId' => 12])->assertDontSee('Freigabe dokumentieren')
            ->call('change', 'toggle')->assertForbidden();
        $this->assertSame(0, DB::table('newsletter_preference_events')->count());
    }
}
