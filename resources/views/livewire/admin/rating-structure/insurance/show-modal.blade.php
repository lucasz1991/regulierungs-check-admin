<div>
    <x-dialog-modal wire:model="showModal">
        {{-- Titel --}}
        <x-slot name="title">
            <div class="flex items-center justify-between space-x-2">
                <div class="flex items-center gap-2">
                    <span class="font-semibold">Versicherung – Details</span>
                    @if($is_active)
                        <span class="text-green-700 bg-green-50 px-2 py-0.5 text-xs rounded">Aktiv</span>
                    @else
                        <span class="text-red-700 bg-red-50 px-2 py-0.5 text-xs rounded">Inaktiv</span>
                    @endif
                </div>

                @if($insuranceId)
                    <x-dropdown>
                        <x-slot name="trigger">
                            <x-link-button href="#" class="btn-xs py-0 leading-[0]">
                                <svg class="w-3 aspect-square" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8.046 40.2299"><defs><style>.a{fill:#2e2f30;}</style></defs><path class="a" d="M4.023,8.046A4.023,4.023,0,1,1,8.046,4.023,4.02293,4.02293,0,0,1,4.023,8.046Zm0,16.0919a4.023,4.023,0,1,1,4.023-4.023A4.02293,4.02293,0,0,1,4.023,24.1379Zm0,16.092a4.023,4.023,0,1,1,4.023-4.023A4.02293,4.02293,0,0,1,4.023,40.2299Z"/></svg>
                            </x-link-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link wire:click.stop="insuranceAnalyzeClaimRatingsViaGpt" class="cursor-pointer">
                                <svg class="w-3 aspect-square mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M80 160l26.66-53.33L160 80l-53.34-26.67L80 0 53.34 53.34 0 80l53.34 26.67L80 160zm144-64l16-32 32-16-32-16-16-32-16 32-32 16 32 16 16 32zm234.66 245.33L432 288l-26.66 53.33L352 368l53.34 26.67L432 448l26.66-53.33L512 368l-53.34-26.67zM400 192c8.84 0 16-7.16 16-16v-27.96l91.87-101.83c5.72-6.32 5.48-16.02-.55-22.05L487.84 4.69c-6.03-6.03-15.73-6.27-22.05-.55L186.6 256H144c-8.84 0-16 7.16-16 16v36.87L10.53 414.84c-13.57 12.28-14.1 33.42-1.16 46.36l41.43 41.43c12.94 12.94 34.08 12.41 46.36-1.16L376.34 192H400z"/></svg>
                                Ai Analyze
                            </x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                @endif
            </div>
        </x-slot>

        {{-- Inhalt (read-only) --}}
