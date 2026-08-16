@php
    $outcomeLabels = ['prize' => 'Gewinn', 'no_win' => 'Niete', 'retry' => 'Zusatzdreh', 'quota_reroll' => 'Kontingent-Neudrehung'];
    $mailLabels = ['pending' => 'Versand offen', 'sent' => 'Versendet', 'failed' => 'Fehlgeschlagen', 'not_required' => 'Keine E-Mail'];
    $ticketStatusLabels = ['ready' => 'Bereit', 'active' => 'Am Rad', 'completed' => 'Abgeschlossen', 'cancelled' => 'Storniert'];
@endphp

<div x-data="{ tab: 'overview' }" class="space-y-6">
    <header class="flex flex-col gap-4 rounded-3xl bg-[#07363c] p-6 text-white shadow-xl shadow-teal-950/10 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-teal-200">Promotion</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight">Glücksrad verwalten</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-teal-50/75">Kampagne veröffentlichen, Gewinne und Mengen pflegen und jeden beobachteten Dreh nachvollziehen.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <select wire:change="selectCampaign($event.target.value)" class="min-w-56 rounded-xl border-white/15 bg-white/10 text-sm text-white focus:border-teal-300 focus:ring-teal-300">
                <option class="text-slate-900" value="">Kampagne wählen</option>
                @foreach ($campaigns as $entry)
                    <option class="text-slate-900" value="{{ $entry->id }}" @selected($entry->id === $campaignId)>{{ $entry->name }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="newCampaign" class="rounded-xl bg-[#ffd166] px-5 py-3 text-sm font-black text-[#07363c] hover:bg-[#ffdc82]">Neue Kampagne</button>
        </div>
    </header>

    @if (session('status'))
        <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-900">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900">{{ $errors->first() }}</div>
    @endif

    <nav aria-label="Promotion-Bereiche" class="grid grid-cols-2 gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm sm:grid-cols-4">
        @foreach (['overview' => 'Übersicht', 'campaign' => 'Kampagne', 'prizes' => 'Gewinne', 'history' => 'Verlauf'] as $key => $label)
            <button type="button" x-on:click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-teal-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'" class="rounded-xl px-4 py-3 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-teal-200">{{ $label }}</button>
        @endforeach
    </nav>

    <section x-show.important="tab === 'overview'" x-cloak class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Tickets</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $ticketCount }}</p><p class="mt-1 text-xs text-slate-500">{{ $readyTicketCount }} bereit zum Scan</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Drehungen heute</p><p class="mt-2 text-3xl font-black text-slate-950">{{ $todayTurnCount }}</p><p class="mt-1 text-xs text-slate-500">inklusive Zusatz- und Neudrehungen</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Öffentliche Kampagne</p><p class="mt-2 truncate text-lg font-black text-slate-950">{{ $campaigns->firstWhere('is_public', true)?->name ?? 'Keine' }}</p><p class="mt-1 text-xs text-slate-500">Es kann immer nur eine veröffentlicht sein.</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Drehplatz</p><p class="mt-2 text-lg font-black {{ $selectedCampaign?->promotionState?->active_turn_id ? 'text-amber-700' : 'text-emerald-700' }}">{{ $selectedCampaign?->promotionState?->active_turn_id ? 'Belegt' : 'Frei' }}</p><p class="mt-1 text-xs text-slate-500">Pro Kampagne genau ein aktiver Teilnehmer.</p></article>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-teal-700">Dauerhafter Poster-Link</p>
                <h2 class="mt-2 text-xl font-black text-slate-950">Ein QR-Code für alle Kampagnen</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Dieser Link bleibt dauerhaft gleich. Welche Kampagne dort erscheint, steuern Sie ausschließlich über „Veröffentlichen“.</p>
                @if ($posterUrl)
                    <div x-data="{ copied: false }" class="mt-5 flex flex-col gap-2 sm:flex-row">
                        <code class="min-w-0 flex-1 overflow-x-auto rounded-xl bg-slate-950 px-4 py-3 text-sm text-teal-200">{{ $posterUrl }}</code>
                        <button type="button" x-on:click="navigator.clipboard.writeText(@js($posterUrl)); copied = true; setTimeout(() => copied = false, 1600)" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold text-slate-800 hover:bg-slate-50" x-text="copied ? 'Kopiert' : 'Link kopieren'"></button>
                    </div>
                @else
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Zuerst die öffentliche Teilnehmer-Adresse unter Einstellungen speichern.</div>
                @endif
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-black text-slate-950">Kontingente</h2>
                <div class="mt-4 space-y-4">
                    @forelse ($selectedCampaign?->prizes?->where('outcome_type.value', 'prize') ?? collect() as $prize)
                        @php($percent = $prize->quota > 0 ? min(100, round(($prize->awarded_count / $prize->quota) * 100)) : 0)
                        <div>
                            <div class="flex justify-between gap-3 text-xs"><span class="truncate font-bold text-slate-700">{{ $prize->name }}</span><span class="text-slate-500">{{ $prize->awarded_count }}/{{ $prize->quota }}</span></div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $percent >= 100 ? 'bg-red-500' : 'bg-teal-600' }}" style="width: {{ $percent }}%"></div></div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Noch keine Gewinne angelegt.</p>
                    @endforelse
                </div>
            </article>
        </div>
    </section>

    <section x-show.important="tab === 'campaign'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-xl font-black text-slate-950">Kampagnendaten</h2><p class="mt-1 text-sm text-slate-500">Ansehen auf der Seite, bearbeiten im Standardmodal.</p></div>
            @if ($selectedCampaign)
                <button type="button" wire:click="editCampaign({{ $selectedCampaign->id }})" class="rounded-xl bg-teal-700 px-5 py-3 text-sm font-black text-white hover:bg-teal-800">Kampagne bearbeiten</button>
            @else
                <button type="button" wire:click="newCampaign" class="rounded-xl bg-teal-700 px-5 py-3 text-sm font-black text-white hover:bg-teal-800">Kampagne anlegen</button>
            @endif
        </div>
        @if ($selectedCampaign)
            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-4"><span class="text-xs font-bold uppercase tracking-wide text-slate-400">Name</span><strong class="mt-2 block text-slate-950">{{ $selectedCampaign->name }}</strong><span class="mt-1 block font-mono text-xs text-slate-500">{{ $selectedCampaign->code }}</span></div>
                <div class="rounded-2xl bg-slate-50 p-4"><span class="text-xs font-bold uppercase tracking-wide text-slate-400">Zeitraum</span><strong class="mt-2 block text-sm text-slate-950">{{ $selectedCampaign->starts_at?->format('d.m.Y H:i') ?? 'Offen' }}</strong><span class="mt-1 block text-xs text-slate-500">bis {{ $selectedCampaign->ends_at?->format('d.m.Y H:i') ?? 'ohne Ende' }}</span></div>
                <div class="rounded-2xl bg-slate-50 p-4"><span class="text-xs font-bold uppercase tracking-wide text-slate-400">Status</span><strong class="mt-2 block {{ $selectedCampaign->is_active ? 'text-emerald-700' : 'text-slate-600' }}">{{ $selectedCampaign->is_active ? 'Aktiv' : 'Inaktiv' }}</strong><span class="mt-1 block text-xs text-slate-500">{{ $selectedCampaign->is_public ? 'Auf /gluecksrad veröffentlicht' : 'Nicht veröffentlicht' }}</span></div>
                <div class="rounded-2xl bg-slate-50 p-4"><span class="text-xs font-bold uppercase tracking-wide text-slate-400">Landingpage</span><strong class="mt-2 block text-slate-950">{{ $selectedCampaign->landing_headline ?: 'Keine Überschrift' }}</strong><span class="mt-1 block text-xs text-slate-500">Texte und Regeln im Modal bearbeiten</span></div>
            </div>
        @else
            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Noch keine Kampagne ausgewählt.</div>
        @endif
    </section>

    <section x-show.important="tab === 'prizes'" x-cloak>
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <header class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div><h2 class="text-xl font-black text-slate-950">Gewinne dieser Kampagne</h2><p class="mt-1 text-sm text-slate-500">Diese Gewinne stehen Mitarbeitern bei der Ergebniserfassung zur Auswahl.</p></div>
                <button type="button" wire:click="createPrize" @disabled(! $campaignId) class="rounded-xl bg-teal-700 px-5 py-3 text-sm font-black text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-40">Gewinn hinzufügen</button>
            </header>
            <div class="divide-y divide-slate-100">
                @forelse ($selectedCampaign?->prizes?->where('outcome_type.value', 'prize') ?? collect() as $prize)
                    <article wire:key="admin-prize-{{ $prize->id }}" class="grid gap-4 p-5 sm:grid-cols-[auto_1fr_auto] sm:items-center sm:px-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-100 text-teal-800">
                            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12v9H4v-9M2 7h20v5H2zM12 7v14M12 7H7.5a2.5 2.5 0 1 1 0-5C10 2 12 7 12 7Zm0 0h4.5a2.5 2.5 0 1 0 0-5C14 2 12 7 12 7Z"/></svg>
                        </span>
                        <div><strong class="text-slate-950">{{ $prize->name }}</strong><p class="mt-1 text-xs text-slate-500">{{ $prize->awarded_count }} von {{ $prize->quota }} vergeben · {{ max(0, $prize->quota - $prize->awarded_count) }} verfügbar</p></div>
                        <div class="flex gap-3 text-sm"><button wire:click="editPrize({{ $prize->id }})" class="font-bold text-teal-700 hover:text-teal-900">Bearbeiten</button><button wire:click="deletePrize({{ $prize->id }})" wire:confirm="Diesen unbenutzten Gewinn wirklich löschen?" class="font-bold text-red-600 hover:text-red-800">Löschen</button></div>
                    </article>
                @empty
                    <p class="p-8 text-center text-sm text-slate-500">Noch keine Gewinne angelegt.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section x-show.important="tab === 'history'" x-cloak class="space-y-5">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <input wire:model.live.debounce.350ms="historySearch" class="rounded-xl border-slate-300 text-sm xl:col-span-2" placeholder="Teilnahme-ID, Name, E-Mail, Ergebnis">
                <select wire:model.live="historyOutcome" class="rounded-xl border-slate-300 text-sm"><option value="">Alle Ergebnisse</option>@foreach($outcomeLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                <select wire:model.live="historyStaffId" class="rounded-xl border-slate-300 text-sm"><option value="">Alle Mitarbeiter</option>@foreach($staff as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select>
                <input type="date" wire:model.live="historyFrom" class="rounded-xl border-slate-300 text-sm" aria-label="Von Datum">
                <input type="date" wire:model.live="historyTo" class="rounded-xl border-slate-300 text-sm" aria-label="Bis Datum">
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Zeit / Ticket</th><th class="px-5 py-3">Teilnehmer</th><th class="px-5 py-3">Ergebnis</th><th class="px-5 py-3">Mitarbeiter</th><th class="px-5 py-3">Mail / Ausgabe</th><th class="px-5 py-3">Aktion</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($results as $result)
                            @php($outcome = $result->outcome_type_snapshot->value)
                            @php($mailStatus = $result->mail_status->value)
                            @php($mode = $result->fulfillment_mode_snapshot instanceof \BackedEnum ? $result->fulfillment_mode_snapshot->value : $result->fulfillment_mode_snapshot)
                            <tr class="{{ $result->superseded_at ? 'bg-slate-50 text-slate-500' : '' }}">
                                <td class="whitespace-nowrap px-5 py-4"><span class="block text-xs text-slate-500">{{ $result->recorded_at?->format('d.m.Y H:i:s') }}</span><span class="mt-1 block font-mono text-xs font-bold text-teal-800">{{ $result->ticket?->participation?->public_id }}</span></td>
                                <td class="px-5 py-4"><strong class="block text-slate-900">{{ $result->ticket?->user?->name ?? 'Nicht belegt' }}</strong><span class="text-xs">{{ $result->ticket?->user?->email }}</span></td>
                                <td class="px-5 py-4">
                                    <strong class="block">{{ $result->label_snapshot }}</strong>
                                    <span class="text-xs">
                                        {{ $outcomeLabels[$outcome] ?? $outcome }}
                                        @if ($result->corrects_result_id)
                                            · Korrektur
                                        @endif
                                        @if ($result->superseded_at)
                                            · ersetzt
                                        @endif
                                    </span>
                                </td>
                                <td class="px-5 py-4">{{ $result->recordedBy?->name ?? 'System' }}</td>
                                <td class="px-5 py-4"><span class="block text-xs font-semibold {{ $mailStatus === 'failed' ? 'text-red-700' : 'text-slate-600' }}">{{ $mailLabels[$mailStatus] ?? $mailStatus }}</span><span class="mt-1 block text-xs text-slate-500">{{ $result->fulfilled_at ? 'Ausgegeben '.$result->fulfilled_at->format('d.m.Y H:i') : 'Nicht ausgegeben' }}</span></td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col items-start gap-2 text-xs font-bold">
                                        @if ($result->is_final && ! $result->superseded_at && ! $result->fulfilled_at)
                                            <button wire:click="prepareCounterbook({{ $result->id }})" class="text-amber-700 hover:text-amber-900">Gegenbuchen</button>
                                            @if ($outcome === 'prize')
                                                <button wire:click="fulfill({{ $result->id }})" wire:confirm="Ausgabe wirklich einmalig dokumentieren?" class="text-teal-700 hover:text-teal-900">{{ $mode === 'external_admin' ? 'Extern zugestellt' : 'Vor Ort ausgegeben' }}</button>
                                            @endif
                                        @endif
                                        @if ($mailStatus === 'failed')
                                            <button wire:click="resendMail({{ $result->id }})" class="text-red-700 hover:text-red-900">Mail erneut senden</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">Keine Drehungen für diese Filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($legacyWins->isNotEmpty())
            <details class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><summary class="cursor-pointer font-bold text-slate-800">Legacy-Gewinnvorgänge ({{ $legacyWins->count() }})</summary><div class="mt-4 divide-y divide-slate-100">@foreach($legacyWins as $legacy)<div class="grid gap-2 py-3 text-sm sm:grid-cols-3"><span class="font-mono text-xs">{{ $legacy->participation?->public_id }}</span><span>{{ $legacy->prize_name_snapshot }}</span><span class="sm:text-right">{{ $legacy->status }} · {{ $legacy->created_at?->format('d.m.Y H:i') }}</span></div>@endforeach</div></details>
        @endif
    </section>

    <x-dialog-modal id="promotion-campaign-modal" wire:model.live="showCampaignModal" :maxWidth="'4xl'">
        <x-slot name="title">{{ $campaignId ? 'Kampagne bearbeiten' : 'Neue Kampagne' }}</x-slot>
        <x-slot name="content">
            <form id="promotion-campaign-form" wire:submit="saveCampaign" class="space-y-5 text-left">
                <div class="grid gap-5 md:grid-cols-2">
                    <div><label for="campaign-name" class="block text-sm font-bold text-slate-700">Name</label><input id="campaign-name" wire:model="campaignName" class="mt-1 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600">@error('campaignName')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="campaign-code" class="block text-sm font-bold text-slate-700">Interner Code</label><input id="campaign-code" wire:model="campaignCode" class="mt-1 block w-full rounded-xl border-slate-300 font-mono uppercase focus:border-teal-600 focus:ring-teal-600" placeholder="STRASSE26">@error('campaignCode')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="campaign-start" class="block text-sm font-bold text-slate-700">Start</label><input id="campaign-start" type="datetime-local" wire:model="campaignStartsAt" class="mt-1 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600">@error('campaignStartsAt')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="campaign-end" class="block text-sm font-bold text-slate-700">Ende</label><input id="campaign-end" type="datetime-local" wire:model="campaignEndsAt" class="mt-1 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600">@error('campaignEndsAt')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                </div>
                <div><label for="campaign-headline" class="block text-sm font-bold text-slate-700">Landingpage-Überschrift</label><input id="campaign-headline" wire:model="campaignLandingHeadline" class="mt-1 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600" placeholder="Dreh dein Glück!">@error('campaignLandingHeadline')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div class="grid gap-5 lg:grid-cols-2">
                    <div><label for="campaign-text" class="block text-sm font-bold text-slate-700">Erklärung</label><textarea id="campaign-text" rows="6" wire:model="campaignLandingText" class="mt-1 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600"></textarea>@error('campaignLandingText')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                    <div><label for="campaign-rules" class="block text-sm font-bold text-slate-700">Teilnahmebedingungen</label><textarea id="campaign-rules" rows="6" wire:model="campaignRulesText" class="mt-1 block w-full rounded-xl border-slate-300 focus:border-teal-600 focus:ring-teal-600"></textarea>@error('campaignRulesText')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                </div>
                <div class="rounded-2xl border border-teal-200 bg-teal-50 p-4 text-sm leading-6 text-teal-950">Sie pflegen ausschließlich Gewinnbezeichnung und Menge. Ist ein Gewinn aufgebraucht, weist die Mitarbeiterkonsole automatisch darauf hin.</div>
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="flex gap-3 rounded-xl bg-slate-50 p-4"><input type="checkbox" wire:model="campaignIsActive" class="mt-1 rounded text-teal-700 focus:ring-teal-600"><span><strong class="block text-sm">Kampagne aktiv</strong><span class="mt-1 block text-xs text-slate-500">Zeitraum und interne Nutzung freigeben.</span></span></label>
                    <label class="flex gap-3 rounded-xl bg-teal-50 p-4"><input type="checkbox" wire:model="campaignIsPublic" class="mt-1 rounded text-teal-700 focus:ring-teal-600"><span><strong class="block text-sm">Auf /gluecksrad veröffentlichen</strong><span class="mt-1 block text-xs text-slate-500">Entfernt automatisch jede andere öffentliche Kampagne.</span></span></label>
                </div>
                @error('campaignIsPublic')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" wire:click="closeCampaignModal" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Abbrechen</button>
            <button type="submit" form="promotion-campaign-form" wire:loading.attr="disabled" wire:target="saveCampaign" class="ml-3 rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-black text-white hover:bg-teal-800 disabled:opacity-50"><span wire:loading.remove wire:target="saveCampaign">Kampagne speichern</span><span wire:loading wire:target="saveCampaign">Wird gespeichert …</span></button>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal id="promotion-prize-modal" wire:model.live="showPrizeModal" :maxWidth="'lg'">
        <x-slot name="title">{{ $prizeId ? 'Gewinn bearbeiten' : 'Gewinn hinzufügen' }}</x-slot>
        <x-slot name="content">
            <form id="promotion-prize-form" wire:submit="savePrize" class="space-y-4 text-left">
                <p class="text-sm text-slate-600">Nur Gewinnbezeichnung und Gesamtmenge werden benötigt.</p>
                @error('prize')
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert">{{ $message }}</div>
                @enderror
                <div><label for="prize-name" class="block text-sm font-bold text-slate-700">Gewinnbezeichnung</label><input id="prize-name" wire:model="prizeName" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600" placeholder="z. B. Amazon-Gutschein 20 €">@error('prizeName')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="prize-quantity" class="block text-sm font-bold text-slate-700">Menge</label><input id="prize-quantity" type="number" min="1" wire:model="prizeQuota" class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">@error('prizeQuota')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" wire:click="closePrizeModal" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Abbrechen</button>
            <button type="submit" form="promotion-prize-form" wire:loading.attr="disabled" wire:target="savePrize" class="ml-3 rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-black text-white hover:bg-teal-800 disabled:opacity-50"><span wire:loading.remove wire:target="savePrize">Gewinn speichern</span><span wire:loading wire:target="savePrize">Wird gespeichert …</span></button>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal id="promotion-counterbook-modal" wire:model.live="showCounterbookModal" :maxWidth="'lg'">
        <x-slot name="title">Ergebnis gegenbuchen</x-slot>
        <x-slot name="content">
            <form id="promotion-counterbook-form" wire:submit="counterbook" class="space-y-4 text-left">
                <p class="text-sm leading-6 text-slate-600">Der vorherige Wert bleibt sichtbar. Kontingente werden transaktional korrigiert und der Teilnehmer erhält eine Korrekturmail.</p>
                <div><label for="counterbook-prize" class="block text-sm font-bold text-slate-700">Neues finales Ergebnis</label><select id="counterbook-prize" wire:model="counterbookPrizeId" class="mt-1 block w-full rounded-xl border-slate-300 text-sm"><option value="">Bitte Ergebnis wählen</option>@foreach($selectedCampaign?->prizes?->where('is_active', true)->whereIn('outcome_type.value', ['prize','no_win']) ?? collect() as $field)<option value="{{ $field->id }}">{{ $field->name }}</option>@endforeach</select>@error('counterbookPrizeId')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="counterbook-reason" class="block text-sm font-bold text-slate-700">Pflichtgrund</label><textarea id="counterbook-reason" wire:model="counterbookReason" rows="4" class="mt-1 block w-full rounded-xl border-slate-300 text-sm" placeholder="Mindestens 10 Zeichen"></textarea>@error('counterbookReason')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" wire:click="cancelCounterbook" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Abbrechen</button>
            <button type="submit" form="promotion-counterbook-form" wire:loading.attr="disabled" wire:target="counterbook" class="ml-3 rounded-lg bg-amber-700 px-5 py-2.5 text-sm font-black text-white hover:bg-amber-800 disabled:opacity-50"><span wire:loading.remove wire:target="counterbook">Gegenbuchung speichern</span><span wire:loading wire:target="counterbook">Wird gespeichert …</span></button>
        </x-slot>
    </x-dialog-modal>
</div>
