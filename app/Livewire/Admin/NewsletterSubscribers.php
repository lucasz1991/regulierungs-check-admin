<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Services\NewsletterSubscriptions;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class NewsletterSubscribers extends Component
{
    use RequiresRbacPermission, WithPagination;

    public string $search = '';

    public string $status = '';

    public ?int $openId = null;

    protected function requiredRbacPermission(): string
    {
        return 'newsletter.view';
    }

    public function mount(NewsletterSubscriptions $subscriptions): void
    {
        $subscriptions->syncSources();
    }

    public function updated($key): void
    {
        if (in_array($key, ['search', 'status'], true)) {
            $this->openId = null;
            $this->resetPage();
        }
    }

    public function render(NewsletterSubscriptions $service)
    {
        $eligible = $service->eligibleQuery()->select('s.id');
        $rows = DB::table('newsletter_subscriptions as n')->select('n.*')
            ->selectSub((clone $eligible)->whereColumn('s.id', 'n.id'), 'sendable')
            ->where(function ($q) {
                $q->where('n.source_type', 'public')
                    ->orWhere(fn ($q) => $q->where('n.source_type', 'user')->whereExists(fn ($q) => $q->selectRaw('1')->from('users')->whereColumn('users.id', 'n.source_id')->whereNull('users.deleted_at')))
                    ->orWhere(fn ($q) => $q->where('n.source_type', 'contact')->whereExists(fn ($q) => $q->selectRaw('1')->from('contacts')->whereColumn('contacts.id', 'n.source_id')));
            })
            ->when(trim($this->search) !== '', fn ($q) => $q->where('n.email', 'like', '%'.trim($this->search).'%'))
            ->when($this->status === 'allowed', fn ($q) => $q->whereIn('n.id', $eligible))
            ->when($this->status === 'off', fn ($q) => $q->where('n.enabled', false))
            ->when($this->status === 'waiting', fn ($q) => $q->where('n.enabled', true)->whereNotIn('n.id', $eligible))
            ->orderBy('n.email')->orderBy('n.id')->paginate(25);

        return view('livewire.admin.newsletter-subscribers', ['rows' => $rows])->layout('layouts.master');
    }
}
