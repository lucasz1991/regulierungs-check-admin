<div class="mx-auto max-w-5xl space-y-5 p-5">
    @section('title','Newsletter-Abonnements')
    <header class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-semibold">Abonnements</h1>
    @can('newsletter.manage')<a class="underline" href="{{ url('/admin/newsletter') }}">Zu den Ausgaben</a>@endcan</header>
    <div class="flex flex-wrap gap-3">
        <input wire:model.live.debounce.350ms="search" class="rounded border-gray-300" placeholder="E-Mail suchen" aria-label="E-Mail suchen">
        <select wire:model.live="status" class="rounded border-gray-300" aria-label="Versandstatus"><option value="">Alle Abonnements</option><option value="allowed">Versandbereit</option><option value="waiting">Offene oder gesperrte Freigabe</option><option value="off">Ausgeschaltet</option></select>
    </div>
    <p class="text-sm">{{ $rows->total() }} Abonnements</p>
    <div class="divide-y rounded-lg border bg-white">
    @forelse($rows as $row)
    <div class="p-3" wire:key="abo-{{ $row->id }}">
        <div class="flex flex-wrap items-center justify-between gap-2"><div><strong class="break-all">{{ $row->email ?: 'Keine gültige Adresse' }}</strong>
            <p class="text-sm text-gray-600">{{ ['public'=>'Öffentliches Formular','contact'=>'Kontakt','user'=>'Benutzerkonto'][$row->source_type] }} · {{ $row->enabled ? 'Abo ein' : 'Abo aus' }} · {{ $row->sendable ? 'Versandbereit' : ($row->consented_at ? 'Versand gesperrt' : 'Keine Versandfreigabe') }}</p></div>
            @if($row->source_type === 'public' || \Illuminate\Support\Facades\Gate::allows($row->source_type === 'user' ? 'users.manage' : 'contacts.manage'))
                <button type="button" wire:click="$set('openId', {{ $openId === $row->id ? 'null' : $row->id }})" aria-expanded="{{ $openId === $row->id ? 'true':'false' }}" class="text-sm underline">Details</button>
            @endif
        </div>
        @if($openId === $row->id && ($row->source_type === 'public' || \Illuminate\Support\Facades\Gate::allows($row->source_type === 'user' ? 'users.manage' : 'contacts.manage')))
            <div class="mt-3"><livewire:admin.newsletter-preference :type="$row->source_type" :record-id="$row->source_id ?? $row->id" :key="'preference-'.$row->id" /></div>
            @if($row->source_type === 'user')
                <a href="{{ route('admin.user-profile', ['userId' => $row->source_id, 'tab' => 'newsletter']) }}" class="mt-2 inline-block text-sm underline">Newsletter im Benutzerprofil öffnen</a>
            @endif
        @endif
    </div>
    @empty <p class="p-4">Keine passenden Abonnements.</p>@endforelse
    </div>
    {{ $rows->links() }}
</div>