<x-slot name="content">
    @if($insuranceId)
        {{-- === OBERER BEREICH / ÜBERSICHT === --}}
        <div class="mb-12 space-y-6">
            <div class="flex items-center gap-3">
                {{-- Logo oder Initialen --}}
                <div class="shrink-0">
                    @if($insurance?->logo)
                        <img src="{{ $insurance?->getLogoImageUrlAttribute() }}" alt="" class="h-12 object-contain rounded">
                    @else
                        <div class="w-min rounded flex items-center justify-center text-sm border px-2 py-1 font-medium shadow-sm"
                             style="background-color: {{ $style['bg_color'] ?? '#eee' }}; color: {{ $style['font_color'] ?? '#333' }}; border-color: {{ $style['border_color'] ?? '#ccc' }};">
                            {{ strtoupper(substr($initials ?? ($name[0] ?? 'V'), 0, 8)) }}
                        </div>
                    @endif
                </div>

                {{-- Name & ID --}}
                <div class="min-w-0">
                    <div class="text-lg font-semibold truncate" title="{{ $name }}">{{ $name }}</div>
                    <div class="text-xs text-gray-500">ID: {{ $insuranceId }}</div>
                </div>

                {{-- Status rechts --}}
                <div class="ml-auto">
                    @if($is_active)
                        <span class="text-green-700 bg-green-50 px-2 py-0.5 text-xs rounded">Aktiv</span>
                    @else
                        <span class="text-red-700 bg-red-50 px-2 py-0.5 text-xs rounded">Inaktiv</span>
                    @endif
                </div>
            </div>

            {{-- Farben & Basisinfos --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="text-sm">
                    <div class="text-gray-600">Initialen</div>
                    <div class="font-medium">{{ $initials ?: '—' }}</div>
                </div>

                <div class="text-sm">
                    <div class="text-gray-600">Farben</div>
                    <div class="mt-1 flex items-center gap-3">
                        <span class="inline-flex items-center gap-1 text-xs">
                            <span class="w-4 h-4 rounded border" style="background: {{ $style['bg_color'] ?? '#eee' }}"></span> BG
                        </span>
                        <span class="inline-flex items-center gap-1 text-xs">
                            <span class="w-4 h-4 rounded border" style="background: {{ $style['font_color'] ?? '#333' }}"></span> Font
                        </span>
                        <span class="inline-flex items-center gap-1 text-xs">
                            <span class="w-4 h-4 rounded border" style="background: {{ $style['border_color'] ?? '#ccc' }}"></span> Border
                        </span>
                    </div>
                </div>

                <div class="text-xs text-gray-500">
                    <div>Erstellt: {{ optional($insurance?->created_at)->locale('de')->diffForHumans() }}</div>
                    <div>Bearbeitet: {{ optional($insurance?->updated_at)->locale('de')->diffForHumans() }}</div>
                </div>
            </div>
        </div>

        {{-- === TABS UNTER DER ÜBERSICHT === --}}
<x-ui.accordion.tabs
    :tabs="[
        'texts'   => ['label' => 'Texte', 'icon' => 'fad fa-align-left'],
        'types'   => ['label' => 'Versicherungstypen ('.count($assignedInsuranceTypes).')', 'icon' => 'fad fa-layer-group'],
        'analyze' => ['label' => 'Analyse', 'icon' => 'fad fa-magic'],
    ]"
    :collapseAt="'md'"
    default="texts"
    class="mt-2"
>
            {{-- TAB: TEXTE --}}
            <x-ui.accordion.tab-panel for="texts">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm font-semibold mb-1">Beschreibung</div>
                        <div class="text-sm bg-white rounded border p-3 min-h-[3rem]">
                            {!! nl2br(e($description ?? '—')) !!}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-semibold mb-1">Hilfstext</div>
                        <div class="text-sm rounded border p-3 min-h-[3rem]
                            {{ filled($helptext) ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-white' }}">
                            {!! nl2br(e($helptext ?? '—')) !!}
                        </div>
                    </div>
                </div>
            </x-ui.accordion.tab-panel>

            {{-- TAB: VERSICHERUNGSTYPEN --}}
            <x-ui.accordion.tab-panel for="types">
                <div class="bg-white border rounded p-3 max-h-64 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                    @forelse($assignedInsuranceTypes as $t)
                        <div class="flex items-center justify-between text-sm border-b last:border-0 py-1">
                            <span class="truncate">{{ $t['name'] }}</span>
                            <span class="text-gray-400 text-[11px]">#{{ $t['id'] }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">Keine Typen zugeordnet.</div>
                    @endforelse
                </div>
            </x-ui.accordion.tab-panel>



{{-- TAB: ANALYSE --}}
<x-ui.accordion.tab-panel for="analyze">
    <div class="mb-3 flex items-center justify-between gap-3">

        <x-button
            wire:click="insuranceAnalyzeClaimRatingsViaGpt"
            wire:loading.attr="disabled"
            class="btn-xs"
            title="AI-Analyse für diese Versicherung anstoßen"
        >
            <span wire:loading.remove>AI-Analyse starten</span>
            <span wire:loading>Analysiere…</span>
        </x-button>
    </div>

    @if(!$currentAnalysis)
        <div class="text-sm text-gray-500">Keine Analyse vorhanden.</div>
    @else
        <div class="rounded border bg-white p-3 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <div class="text-sm font-semibold truncate">
                            {{ $currentAnalysis['subtype'] ?? 'Allgemein' }}
                        </div>
                        @if(!empty($currentAnalysis['type']))
                            <span class="text-[10px] px-1.5 py-0.5 rounded border bg-gray-50 text-gray-700">
                                {{ strtoupper($currentAnalysis['type']) }}
                            </span>
                        @endif
                        @if(!empty($currentAnalysis['status']))
                            <span class="text-[10px] px-1.5 py-0.5 rounded border {{ $this->statusBadge($currentAnalysis['status']) }}">
                                {{ ucfirst($currentAnalysis['status']) }}
                            </span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500">
                        Erstellt {{ $currentAnalysis['created_at'] }}
                        · Aktualisiert {{ $currentAnalysis['updated_at'] }}
                        · #{{ $currentAnalysis['id'] }}
                    </div>
                </div>

                <div class="shrink-0 text-right">
                    <div class="text-xs text-gray-500">Gesamtscore</div>
                    <div class="text-lg font-bold leading-none">
                        {{ number_format((float) $currentAnalysis['total_score'], 2, ',', '.') }}
                    </div>
                </div>
            </div>

            {{-- Teil-Scores --}}
            <div class="mt-2 flex flex-wrap gap-2 text-[11px]">
                @isset($currentAnalysis['fairness'])
                    <span class="px-1.5 py-0.5 rounded border bg-sky-50 text-sky-800">
                        Fairness: <b>{{ number_format((float) $currentAnalysis['fairness'], 2, ',', '.') }}</b>
                    </span>
                @endisset
                @isset($currentAnalysis['speed'])
                    <span class="px-1.5 py-0.5 rounded border bg-sky-50 text-sky-800">
                        Geschwindigkeit: <b>{{ number_format((float) $currentAnalysis['speed'], 2, ',', '.') }}</b>
                    </span>
                @endisset
                @isset($currentAnalysis['communication'])
                    <span class="px-1.5 py-0.5 rounded border bg-sky-50 text-sky-800">
                        Kommunikation: <b>{{ number_format((float) $currentAnalysis['communication'], 2, ',', '.') }}</b>
                    </span>
                @endisset
                @isset($currentAnalysis['transparency'])
                    <span class="px-1.5 py-0.5 rounded border bg-sky-50 text-sky-800">
                        Transparenz: <b>{{ number_format((float) $currentAnalysis['transparency'], 2, ',', '.') }}</b>
                    </span>
                @endisset
            </div>

            {{-- AI-Kommentar --}}
            @if(!empty($currentAnalysis['ai_comment']))
                <div class="mt-3 text-sm bg-gray-50 rounded border p-2">
                    {!! $currentAnalysis['ai_comment'] !!}
                </div>
            @endif

            {{-- AI-Tags --}}
            @if(!empty($currentAnalysis['ai_tags']))
                <div class="mt-2 flex flex-wrap gap-1">
                    @foreach($currentAnalysis['ai_tags'] as $tag)
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full border bg-white text-gray-700">
                            #{{ $tag }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-ui.accordion.tab-panel>



        </x-ui.accordion.tabs>
    @endif
</x-slot>



        {{-- Footer --}}
        <x-slot name="footer">
            <div class="flex items-center justify-end">
                <x-button wire:click="$set('showModal', false)" class="btn-xs text-sm">
                    Schließen
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
