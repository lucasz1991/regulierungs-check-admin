<?php

namespace Tests\Feature;

use App\Jobs\ProcessMailJob;
use App\Livewire\Admin\Contacts\SearchContactsForm;
use App\Livewire\Admin\ManageContacts;
use App\Livewire\Admin\Users;
use App\Models\Contact;
use App\Models\Mail;
use App\Models\User;
use DOMDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerMailContactsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('CREATE TABLE customers (id INTEGER PRIMARY KEY, user_id INTEGER, deleted_at TEXT)');
        DB::statement('CREATE TABLE activity_log (id INTEGER PRIMARY KEY, causer_id INTEGER, created_at TEXT)');
        DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, role TEXT, status INTEGER, created_at TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE contacts (id INTEGER PRIMARY KEY, name TEXT, email TEXT UNIQUE, company TEXT, phone TEXT, address TEXT, city TEXT, postal_code TEXT, country TEXT, category TEXT, website TEXT, additional_data TEXT, mail_permission TEXT DEFAULT "unknown", created_at TEXT, updated_at TEXT)');
        DB::statement('CREATE TABLE mails (id INTEGER PRIMARY KEY, type TEXT DEFAULT "message", status INTEGER, recipients TEXT, content TEXT, dispatch_key TEXT UNIQUE, created_at TEXT, updated_at TEXT)');
        $this->actingAs(User::create(['name' => 'Team', 'email' => 'admin@example.test', 'role' => 'admin', 'password' => 'unused', 'status' => true]));
        Queue::fake();
    }

    public function test_user_form_stores_exactly_the_selected_channel_and_queues_base_job(): void
    {
        $customer = User::create(['name' => 'Alex', 'email' => 'alex@example.test', 'role' => 'guest', 'password' => 'unused', 'status' => true]);
        foreach (['mail', 'message', 'both'] as $type) {
            Livewire::test(Users::class)->call('openMailModal', $customer->id)
                ->assertSet('mailType', 'mail')->assertSee('Interne Systemnachricht')->assertSee('Systemnachricht und E-Mail')->assertSee('Nur E-Mail')
                ->set('mailType', $type)->set('mailSubject', 'Ein Test')->set('mailHeader', 'Hallo')->set('mailBody', 'Text')
                ->call('previewMail')->assertHasNoErrors()->assertSee('Versandvorschau')
                ->assertSee($type === 'message' ? 'Internes Postfach' : '/site-images/mail/globe.png')
                ->call('sendMail')->assertHasNoErrors();
            $this->assertSame($type, Mail::latest('id')->firstOrFail()->type);
        }
        Queue::assertPushed(ProcessMailJob::class, 3);
        Queue::assertPushedOn('kundenmail', ProcessMailJob::class);
    }

    public static function buttonLabels(): array
    {
        return [
            'custom email label' => ['mail', '  Schäden & <Details>  ', 'https://example.test/details', 'Schäden & <Details>'],
            'default combined label' => ['both', '   ', 'https://example.test/details', 'Weiter'],
            'label without link' => ['mail', 'Details ansehen', '', null],
        ];
    }

    /** @dataProvider buttonLabels */
    public function test_composer_previews_and_persists_the_optional_button_label(string $channel, string $input, string $link, ?string $expectedLabel): void
    {
        $customer = User::create(['name' => 'Alex', 'email' => 'alex@example.test', 'role' => 'guest', 'password' => 'unused', 'status' => true]);
        $form = Livewire::test(Users::class)->call('openMailModal', $customer->id)
            ->assertSeeHtml('wire:model="mailButtonLabel"')
            ->set('mailType', $channel)->set('mailSubject', 'Unterlagen')->set('mailHeader', 'Hallo Alex')->set('mailBody', 'Deine Nachricht.')
            ->set('mailLink', $link)->set('mailButtonLabel', $input)->call('previewMail')->assertHasNoErrors();

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$form->html(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $preview = $document->getElementsByTagName('iframe')->item(0)->getAttribute('srcdoc');
        if ($expectedLabel === null) {
            $this->assertStringNotContainsString($input, $preview);
            $this->assertStringNotContainsString('Falls der Button', $preview);
        } else {
            $this->assertStringContainsString('>'.e($expectedLabel), $preview);
            $this->assertSame(2, substr_count($preview, 'href="'.$link.'"'));
        }

        $form->call('sendMail')->assertHasNoErrors()->assertSet('mailButtonLabel', '');
        $content = Mail::firstOrFail()->content;
        $this->assertSame($link, $content['link']);
        $this->assertSame($link !== '' && trim($input) !== '' ? trim($input) : null, $content['button_label'] ?? null);
        Queue::assertPushed(ProcessMailJob::class, 1);
    }

    public function test_long_button_label_is_rejected_before_scheduling(): void
    {
        $customer = User::create(['name' => 'Alex', 'email' => 'alex@example.test', 'role' => 'guest', 'password' => 'unused', 'status' => true]);
        $form = Livewire::test(Users::class)->call('openMailModal', $customer->id)
            ->set('mailSubject', 'Unterlagen')->set('mailHeader', 'Hallo Alex')->set('mailBody', 'Deine Nachricht.')
            ->set('mailLink', 'https://example.test/details')->set('mailButtonLabel', str_repeat('a', 81));
        $form->call('previewMail')->assertHasErrors(['mailButtonLabel' => 'max'])->assertSet('mailPreview', false);
        $form->call('sendMail')->assertHasErrors(['mailButtonLabel' => 'max']);
        $this->assertSame(0, Mail::count());
        Queue::assertNothingPushed();
    }

    public function test_bulk_send_uses_all_filtered_results_and_enqueues_each_recipient_once(): void
    {
        $recipientCount = 53;
        for ($i = 0; $i < $recipientCount; $i++) {
            User::create(['name' => 'Kurs '.$i, 'email' => 'kurs'.$i.'@example.test', 'role' => 'guest', 'password' => 'unused', 'status' => true]);
        }
        User::create(['name' => 'Weitere Person', 'email' => 'other@example.test', 'role' => 'guest', 'password' => 'unused', 'status' => true]);

        $form = Livewire::test(Users::class)->set('search', 'Kurs')->call('toggleSelectAll')->call('openMailModal')
            ->set('mailType', 'message')->set('mailSubject', 'Termin')->set('mailBody', 'Eine interne Nachricht.')
            ->set('mailLink', 'ignored-for-internal-messages')->set('mailButtonLabel', str_repeat('a', 81))
            ->assertDontSeeHtml('wire:model="mailButtonLabel"');
        $duplicate = clone $form->instance();
        $form->call('sendMail')->assertHasNoErrors();
        $duplicate->sendMail();

        $orders = Mail::orderBy('id')->get();
        $this->assertSame([50, 3], $orders->map(fn ($order) => count($order->recipients))->all());
        $this->assertSame('', $orders->first()->content['link']);
        $this->assertArrayNotHasKey('button_label', $orders->first()->content);
        $addresses = $orders->flatMap(fn ($order) => array_column($order->recipients, 'email'));
        $this->assertCount($recipientCount, $addresses->unique());
        $this->assertNotContains('other@example.test', $addresses);
        Queue::assertPushed(ProcessMailJob::class, $recipientCount);
    }

    public function test_contact_can_be_updated_then_mailed_only_after_release(): void
    {
        $page = Livewire::test(ManageContacts::class)->call('create')->set('form.name', 'Beispiel')->set('form.email', 'TEST@example.test')
            ->call('save')->assertHasNoErrors();
        $contact = Contact::firstOrFail();
        $blocked = Contact::create(['name' => 'Gesperrt', 'email' => 'blocked@example.test', 'mail_permission' => 'blocked']);
        $page->call('edit', $contact->id)->set('form.mail_permission', 'allowed')->call('save')->assertHasNoErrors()
            ->set('selected', [$contact->id, $blocked->id])->call('openMailModal')->assertSee('1 erreichbarer Empfänger')
            ->set('mailType', 'both')->set('mailSubject', 'Test')->set('mailHeader', 'Hallo')->set('mailBody', 'Text')
            ->call('sendMail')->assertHasErrors('mailType')->set('mailType', 'mail')->call('sendMail')->assertHasNoErrors();
        $this->assertSame('test@example.test', Mail::firstOrFail()->recipients[0]['email']);
        $this->assertCount(1, Mail::firstOrFail()->recipients);
        Queue::assertPushed(ProcessMailJob::class, 1);
    }

    public function test_saved_html_imports_available_contact_fields_without_granting_mail_permission(): void
    {
        $html = '<article class="mod-Treffer"><h2>Firma Beispiel</h2><address>Hafen 1</address><a href="mailto:info@example.test">E-Mail</a></article>';
        $file = UploadedFile::fake()->createWithContent('treffer.html', $html);
        Livewire::test(SearchContactsForm::class)->call('openModal')->set('file', $file)->call('analyzeFile')
            ->assertHasNoErrors()->assertSee('Firma Beispiel')->call('saveContacts')->assertHasNoErrors();
        $this->assertDatabaseHas('contacts', ['email' => 'info@example.test', 'address' => 'Hafen 1', 'mail_permission' => 'unknown']);
        Queue::assertNothingPushed();
    }

    public function test_retry_only_requeues_failed_recipients_and_repeated_click_is_harmless(): void
    {
        $mail = Mail::withoutEvents(fn () => Mail::create(['type' => 'mail', 'status' => false, 'content' => ['subject' => 'Test'],
            'recipients' => [['state' => 'sent', 'status' => true], ['state' => 'failed', 'status' => false], ['state' => 'uncertain', 'status' => false]]]));
        $this->assertSame(1, $mail->retryFailed());
        $this->assertSame(0, $mail->retryFailed());
        Queue::assertPushed(ProcessMailJob::class, fn ($job) => $job->recipientIndex === 1 && $job->mail->is($mail));
        Queue::assertPushed(ProcessMailJob::class, 1);
        $this->assertSame(['sent', 'pending', 'uncertain'], array_column($mail->fresh()->recipients, 'state'));
    }

    public function test_import_preview_and_repeat_do_not_overwrite_release_or_create_duplicates(): void
    {
        $csv = "Name;E-Mail;Firma\nMuster;info@example.test;Firma\nMuster;INFO@example.test;Firma\nDefekt;ungueltig;Firma\nOhne Mail;;Firma";
        $upload = fn () => UploadedFile::fake()->createWithContent('kontakte.csv', $csv);
        Livewire::test(SearchContactsForm::class)->call('openModal')->set('file', $upload())->call('analyzeFile')
            ->assertHasNoErrors()->assertSee('Doppelte Zeile')->call('saveContacts');
        $this->assertSame(2, Contact::count());
        $contact = Contact::whereNotNull('email')->firstOrFail();
        $contact->update(['mail_permission' => 'blocked']);
        Livewire::test(SearchContactsForm::class)->call('openModal')->set('file', $upload())->call('analyzeFile')
            ->set('updateExisting', true)->call('saveContacts')->assertHasNoErrors();
        $this->assertSame(2, Contact::count());
        $this->assertSame('blocked', $contact->fresh()->mail_permission);
        Queue::assertNothingPushed();
    }
}
