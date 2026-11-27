<?php

namespace Tests\Feature;

use App\Http\Controllers\MediaController;
use App\Jobs\ProcessMailJob;
use App\Models\NewsletterCampaign;
use App\Models\User;
use App\Services\NewsletterSubscriptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewsletterCampaignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, role TEXT, status INTEGER, created_at TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE posts (id INTEGER PRIMARY KEY, type TEXT, title TEXT, excerpt TEXT, slug TEXT, images TEXT, cover_image TEXT, published INTEGER, published_at TEXT)');
        DB::statement('CREATE TABLE settings (id INTEGER PRIMARY KEY, type TEXT, key TEXT, value TEXT)');
        DB::statement('CREATE TABLE newsletter_campaigns (id INTEGER PRIMARY KEY, draft_key TEXT UNIQUE, post_id INTEGER, article_title TEXT, name TEXT, subject TEXT, preheader TEXT, body TEXT, link TEXT, button_label TEXT, greeting TEXT, audience TEXT DEFAULT "all", image_source TEXT, image_url TEXT, image_alt TEXT, image_width INTEGER, image_height INTEGER, focus_x INTEGER DEFAULT 50, focus_y INTEGER DEFAULT 50, include_image INTEGER DEFAULT 0, status TEXT DEFAULT "draft", scheduled_at TEXT, revision INTEGER DEFAULT 1, created_at TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE newsletter_deliveries (id INTEGER PRIMARY KEY, campaign_id INTEGER, subscription_id INTEGER, email TEXT, user_id INTEGER, contact_id INTEGER, status TEXT, queued_at TEXT, created_at TEXT, updated_at TEXT, UNIQUE(campaign_id,email))');
        DB::statement('CREATE TABLE mails (id INTEGER PRIMARY KEY, type TEXT, recipients TEXT, status INTEGER, content TEXT, dispatch_key TEXT UNIQUE, created_at TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE newsletter_subscriptions (id INTEGER PRIMARY KEY, source_type TEXT, source_id INTEGER, email TEXT, enabled INTEGER DEFAULT 1, consented_at TEXT DEFAULT "2026-01-01")');
        DB::statement('CREATE TABLE newsletter_suppressions (email TEXT PRIMARY KEY)');
        config(['services.newsletter.base_url' => 'http://127.0.0.1:17657']);
        $this->actingAs(User::withoutEvents(fn () => User::create(['name' => 'Redaktion', 'email' => 'admin@example.test', 'password' => 'unused', 'role' => 'admin', 'status' => true])));
        $this->mock(NewsletterSubscriptions::class, function ($mock) {
            $mock->allows('syncSources');
            $mock->allows('eligibleQuery')->andReturnUsing(fn () => DB::table('newsletter_subscriptions as s')
                ->select('s.*')->where('s.enabled', true)->whereNotNull('s.consented_at')->whereNotNull('s.email'));
        });
        Queue::fake();
    }

    public function test_news_entry_reuses_the_same_draft_and_refresh_is_explicit(): void
    {
        DB::table('posts')->insert(['id' => 8, 'type' => 'news', 'title' => 'Erster Titel', 'excerpt' => 'Eigener Kurztext', 'images' => json_encode([['path' => 'uploads/news/demo.png', 'alt' => 'Beispiel', 'sort' => 0]])]);
        $this->post(route('admin.newsletter.create'), ['post_id' => 8])->assertRedirect();
        $this->post(route('admin.newsletter.create'), ['post_id' => 8])->assertRedirect();
        $campaign = NewsletterCampaign::sole();
        $this->assertSame('Eigener Kurztext', $campaign->body);
        $this->assertTrue($campaign->include_image);
        $this->assertSame('http://127.0.0.1:17657/news/lesen/8', $campaign->link);
        DB::table('posts')->where('id', 8)->update(['excerpt' => 'Neuer Teaser', 'title' => 'Neuer Titel']);
        $this->assertSame('Eigener Kurztext', $campaign->fresh()->body);
        $this->post(route('admin.newsletter.source', $campaign), ['action' => 'refresh', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame('Neuer Teaser', $campaign->fresh()->body);
        $this->post(route('admin.newsletter.source', $campaign), ['action' => 'detach', 'revision' => 1])->assertSessionHasErrors('revision');
        $this->assertSame(8, $campaign->fresh()->post_id);
        $this->post(route('admin.newsletter.source', $campaign), ['action' => 'detach', 'revision' => 2])->assertSessionHasNoErrors();
        $this->assertNull($campaign->fresh()->post_id);
        Queue::assertNothingPushed();
    }

    public function test_source_image_is_frozen_and_news_header_independent_of_subject(): void
    {
        $campaign = NewsletterCampaign::create(['name' => 'Ausgabe', 'subject' => 'Titel', 'article_title' => 'News-Titel', 'post_id' => 9, 'image_source' => 'uploads/news/motiv.png', 'body' => 'Teaser']);
        $this->mock(MediaController::class)->shouldReceive('newsletterImage')->once()->with(9, 50, 40, 'uploads/news/motiv.png')
            ->andReturn(['url' => 'https://example.test/storage/mail/a.jpg', 'width' => 940, 'height' => 470, 'alt' => 'Motiv', 'bytes' => 77722]);
        $this->post(route('admin.newsletter.save', $campaign), ['revision' => 1, 'name' => 'Ausgabe', 'subject' => 'Betreff', 'body' => 'Mein Teaser', 'audience' => 'all', 'include_image' => 1, 'focus_x' => 50, 'focus_y' => 40, 'image_alt' => 'Motiv', 'after' => 'preview'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.newsletter.preview', $campaign));
        $campaign->refresh();
        $content = $campaign->content();
        $this->assertSame('News-Titel', $content['newsletter']['title']);
        $this->assertSame('Betreff', $content['subject']);
        $this->assertSame(470, $content['newsletter']['image']['height']);
        $this->assertSame('Mein Teaser', $content['body']);
    }

    public function test_withdrawn_news_is_reopened_from_source_unless_a_newer_draft_exists(): void
    {
        DB::table('posts')->insert(['id' => 8, 'type' => 'news', 'title' => 'News', 'excerpt' => 'Teaser']);
        $campaign = NewsletterCampaign::create(['name' => 'News', 'subject' => 'News', 'body' => 'Text', 'post_id' => 8, 'status' => 'scheduled']);
        $this->post(route('admin.newsletter.planning', $campaign), ['revision' => 1, 'action' => 'withdraw'])->assertSessionHasNoErrors();
        $this->post(route('admin.newsletter.create'), ['post_id' => 8])->assertRedirect(route('admin.newsletter.show', $campaign));
        $campaign->refresh()->update(['draft_key' => null, 'status' => 'scheduled']);
        $other = NewsletterCampaign::create(['name' => 'Neue Ausgabe', 'subject' => 'News', 'body' => 'Text', 'post_id' => 8, 'draft_key' => 'news:8']);
        $this->post(route('admin.newsletter.planning', $campaign), ['revision' => 2, 'action' => 'withdraw'])->assertSessionHasNoErrors();
        $this->post(route('admin.newsletter.create'), ['post_id' => 8])->assertRedirect(route('admin.newsletter.show', $other));
        $this->assertNull($campaign->fresh()->draft_key);
        $this->assertDatabaseCount('newsletter_campaigns', 2);
    }

    public function test_schedule_freezes_recipient_set_and_rejects_stale_and_ineligible_campaigns(): void
    {
        $this->post(route('admin.newsletter.create'))->assertRedirect();
        $campaign = NewsletterCampaign::sole();
        $this->post(route('admin.newsletter.schedule', $campaign), ['revision' => 1])->assertSessionHasErrors('subject');
        $this->post(route('admin.newsletter.save', $campaign), ['revision' => 1, 'name' => 'Frei', 'subject' => 'Betreff', 'body' => 'Nachricht', 'audience' => 'contacts'])->assertSessionHasNoErrors();
        $this->post(route('admin.newsletter.schedule', $campaign), ['revision' => 2])->assertSessionHasErrors('audience');
        foreach ([['contact', 5, 'kontakt@example.test'], ['contact', 6, 'kontakt@example.test'], ['user', 7, 'kunde@example.test']] as [$type,$id,$email]) {
            DB::table('newsletter_subscriptions')->insert(['source_type' => $type, 'source_id' => $id, 'email' => $email]);
        }
        $this->post(route('admin.newsletter.schedule', $campaign), ['revision' => 2])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('newsletter_campaigns', ['id' => $campaign->id, 'status' => 'scheduled', 'revision' => 3]);
        $this->assertDatabaseCount('newsletter_deliveries', 1);
        $this->assertDatabaseHas('newsletter_deliveries', ['contact_id' => 5, 'email' => 'kontakt@example.test', 'status' => 'pending']);
        $this->post(route('admin.newsletter.schedule', $campaign), ['revision' => 2])->assertSessionHasErrors('revision');
        $this->post(route('admin.newsletter.planning', $campaign), ['revision' => 3, 'action' => 'withdraw'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('newsletter_deliveries', 0);
        $this->assertDatabaseHas('newsletter_campaigns', ['id' => $campaign->id, 'status' => 'draft', 'revision' => 4]);
        Queue::assertNothingPushed();
    }

    public function test_testmail_enqueues_only_explicit_address_and_uses_neutral_preference_links(): void
    {
        $campaign = NewsletterCampaign::create(['name' => 'Los', 'subject' => 'Neu', 'body' => 'Hallo']);
        $input = ['revision' => 1, 'test_email' => 'redaktion@example.test', 'test_key' => Str::uuid()->toString(), 'profile' => 'neutral'];
        $this->post(route('admin.newsletter.test', $campaign), $input)->assertSessionHasNoErrors();
        $this->post(route('admin.newsletter.test', $campaign), $input)->assertSessionHasNoErrors();
        $mail = \App\Models\Mail::sole();
        $this->assertSame('redaktion@example.test', $mail->recipients[0]['email']);
        $this->assertSame('/newsletter/testansicht', parse_url($mail->content['newsletter']['settings_url'], PHP_URL_PATH));
        $this->assertSame('Hallo,', $mail->content['header']);
        $this->assertSame('draft', $campaign->fresh()->status);
        Queue::assertPushed(ProcessMailJob::class, 1);
    }

    public function test_editor_and_preview_render_through_the_admin_routes_without_dispatch(): void
    {
        $campaign = NewsletterCampaign::create(['name' => 'Ausgabe im Entwurf', 'subject' => 'Neuigkeiten', 'body' => 'Ein gespeicherter Teaser']);
        $this->get(route('admin.newsletter.index'))->assertOk()->assertSee('Freie Ausgabe erstellen')->assertSee('Ausgabe im Entwurf');
        $this->get(route('admin.newsletter.show', $campaign))->assertOk()->assertSee('Speichern und Vorschau öffnen');
        $this->get(route('admin.newsletter.preview', $campaign))->assertOk()->assertSee('Newsletter-Einstellungen ändern')->assertSee('Versand verbindlich freigeben');
        Queue::assertNothingPushed();
    }

    public function test_plain_preview_matches_the_mail_order_and_shows_editor_text_safely(): void
    {
        $campaign = NewsletterCampaign::create([
            'name' => 'Juni', 'subject' => 'Neuigkeiten', 'post_id' => 8, 'article_title' => 'Grün & günstig',
            'body' => 'Prüfung <b>für Sie</b> & mehr.', 'button_label' => 'Für mich öffnen',
            'link' => 'https://example.test/news?a=1&b=2',
        ]);
        $response = $this->get(route('admin.newsletter.preview', $campaign))->assertOk();
        $text = $response->viewData('text');
        $after = 0;
        foreach (['Regulierungs-CHECK · News', 'Hallo Alex,', 'Grün & günstig', 'Prüfung <b>für Sie</b> & mehr.',
            'Für mich öffnen', $campaign->link, 'Newsletter-Einstellungen ändern', '/newsletter/testansicht',
            'Viele Grüße', 'Regulierungs-Check RCQ GmbH', 'Geschäftsführer Birkan Özülkü', 'HRB 193808',
            'Impressum: https://www.regulierungs-check.de/imprint', 'Datenschutz: https://www.regulierungs-check.de/privacypolicy',
            'Newsletter abbestellen', '/newsletter/testansicht'] as $part) {
            $position = strpos($text, $part, $after);
            $this->assertNotFalse($position, $part);
            $after = $position + strlen($part);
        }
        $response->assertSee(e($text).'</pre>', false);
        $this->assertStringNotContainsString('&amp;', $text);
    }

    public function test_release_preview_groups_excluded_profiles_for_the_selected_audience(): void
    {
        $campaign = NewsletterCampaign::create(['name' => 'Kontakte', 'subject' => 'Info', 'body' => 'Juni', 'audience' => 'contacts']);
        foreach ([['contact', 'on@example.test', 1, '2026-01-01'], ['contact', 'off@example.test', 0, null],
            ['contact', 'proof@example.test', 1, null], ['user', 'user@example.test', 0, null]] as [$type, $email, $enabled, $proof]) {
            DB::table('newsletter_subscriptions')->insert(['source_type' => $type, 'email' => $email, 'enabled' => $enabled, 'consented_at' => $proof]);
        }
        $response = $this->get(route('admin.newsletter.preview', $campaign))->assertOk()
            ->assertSee('2 ausgeschlossene Profile')->assertSee('Abgemeldet oder gesperrt · 1')->assertSee('Ohne Versandnachweis · 1');
        $this->assertEquals(['eligible' => 1, 'disabled' => 1, 'proof' => 1], $response->viewData('counts')->all());
    }
}
