<?php

namespace App\Livewire\Admin;

use App\Services\NewsletterSubscriptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NewsletterPreference extends Component
{
    #[Locked]
    public int $subscriptionId;

    #[Locked]
    public int $version;

    public string $proof = '';

    public string $notice = '';

    private function authorizeRecord(string $type): void
    {
        abort_unless(Gate::any(['newsletter.manage', 'newsletter.view']), 403);
        if ($type !== 'public') {
            Gate::authorize($type === 'user' ? 'users.manage' : 'contacts.manage');
        }
    }

    public function mount(NewsletterSubscriptions $service, string $type, int $recordId): void
    {
        $this->authorizeRecord($type);
        $row = $type === 'public'
            ? DB::table('newsletter_subscriptions')->where('source_type', 'public')->find($recordId)
            : $service->forSource($type, $recordId);
        abort_unless($row, 404);
        $this->subscriptionId = $row->id;
        $this->version = $row->version;
    }

    private function record(NewsletterSubscriptions $service): object
    {
        $row = DB::table('newsletter_subscriptions')->find($this->subscriptionId);
        abort_unless($row, 404);
        $this->authorizeRecord($row->source_type);

        return $service->current($row->id);
    }

    public function change(string $action, NewsletterSubscriptions $service): void
    {
        Gate::authorize('newsletter.manage');
        abort_unless(in_array($action, ['toggle', 'allow', 'greeting'], true), 422);
        $row = $this->record($service);
        $enabled = $action === 'toggle' ? ! $row->enabled : (bool) $row->enabled;
        if ($enabled && $action !== 'greeting') {
            $this->validate(['proof' => 'required|string|min:5|max:500'], ['proof.required' => 'Bitte den Bestellwunsch mit Quelle und Zeitpunkt dokumentieren.']);
        }
        $proof = $enabled && $action !== 'greeting' ? trim($this->proof) : null;
        $service->update($row->id, $this->version, $enabled, $action === 'greeting' ? ! $row->neutral_greeting : (bool) $row->neutral_greeting,
            'admin_profile', auth()->id(), $proof);
        $this->version = $this->record($service)->version;
        $this->proof = '';
        $this->notice = 'Newsletter-Einstellungen gespeichert.';
    }

    public function render(NewsletterSubscriptions $service)
    {
        $row = $this->record($service);

        return view('livewire.admin.newsletter-preference', ['subscription' => $row,
            'eligible' => $service->eligibleQuery()->where('s.id', $row->id)->exists(),
            'events' => DB::table('newsletter_preference_events')->where('subscription_id', $row->id)->latest('id')->limit(5)->get()]);
    }
}
