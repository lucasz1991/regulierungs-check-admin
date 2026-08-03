@php
    // Fuer die Diagramme reicht ein kompaktes JSON-Paket; die Charts werden
    // clientseitig mit ApexCharts gezeichnet (im Admin bereits eingebunden).
    $chartPayload = [
        'labels' => $timeline['labels'] ?? [],
        'eingang' => $timeline['eingang'] ?? [],
        'veroeffentlicht' => $timeline['veroeffentlicht'] ?? [],
        'status' => $statusBreakdown,
    ];
@endphp

{{-- data-chart-payload traegt die aktualisierten Werte nach einem Livewire-
     Re-Render zu den Charts, die selbst hinter wire:ignore liegen. --}}
<div
    wire:loading.class="cursor-wait"
    data-chart-payload="{{ json_encode($chartPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
    {{-- Kein x-init: Alpine ruft init() bei Alpine.data()-Komponenten selbst auf.
         Beides zusammen liess die Einstiegsanimation doppelt starten, wodurch der
         zweite Durchlauf die Kacheln wieder auf unsichtbar zuruecksetzte. --}}
    x-data="adminDashboard(@js($chartPayload))"
>

    {{-- Kopfzeile --}}
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between" data-dash="head">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Regulierungs-Check</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Übersicht</h1>
            <p class="mt-1 text-sm text-gray-500">
                Stand {{ \Carbon\Carbon::now()->translatedFormat('d. F Y, H:i') }} Uhr
            </p>
        </div>

        <button
            type="button"
            wire:click="refreshDashboard"
            wire:loading.attr="disabled"
            class="inline-flex w-fit items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:opacity-60"
        >
            <svg wire:loading.class="animate-spin" wire:target="refreshDashboard" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-4.992 4.992-2.51-2.51a7.5 7.5 0 1 0 1.087 8.66" />
            </svg>
            Aktualisieren
        </button>
    </div>

    {{-- Kennzahlen --}}
    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $key => $kpi)
            <div
                data-dash="tile"
                class="relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow-md {{ ($kpi['alert'] ?? false) ? 'border-amber-300' : 'border-gray-200' }}"
            >
                @if($kpi['alert'] ?? false)
                    <span class="absolute inset-x-0 top-0 h-1 bg-amber-400" aria-hidden="true"></span>
                @endif

                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $kpi['label'] }}</p>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary" aria-hidden="true">
                        @switch($kpi['icon'])
                            @case('inbox')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>
                                @break
                            @case('clock')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                @break
                            @case('star')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.5a.56.56 0 0 1 1.04 0l2.12 4.98 5.4.46c.5.04.7.66.32.98l-4.1 3.55 1.23 5.27c.11.48-.41.87-.84.62L12 16.6l-4.65 2.76c-.43.25-.95-.14-.84-.62l1.23-5.27-4.1-3.55a.56.56 0 0 1 .32-.98l5.4-.46L11.48 3.5Z" /></svg>
                                @break
                            @default
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.4 48.4 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" /></svg>
                        @endswitch
                    </span>
                </div>

                <div class="mt-3 flex items-baseline gap-1">
                    <span
                        class="text-3xl font-bold tracking-tight text-gray-900 tabular-nums"
                        data-countup="{{ $kpi['value'] }}"
                        data-decimals="{{ str_contains((string) $kpi['value'], '.') ? 2 : 0 }}"
                    >{{ number_format((float) $kpi['value'], str_contains((string) $kpi['value'], '.') ? 2 : 0, ',', '.') }}</span>
                    @if($kpi['suffix'] !== '')
                        <span class="text-sm font-semibold text-gray-500">{{ $kpi['suffix'] }}</span>
                    @endif
                </div>

                <div class="mt-2 flex items-center gap-2 text-xs">
                    @if($kpi['trend'])
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-semibold {{ $kpi['trend']['direction'] === 'up' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                            {{ $kpi['trend']['direction'] === 'up' ? '▲' : '▼' }} {{ $kpi['trend']['percent'] }} %
                        </span>
                    @endif
                    <span class="text-gray-500">{{ $kpi['hint'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Diagramme --}}
    <div class="mb-5 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div data-dash="tile" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Bewertungseingang</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Eingegangen gegenüber veröffentlicht, letzte 12 Monate</p>
                </div>
            </div>
            <div wire:ignore x-ref="timelineChart" class="min-h-[280px]"></div>
        </div>

        <div data-dash="tile" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900">Status der Bewertungen</h2>
            <p class="mt-0.5 text-xs text-gray-500">Verteilung über alle Einreichungen</p>

            @if(count($statusBreakdown) > 0)
                <div wire:ignore x-ref="statusChart" class="min-h-[240px]"></div>
                <ul class="mt-2 space-y-1.5">
                    @foreach($statusBreakdown as $entry)
                        <li class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2 text-gray-600">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $entry['color'] }}" aria-hidden="true"></span>
                                {{ $entry['label'] }}
                            </span>
                            <span class="font-semibold tabular-nums text-gray-900">{{ number_format($entry['count'], 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-6 text-sm text-gray-500">Noch keine Bewertungen erfasst.</p>
            @endif
        </div>
    </div>

    {{-- Tabellen --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div data-dash="tile" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-sm font-bold text-gray-900">Moderationsschlange</h2>
                <p class="mt-0.5 text-xs text-gray-500">Älteste offene Fälle zuerst</p>
            </div>

            @forelse($moderationQueue as $item)
                <a
                    href="{{ route('admin.reviews.show', $item['id']) }}"
                    class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 text-sm transition last:border-b-0 hover:bg-gray-50"
                >
                    <span class="min-w-0">
                        <span class="block truncate font-semibold text-gray-900">{{ $item['insurance'] }}</span>
                        <span class="mt-0.5 block text-xs text-gray-500">
                            wartet seit {{ $item['wartetSeit'] }} {{ $item['wartetSeit'] === 1 ? 'Tag' : 'Tagen' }}
                            @if($item['score'] !== null)
                                · {{ number_format($item['score'], 1, ',', '.') }} / 5
                            @endif
                        </span>
                    </span>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $item['badge'] }}">
                        {{ $item['status'] }}
                    </span>
                </a>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">Nichts offen — alle Bewertungen sind bearbeitet.</p>
            @endforelse
        </div>

        <div data-dash="tile" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-sm font-bold text-gray-900">Anbieter mit der besten Bewertung</h2>
                <p class="mt-0.5 text-xs text-gray-500">Ab {{ 3 }} veröffentlichten Bewertungen</p>
            </div>

            @forelse($topInsurances as $index => $row)
                <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-3 last:border-b-0">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xs font-bold text-gray-600">
                        {{ $index + 1 }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-gray-900">{{ $row['name'] }}</span>
                        <span class="mt-1 block h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                            <span class="block h-full rounded-full bg-secondary" style="width: {{ max(4, min(100, $row['score'] / 5 * 100)) }}%"></span>
                        </span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="block text-sm font-bold tabular-nums text-gray-900">{{ number_format($row['score'], 2, ',', '.') }}</span>
                        <span class="block text-xs text-gray-500">{{ $row['total'] }} Bew.</span>
                    </span>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">Noch zu wenige veröffentlichte Bewertungen für eine Rangliste.</p>
            @endforelse
        </div>
    </div>

    {{-- Redaktioneller Stand --}}
    <div data-dash="tile" class="mt-5 grid grid-cols-2 gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-4">
        @foreach([
            ['News veröffentlicht', $redaktion['newsVeroeffentlicht']],
            ['News-Entwürfe', $redaktion['newsEntwuerfe']],
            ['Anbieter', $redaktion['anbieter']],
            ['Benutzer', $redaktion['benutzer']],
        ] as [$label, $value])
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-gray-900" data-countup="{{ $value }}" data-decimals="0">
                    {{ number_format($value, 0, ',', '.') }}
                </p>
            </div>
        @endforeach
    </div>
</div>
