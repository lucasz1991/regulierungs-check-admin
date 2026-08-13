@php
    $maskName = static function (?string $name): string {
        if (!$name) return 'Nicht belegt';
        $parts = preg_split('/\s+/', trim($name));
        return collect($parts)->map(fn ($part) => mb_substr($part, 0, 1).str_repeat('*', max(2, mb_strlen($part) - 1)))->join(' ');
    };
    $maskEmail = static function (?string $email): string {
        if (!$email || !str_contains($email, '@')) return 'Nicht belegt';
        [$local, $domain] = explode('@', $email, 2);
        return mb_substr($local, 0, 1).str_repeat('*', max(3, mb_strlen($local) - 1)).'@'.$domain;
    };
    $statusLabels = ['issued' => 'QR erstellt', 'bound' => 'Zugeordnet', 'confirmed' => 'Bestaetigt', 'fulfilled' => 'Ausgegeben', 'disputed' => 'Beanstandet', 'expired' => 'Abgelaufen', 'cancelled' => 'Storniert'];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-700">Physisches Gluecksrad</p><h1 class="mt-1 text-3xl font-bold tracking-tight">Gewinn sicher erfassen</h1><p class="mt-2 max-w-2xl text-sm text-slate-600">Preis waehlen, QR-Code gemeinsam mit dem Teilnehmer scannen und die Bestaetigung abwarten.</p></div>
        @if($campaigns->count() > 1)<select wire:change="chooseCampaign($event.target.value)" class="rounded-xl border-slate-300 bg-white text-sm">@foreach($campaigns as $entry)<option value="{{ $entry->id }}" @selected($entry->id === $campaignId)>{{ $entry->name }}</option>@endforeach</select>@endif
    </div>

    @if(session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-900">{{ session('status') }}</div>@endif
    @error('promotion')<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>@enderror

    @if($issuedQrSvg)
        <section class="grid gap-6 overflow-hidden rounded-3xl border border-teal-200 bg-white p-6 shadow-xl shadow-teal-900/10 lg:grid-cols-[380px_1fr]">
            <div class="mx-auto w-full max-w-[360px]">{!! $issuedQrSvg !!}</div>
            <div class="flex flex-col justify-center"><span class="w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Noch nicht zugeordnet</span><h2 class="mt-4 text-2xl font-bold">Jetzt gemeinsam scannen</h2><p class="mt-2 text-slate-600">Der Teilnehmer scannt diesen Einmal-QR-Code, meldet sich an und bestaetigt den angezeigten Gewinn. Danach ist der Code nicht erneut nutzbar.</p><p class="mt-4 break-all rounded-lg bg-slate-50 p-3 font-mono text-xs text-slate-500">{{ $issuedUrl }}</p><button wire:click="clearIssuedCode" class="mt-5 w-fit rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">Anzeige schliessen</button></div>
        </section>
    @elseif($campaign)
        <section><h2 class="mb-3 text-lg font-bold">{{ $campaign->name }}: Ergebnis am Rad</h2><div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">@forelse($campaign->prizes as $prize)<button wire:click="issueWin({{ $prize->id }})" wire:loading.attr="disabled" @disabled(!$prize->hasQuota()) class="group rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-teal-400 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"><span class="text-xs font-bold uppercase tracking-wider text-teal-700">{{ $prize->fulfillment_mode === 'onsite_staff' ? 'Vor Ort' : 'Externe Erfuellung' }}</span><span class="mt-2 block text-lg font-bold">{{ $prize->name }}</span><span class="mt-3 block text-xs text-slate-500">Verfuegbar: {{ max(0, $prize->quota - $prize->reserved_count) }} von {{ $prize->quota }}</span></button>@empty<p class="text-sm text-slate-500">Keine aktiven Preise konfiguriert.</p>@endforelse</div></section>
    @else
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900">Aktuell ist keine aktive Promotion-Kampagne verfuegbar.</div>
    @endif

    @can('promotion.wins.view_all')
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-bold">Gewinnvorgaenge</h2>
                <p class="mt-1 text-xs text-slate-500">Mitarbeitersicht ist datensparsam maskiert.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Teilnahme-ID</th>
                            <th class="px-5 py-3">Teilnehmer</th>
                            <th class="px-5 py-3">Kampagne / Gewinn</th>
                            <th class="px-5 py-3">Erfasst von</th>
                            <th class="px-5 py-3">Status / Zeiten</th>
                            <th class="px-5 py-3">Aktion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($wins as $win)
                            @php($participant = $win->participation?->user)
                            <tr>
                                <td class="px-5 py-3 font-mono text-xs">{{ $win->participation?->public_id }}</td>
                                <td class="px-5 py-3">
                                    <strong class="block">{{ $showFullIdentity ? ($participant?->name ?? 'Nicht belegt') : $maskName($participant?->name) }}</strong>
                                    <span class="text-xs text-slate-500">{{ $showFullIdentity ? ($participant?->email ?? '') : $maskEmail($participant?->email) }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <strong class="block">{{ $win->campaign->name }}</strong>
                                    <span class="text-xs text-slate-500">{{ $win->prize_name_snapshot }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    {{ $win->issuedBy?->name ?? 'System' }}
                                    <span class="mt-1 block text-xs text-slate-500">{{ $win->created_at?->format('d.m.Y H:i') }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <strong class="block">{{ $statusLabels[$win->status] ?? $win->status }}</strong>
                                    <span class="text-xs text-slate-500">
                                        Zugeordnet: {{ $win->bound_at?->format('d.m.Y H:i') ?? '–' }}<br>
                                        Bestaetigt: {{ $win->confirmed_at?->format('d.m.Y H:i') ?? '–' }}<br>
                                        Ausgegeben: {{ $win->fulfilled_at?->format('d.m.Y H:i') ?? '–' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    @if($win->status === 'confirmed' && $participant?->hasVerifiedEmail())
                                        @can($win->fulfillment_mode_snapshot === 'external_admin' ? 'promotion.fulfillment.external' : 'promotion.fulfillment.onsite')
                                            <button wire:click="fulfill({{ $win->id }})" wire:confirm="Gewinn wirklich als ausgegeben markieren? Dieser Schritt ist nicht wiederholbar." class="font-semibold text-teal-700">Ausgeben</button>
                                        @endcan
                                    @else
                                        <span class="text-xs text-slate-400">Gesperrt</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-500">Noch keine zugeordneten Gewinne.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($wins, 'links'))
                <div class="border-t border-slate-100 px-5 py-3">{{ $wins->links() }}</div>
            @endif
        </section>
    @endcan
</div>
