@php
    $outcomeStyles = [
        'prize' => 'border-teal-500 bg-teal-500 text-white hover:bg-teal-600 focus-visible:ring-teal-400',
        'no_win' => 'border-slate-700 bg-slate-800 text-white hover:bg-slate-900 focus-visible:ring-slate-500',
        'retry' => 'border-amber-400 bg-amber-300 text-amber-950 hover:bg-amber-400 focus-visible:ring-amber-300',
    ];
    $outcomeLabels = [
        'prize' => 'Gewinn',
        'no_win' => 'Niete',
        'retry' => 'Zusatzdreh',
        'quota_reroll' => 'Neudrehung (Kontingent)',
    ];
    $statusLabels = [
        'active' => 'dreht gerade',
        'completed' => 'abgeschlossen',
        'released' => 'abgebrochen',
    ];
    $prizeOptions = $resultFields->filter(fn ($result) => $result->outcome_type->value === 'prize');
    $operationalOptions = $resultFields->filter(fn ($result) => in_array($result->outcome_type->value, ['no_win', 'retry'], true));
@endphp

<div
    class="space-y-6"
    x-data="promotionScanner($wire)"
    x-init="init()"
    wire:poll.2s.visible
>
    <section class="relative overflow-hidden rounded-[2rem] bg-[#082f35] px-5 py-7 text-white shadow-2xl shadow-teal-950/20 sm:px-8 sm:py-9">
        <div aria-hidden="true" class="absolute -right-16 -top-24 h-72 w-72 rounded-full border-[44px] border-teal-300/10"></div>
        <div aria-hidden="true" class="absolute -bottom-20 right-28 h-44 w-44 rounded-full bg-amber-300/10 blur-3xl"></div>

        <div class="relative grid gap-7 lg:grid-cols-[1fr_auto] lg:items-end">
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-[0.18em] text-teal-200">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-teal-300 shadow-[0_0_0_5px_rgba(94,234,212,0.12)]"></span>
                    Physisches Glücksrad
                    @if ($campaign)
                        <span class="rounded-full bg-white/10 px-3 py-1 tracking-normal text-white">{{ $campaign->name }}</span>
                    @endif
                </div>
                <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Teilnehmer aufrufen</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-teal-50/80 sm:text-base">
                    Persönliches Ticket scannen, Drehung beobachten und das Ergebnis direkt speichern. Der nächste Teilnehmer wird erst danach freigegeben.
                </p>
            </div>

            <button
                type="button"
                x-on:click="show()"
                @disabled(! $newScansAllowed || $activeTurn || $stickerRequired || $scanBlockedByQuota)
                class="group inline-flex min-h-16 items-center justify-center gap-3 rounded-2xl bg-[#ffd166] px-7 py-4 text-base font-black text-[#082f35] shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:bg-[#ffdc82] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-amber-200 disabled:cursor-not-allowed disabled:opacity-50 sm:text-lg"
            >
                <svg aria-hidden="true" class="h-7 w-7 transition group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2" />
                    <path d="M7 8h2v2H7zM15 8h2v2h-2zM7 14h2v2H7zM12 12h2v2h-2zM15 15h2v2h-2z" />
                </svg>
                Nächsten Teilnehmer scannen
            </button>
        </div>
    </section>

    @if (session('status'))
        <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-900">
            {{ session('status') }}
        </div>
    @endif

    @if ($stickerRequired)
        <section class="grid gap-4 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950 shadow-sm sm:grid-cols-[1fr_auto] sm:items-center">
            <div>
                <h2 class="font-black">Erschöpften Gewinn am Rad abkleben</h2>
                <p class="mt-1 text-sm leading-6">Bitte prüfen Sie das physische Glücksrad und kleben Sie alle ausgeschöpften Gewinne sichtbar ab. Erst danach kann der nächste Teilnehmer gestartet werden.</p>
            </div>
            <button
                type="button"
                wire:click="acknowledgeSticker"
                wire:loading.attr="disabled"
                wire:target="acknowledgeSticker"
                class="rounded-xl bg-amber-900 px-5 py-3 text-sm font-black text-white hover:bg-amber-950 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-amber-300 disabled:opacity-50"
            >
                Abkleben bestätigt
            </button>
        </section>
    @endif

    @if ($scanBlockedByQuota)
        <section role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-950 shadow-sm">
            <h2 class="font-black">Neue Drehungen sind gesperrt</h2>
            <p class="mt-1 text-sm leading-6">Mindestens ein Gewinn ist ausgeschöpft. Ein Volladmin muss dessen Menge anpassen oder den Gewinn am physischen Rad entfernen.</p>
        </section>
    @endif

    @if ($campaign && ! $newScansAllowed)
        <section role="alert" class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950 shadow-sm">
            <h2 class="font-black">Neue Scans sind pausiert</h2>
            <p class="mt-1 text-sm leading-6">Die Promotion wurde deaktiviert oder die Kampagne ist inzwischen beendet. Ein bereits aktiver Aufruf bleibt unten sichtbar und kann sicher abgeschlossen oder freigegeben werden.</p>
        </section>
    @endif

    @if (! $campaign)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-950">
            <h2 class="font-bold">Keine öffentliche Kampagne</h2>
            <p class="mt-1 text-sm">Ein Volladmin muss zuerst eine aktive Kampagne veröffentlichen.</p>
        </section>
    @else
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <header class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">Letzte Teilnehmer</h2>
                        <p class="mt-1 text-xs text-slate-500">Automatisch aktualisiert · Kontaktdaten sind für Mitarbeiter maskiert.</p>
                    </div>
                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-teal-50 px-3 py-1.5 text-xs font-bold text-teal-800">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-teal-500 motion-reduce:animate-none"></span>
                        Live
                    </span>
                </header>

                <div class="divide-y divide-slate-100">
                    @forelse ($recentTurns as $turn)
                        @php
                            $ticket = $turn->ticket;
                            $participant = $ticket?->user ?? $ticket?->participation?->user;
                            $result = $turn->effectiveResult ?? $turn->results?->where('is_final', true)->last();
                            $outcome = $result?->outcome_type_snapshot?->value;
                            $fulfillmentMode = $result?->fulfillment_mode_snapshot instanceof \BackedEnum
                                ? $result->fulfillment_mode_snapshot->value
                                : $result?->fulfillment_mode_snapshot;
                            $turnStatus = $turn->status->value;
                        @endphp
                        <article wire:key="promotion-turn-{{ $turn->id }}" class="grid gap-3 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-6">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-teal-800">{{ $ticket?->participation?->public_id ?? '–' }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $turnStatus === 'active' ? 'bg-amber-100 text-amber-900' : ($turnStatus === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600') }}">
                                        {{ $statusLabels[$turnStatus] ?? $turnStatus }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-sm font-bold text-slate-950">{{ $this->displayParticipantName($participant) }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $this->displayParticipantEmail($participant) }}</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="text-sm font-bold text-slate-900">{{ $result?->label_snapshot ?? ($turnStatus === 'active' ? 'Drehung läuft' : 'Kein finales Ergebnis') }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $turn->started_at?->format('d.m.Y H:i:s') ?? '–' }}
                                    @if ($turn->startedBy)
                                        · {{ $turn->startedBy->name }}
                                    @endif
                                </p>
                                @if ($outcome)
                                    <span class="mt-1 inline-block text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $outcomeLabels[$outcome] ?? $outcome }}</span>
                                @endif
                                @if ($result?->fulfilled_at)
                                    <span class="mt-2 block text-xs font-bold text-emerald-700">Ausgabe dokumentiert</span>
                                @elseif ($result?->is_final && ! $result->superseded_at && $outcome === 'prize' && $fulfillmentMode === \App\Models\PromotionPrize::FULFILLMENT_ONSITE)
                                    @can('promotion.fulfillment.onsite')
                                        <button type="button" wire:click="fulfill({{ $result->id }})" wire:confirm="Gewinn jetzt verbindlich als ausgehändigt markieren?" class="mt-2 block text-xs font-bold text-teal-700 hover:text-teal-900 sm:ml-auto">Als ausgehändigt markieren</button>
                                    @endcan
                                @elseif ($result?->is_final && ! $result->superseded_at && $outcome === 'prize' && $fulfillmentMode === \App\Models\PromotionPrize::FULFILLMENT_EXTERNAL && auth()->user()?->isAdmin())
                                    @can('promotion.fulfillment.external')
                                        <button type="button" wire:click="fulfill({{ $result->id }})" wire:confirm="Externe Ausgabe jetzt verbindlich dokumentieren?" class="mt-2 block text-xs font-bold text-teal-700 hover:text-teal-900 sm:ml-auto">Externe Ausgabe dokumentieren</button>
                                    @endcan
                                @endif
                                @if ($result?->is_final && ! $result->superseded_at && ! $result->fulfilled_at && (int) $result->recorded_by === (int) auth()->id() && $turn->completed_at?->gte(now()->subMinutes(10)))
                                    <button type="button" wire:click="prepareCorrection({{ $result->id }})" class="mt-2 block text-xs font-bold text-amber-700 hover:text-amber-900 sm:ml-auto">Ergebnis korrigieren</button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7h8M8 12h5M8 17h3"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-slate-700">Noch kein Teilnehmer gescannt.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="space-y-4">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Heute</p>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-teal-50 p-4">
                            <p class="text-3xl font-black text-teal-900">{{ $todayCompleted }}</p>
                            <p class="mt-1 text-xs font-semibold text-teal-700">abgeschlossen</p>
                        </div>
                        <div class="rounded-2xl bg-slate-100 p-4">
                            <p class="text-3xl font-black text-slate-900">{{ $todayTotal }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-600">aufgerufen</p>
                        </div>
                    </div>
                </section>

                @if ($activeTurn)
                    <section class="rounded-3xl border border-amber-300 bg-amber-50 p-5 shadow-sm">
                        <span class="text-xs font-black uppercase tracking-[0.16em] text-amber-800">Drehplatz belegt</span>
                        <p class="mt-2 font-mono text-sm font-bold text-amber-950">{{ $activeTurn->ticket?->participation?->public_id }}</p>
                        <p class="mt-1 text-sm text-amber-900">{{ $this->displayParticipantName($activeTurn->ticket?->user ?? $activeTurn->ticket?->participation?->user) }}</p>
                        <button
                            type="button"
                            x-on:click='resume(@json($this->turnPayload($activeTurn)))'
                            class="mt-4 w-full rounded-xl bg-amber-900 px-4 py-3 text-sm font-bold text-white hover:bg-amber-950 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-amber-300"
                        >
                            Aktiven Aufruf öffnen
                        </button>
                    </section>
                @endif
            </aside>
        </div>
    @endif

    <template x-teleport="body" wire:ignore>
        <div
            wire:ignore
            x-show.important="open"
            x-cloak
            x-ref="dialog"
            x-on:keydown.escape.window="if (! alertOpen) close()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="promotion-scanner-title"
            x-trap.inert.noscroll="open"
            class="fixed inset-0 z-[10000] flex min-h-dvh flex-col bg-[#031f24] text-white"
        >
            <header class="flex shrink-0 items-center justify-between gap-4 border-b border-white/10 px-4 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-300">{{ $campaign?->name }}</p>
                    <h2 id="promotion-scanner-title" class="mt-1 text-xl font-black" x-text="phase === 'camera' ? 'Teilnehmer-Ticket scannen' : 'Drehung beobachten'"></h2>
                </div>
                <button
                    type="button"
                    x-on:click="close()"
                    x-ref="closeButton"
                    :disabled="busy"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white hover:bg-white/20 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-teal-300 disabled:cursor-wait disabled:opacity-50"
                    aria-label="Scanner schließen"
                >
                    <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </header>

            <p class="sr-only" aria-live="polite" x-text="phase === 'camera' ? (cameraError || 'Kamera bereit. Ticket scannen.') : (participant?.instruction || 'Scan erfolgreich. Teilnehmer darf drehen.')"></p>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <div x-show.important="phase === 'camera'" class="mx-auto flex min-h-full w-full max-w-5xl flex-col px-4 py-5 sm:px-6">
                    <div class="relative min-h-[18rem] flex-1 overflow-hidden rounded-[1.75rem] border border-white/15 bg-black shadow-2xl">
                        <video x-ref="video" wire:ignore autoplay muted playsinline class="h-full min-h-[18rem] w-full object-cover"></video>
                        <div aria-hidden="true" class="pointer-events-none absolute inset-0 grid place-items-center bg-[radial-gradient(circle_at_center,transparent_0,transparent_34%,rgba(0,0,0,.6)_35%)]">
                            <div class="relative aspect-square w-[min(68vw,21rem)] rounded-[2rem] border-2 border-teal-300 shadow-[0_0_0_999px_rgba(0,0,0,.08)]">
                                <span class="absolute -left-1 -top-1 h-12 w-12 rounded-tl-[2rem] border-l-8 border-t-8 border-[#ffd166]"></span>
                                <span class="absolute -right-1 -top-1 h-12 w-12 rounded-tr-[2rem] border-r-8 border-t-8 border-[#ffd166]"></span>
                                <span class="absolute -bottom-1 -left-1 h-12 w-12 rounded-bl-[2rem] border-b-8 border-l-8 border-[#ffd166]"></span>
                                <span class="absolute -bottom-1 -right-1 h-12 w-12 rounded-br-[2rem] border-b-8 border-r-8 border-[#ffd166]"></span>
                            </div>
                        </div>
                        <div x-show.important="cameraStarting" class="absolute inset-0 grid place-items-center bg-[#031f24]/80">
                            <div class="text-center">
                                <span class="mx-auto block h-10 w-10 animate-spin rounded-full border-4 border-white/20 border-t-teal-300 motion-reduce:animate-none"></span>
                                <p class="mt-3 text-sm font-semibold">Kamera wird gestartet …</p>
                            </div>
                        </div>
                    </div>

                    <p class="mt-4 text-center text-sm text-teal-50/75">Den persönlichen QR-Code vollständig in den Rahmen halten.</p>

                    <div x-show.important="cameraError" role="alert" class="mt-4 rounded-2xl border border-red-300/30 bg-red-400/10 p-4 text-sm text-red-100" x-text="cameraError"></div>

                    <form x-on:submit.prevent="submitManual()" class="mt-5 grid gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 sm:grid-cols-[1fr_auto]">
                        <div>
                            <label for="manual-ticket-id" class="block text-xs font-bold uppercase tracking-wide text-teal-200">Manuelle Teilnahme-ID</label>
                            <input
                                id="manual-ticket-id"
                                type="text"
                                x-model="manualParticipationId"
                                autocomplete="off"
                                spellcheck="false"
                                class="mt-2 block w-full rounded-xl border-white/15 bg-white/10 px-4 py-3 font-mono text-sm uppercase text-white placeholder:text-white/35 focus:border-teal-300 focus:ring-teal-300"
                                placeholder="RC-STR26-…"
                            >
                        </div>
                        <button type="submit" :disabled="busy" class="self-end rounded-xl bg-white px-5 py-3 text-sm font-black text-[#082f35] hover:bg-teal-50 disabled:opacity-50">Ticket prüfen</button>
                    </form>
                </div>

                <div x-show.important="phase === 'result'" class="mx-auto flex min-h-full w-full max-w-6xl flex-col px-4 py-6 sm:px-6">
                    <div class="grid gap-4 rounded-3xl border border-teal-300/25 bg-teal-300/10 p-5 sm:grid-cols-[auto_1fr] sm:items-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-300 text-[#082f35]">
                            <svg aria-hidden="true" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-teal-200">Teilnehmer ist aktiv</p>
                            <p class="mt-1 text-2xl font-black" x-text="participant?.ticket_id"></p>
                            <p class="mt-1 text-sm text-teal-50/75" x-text="participant?.name"></p>
                            <p x-show.important="participant?.instruction" class="mt-2 font-bold text-[#ffd166]" x-text="participant?.instruction"></p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p class="text-sm font-bold text-white">Gewinn auswählen</p>
                        <p class="mt-1 text-xs text-teal-50/65">Bitte den beobachteten Gewinn antippen. Die verfügbare Menge wird automatisch aktualisiert.</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @forelse ($prizeOptions as $prize)
                                <button
                                    type="button"
                                    x-on:click="record({{ $prize->id }})"
                                    :disabled="busy"
                                    class="min-h-28 rounded-2xl border-2 p-5 text-left shadow-lg transition hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-4 disabled:cursor-wait disabled:opacity-50 {{ $outcomeStyles['prize'] }}"
                                >
                                    <span class="block text-xs font-black uppercase tracking-[0.14em] opacity-75">Gewinn</span>
                                    <span class="mt-2 block text-xl font-black leading-tight">{{ $prize->name }}</span>
                                    <span class="mt-2 block text-xs font-semibold opacity-80">{{ max(0, $prize->quota - $prize->awarded_count) }} von {{ $prize->quota }} verfügbar</span>
                                </button>
                            @empty
                                <div class="rounded-2xl border border-amber-300/30 bg-amber-300/10 p-5 text-sm text-amber-100 sm:col-span-2 lg:col-span-3">Für diese Kampagne ist aktuell kein Gewinn verfügbar.</div>
                            @endforelse
                        </div>
                    </div>

                    @if ($operationalOptions->isNotEmpty())
                        <div class="mt-6 border-t border-white/10 pt-5">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-teal-100/60">Kein Gewinn oder erneuter Dreh</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach ($operationalOptions as $option)
                                    @php($optionOutcome = $option->outcome_type->value)
                                    <button
                                        type="button"
                                        x-on:click="record({{ $option->id }})"
                                        :disabled="busy"
                                        class="rounded-2xl border-2 p-4 text-left transition focus-visible:outline-none focus-visible:ring-4 disabled:cursor-wait disabled:opacity-50 {{ $outcomeStyles[$optionOutcome] }}"
                                    >
                                        <span class="block text-xs font-black uppercase tracking-[0.14em] opacity-75">{{ $outcomeLabels[$optionOutcome] }}</span>
                                        <span class="mt-1 block text-lg font-black">{{ $option->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <button
                        type="button"
                        x-on:click="close()"
                        :disabled="busy"
                        class="mt-7 self-start rounded-xl border border-red-200/30 px-5 py-3 text-sm font-bold text-red-100 hover:bg-red-400/10 disabled:opacity-50"
                    >
                        Aufruf abbrechen und Ticket freigeben
                    </button>
                </div>
            </div>
        </div>
    </template>

    <x-dialog-modal id="promotion-result-correction-modal" wire:model.live="correctionModalOpen" maxWidth="md">
        <x-slot name="title">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-amber-700">10-Minuten-Korrektur</p>
            <h2 class="mt-2 text-xl font-black text-slate-900">Beobachtetes Ergebnis korrigieren</h2>
        </x-slot>

        <x-slot name="content">
            <form id="promotion-result-correction-form" wire:submit="correctResult">
                <p class="text-sm leading-6 text-slate-600">Nur das eigene Ergebnis und nur vor einer Ausgabe. Der vorherige Stand bleibt im Verlauf erhalten.</p>

                <label for="staff-correction-field" class="mt-5 block text-sm font-bold text-slate-700">Korrektes Ergebnis</label>
                <select
                    id="staff-correction-field"
                    wire:model="correctionPrizeId"
                    class="mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600"
                >
                    <option value="">Bitte Ergebnis wählen</option>
                    @foreach ($resultFields->filter(fn ($field) => in_array($field->outcome_type->value, ['prize', 'no_win'], true)) as $field)
                        <option value="{{ $field->id }}">{{ $field->name }}</option>
                    @endforeach
                </select>
                @error('correctionPrizeId')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </form>
        </x-slot>

        <x-slot name="footer">
            <button
                type="button"
                wire:click="cancelCorrection"
                wire:loading.attr="disabled"
                wire:target="correctResult"
                class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            >
                Abbrechen
            </button>
            <button
                type="submit"
                form="promotion-result-correction-form"
                wire:loading.attr="disabled"
                wire:target="correctResult"
                class="ml-3 rounded-xl bg-amber-700 px-5 py-3 text-sm font-black text-white hover:bg-amber-800 disabled:opacity-50"
            >
                Korrektur speichern
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
