<div class="rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-center justify-between gap-3">
        <div><h3 class="font-semibold">Newsletter abonnieren</h3><p class="break-all text-sm text-gray-600">{{ $subscription->email ?: 'Keine E-Mail-Adresse hinterlegt' }}</p></div>
        @can('newsletter.manage')
            <button type="button" role="switch" aria-label="Newsletter abonnieren" aria-checked="{{ $subscription->enabled ? 'true' : 'false' }}" wire:click="change('toggle')" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full py-2 text-sm font-semibold">
                <span>{{ $subscription->enabled ? 'Ein' : 'Aus' }}</span><span aria-hidden="true" style="display:inline-flex;width:2.75rem;padding:3px;border-radius:2rem;background:{{ $subscription->enabled ? '#146c60' : '#697b78' }};justify-content:{{ $subscription->enabled ? 'flex-end' : 'flex-start' }}"><span style="height:1.25rem;width:1.25rem;background:white;border-radius:50%"></span></span>
            </button>
        @else
            <strong class="text-sm">{{ $subscription->enabled ? 'Ein' : 'Aus' }}</strong>
        @endcan
    </div>
    <details class="mt-3 text-sm" @if($errors->has('proof')) open @endif>
        <summary class="cursor-pointer">Details</summary>
        <p class="mt-2">{{ $eligible ? 'Versand freigegeben' : ($subscription->consented_at ? 'Versand gesperrt oder Zuordnung unklar' : 'Keine Versandfreigabe') }}</p>
        <p>Begrüßung: {{ $subscription->neutral_greeting ? 'Neutral' : 'Persönlich' }} @can('newsletter.manage')<button type="button" class="ml-1 underline" wire:click="change('greeting')">Ändern</button>@endcan</p>
        @can('newsletter.manage')
        @if(! $subscription->consented_at || ! $subscription->enabled)
        <label class="mt-3 block">Nachweis der Newsletter-Bestellung
            <input type="text" maxlength="500" placeholder="Quelle und Zeitpunkt des Bestellwunsches" class="mt-1 block w-full rounded border-gray-300" wire:model="proof">
        </label>
        @error('proof')<p role="alert" class="text-red-700">{{ $message }}</p>@enderror
        <button type="button" class="mt-2 underline" wire:click="change('{{ $subscription->enabled ? 'allow' : 'toggle' }}')">{{ $subscription->enabled ? 'Freigabe dokumentieren' : 'Auf Wunsch einschalten' }}</button>
        @endif
        @endcan
        @if($events->isNotEmpty())<h4 class="mt-3 font-semibold">Letzte Änderungen</h4><ul>
        @foreach($events as $event)<li class="border-t py-1">{{ \Illuminate\Support\Carbon::parse($event->created_at)->format('d.m.Y H:i') }} · {{ ['consent'=>'Bestellung dokumentiert','unsubscribe'=>'Abgemeldet','address_changed'=>'Adresse geändert','preference'=>'Begrüßung geändert'][$event->event] ?? $event->event }} @if($event->actor_id)· {{ $event->source === 'admin_profile' ? 'Admin' : 'Konto' }} #{{ $event->actor_id }}@endif @if($event->note)<span class="block text-gray-600">{{ $event->note }}</span>@endif</li>@endforeach</ul>@endif
    </details>
    @error('newsletter')<p class="text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
    @if($notice)<p class="mt-2 text-sm" role="status">{{ $notice }}</p>@endif
</div>
