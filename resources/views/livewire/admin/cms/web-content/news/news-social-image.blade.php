<div>
    <style>
        @media (max-width: 639px) {
            #news-social-image-modal [data-social-hotspot="horizontal"] {
                left: 8px !important;
            }
        }
    </style>

    <x-dialog-modal id="news-social-image-modal" wire:model="show" :maxWidth="'2xl'">
        <x-slot name="title">
            <span class="text-base font-bold text-gray-900">Social-Media-Bild</span>
            @if($title !== '')
                <span class="mt-0.5 block truncate text-sm font-normal text-gray-500">{{ $title }}</span>
            @endif
        </x-slot>

        <x-slot name="content">
            @if($postId)
                <div class="mb-4 flex justify-center">
                    <div class="inline-flex rounded-xl bg-gray-100 p-1" role="group" aria-label="Bildformat">
                        @foreach($formats as $key => $spec)
                            <button
                                type="button"
                                wire:click="setFormat('{{ $key }}')"
                                @class([
                                    'rounded-lg px-3.5 py-2 text-xs font-semibold transition',
                                    'bg-white text-gray-900 shadow-sm' => $format === $key,
                                    'text-gray-500 hover:text-gray-800' => $format !== $key,
                                ])
                                aria-pressed="{{ $format === $key ? 'true' : 'false' }}"
                            >
                                {{ $spec['label'] }}
                                <span class="ml-1 font-normal opacity-70">{{ $spec['hint'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                @php
                    $hotspots = [
                        'logo' => [
                            'label' => 'Logo',
                            'keys' => ['logo_size'],
                            'icon' => 'logo',
                            'style' => 'left: 8px; top: 8px;',
                            'panelStyle' => 'left: 0; top: 44px;',
                            'logoVariant' => true,
                        ],
                        'category' => [
                            'label' => 'Kategorie',
                            'keys' => ['badge_font_size', 'badge_background', 'badge_text_color'],
                            'icon' => 'category',
                            'style' => 'right: 8px; top: 8px;',
                            'panelStyle' => 'right: 0; top: 44px;',
                        ],
                        'title' => [
                            'label' => 'Titel',
                            'keys' => ['title_font_size', 'title_line_height', 'title_color', 'title_alignment', 'title_lines'],
                            'icon' => 'title',
                            'style' => 'left: 8px; top: 61%;',
                            'panelStyle' => 'left: 44px; top: 0;',
                        ],
                        'section' => [
                            'label' => 'Textabstand',
                            'keys' => ['section_spacing', 'accent_color'],
                            'icon' => 'section',
                            'style' => 'right: 8px; top: 71%;',
                            'panelStyle' => 'right: 44px; top: 0;',
                        ],
                        'excerpt' => [
                            'label' => 'Kurztext',
                            'keys' => ['excerpt_font_size', 'excerpt_line_height', 'excerpt_color', 'excerpt_alignment', 'excerpt_lines'],
                            'icon' => 'excerpt',
                            'style' => 'left: 8px; top: 79%;',
                            'panelStyle' => 'left: 44px; bottom: 0;',
                        ],
                        'horizontal' => [
                            'label' => 'Seitenabstand',
                            'keys' => ['horizontal_padding'],
                            'icon' => 'horizontal',
                            'style' => 'left: -44px; top: 50%; transform: translateY(-50%);',
                            'panelStyle' => 'left: 44px; top: 50%; transform: translateY(-50%);',
                        ],
                        'bottom' => [
                            'label' => 'Unterer Abstand',
                            'keys' => ['bottom_spacing'],
                            'icon' => 'bottom',
                            'style' => 'bottom: 8px; left: 50%; transform: translateX(-50%);',
                            'panelStyle' => 'bottom: 44px; left: 50%; transform: translateX(-50%);',
                        ],
                    ];
                @endphp

                <div
                    wire:key="social-image-{{ $postId }}-{{ $format }}-{{ $logoVariant }}-{{ $previewRevisions[$format] ?? 0 }}"
                    class="relative mx-auto w-full overflow-visible"
                    style="max-width: {{ $formats[$format]['height'] > $formats[$format]['width'] ? '268px' : '440px' }};"
                >
                    <div
                        x-data="{ controlsVisible: false }"
                        x-on:mouseenter="controlsVisible = true"
                        x-on:mouseleave="controlsVisible = false"
                        x-on:focusin="controlsVisible = true"
                        x-on:focusout="if (! $el.contains($event.relatedTarget)) controlsVisible = false"
                        x-on:click="controlsVisible = true"
                        class="relative overflow-visible focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-300 focus-visible:ring-offset-4"
                        tabindex="0"
                        aria-label="Bildeinstellungen anzeigen"
                    >
                        <div
                            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 shadow-lg ring-1 ring-black/5"
                            style="aspect-ratio: {{ $formats[$format]['width'] }} / {{ $formats[$format]['height'] }};"
                        >
                            <div class="absolute inset-0 z-0 flex items-center justify-center">
                                <span class="h-7 w-7 animate-spin rounded-full border-[3px] border-slate-300 border-t-primary" aria-hidden="true"></span>
                            </div>

                            <img
                                src="{{ route('admin.news.social-image.preview', ['post' => $postId, 'format' => $format, 'logo' => $logoVariant, 'revision' => $previewRevisions[$format] ?? 0]) }}"
                                alt="Vorschau des Social-Media-Bildes für {{ $title }}"
                                class="relative z-10 h-full w-full object-cover"
                            >
                        </div>

                        @foreach($hotspots as $hotspotKey => $hotspot)
                            <div
                                x-data="{ open: false }"
                                x-on:click.outside="open = false"
                                x-on:keydown.escape.window="open = false"
                                x-cloak
                                x-show="controlsVisible || open"
                                x-transition.opacity.duration.150ms
                                x-bind:class="{ 'z-50': open, 'z-30': ! open }"
                                class="absolute"
                                data-social-hotspot="{{ $hotspotKey }}"
                                style="{{ $hotspot['style'] }}"
                            >
                            <button
                                type="button"
                                x-on:click.stop="open = ! open"
                                x-bind:aria-expanded="open.toString()"
                                aria-controls="social-image-hotspot-{{ $format }}-{{ $hotspotKey }}"
                                aria-label="{{ $hotspot['label'] }} einstellen"
                                title="{{ $hotspot['label'] }} einstellen"
                                class="flex h-9 w-9 items-center justify-center rounded-full border border-white/70 text-white shadow-lg backdrop-blur transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-300 focus:ring-offset-2"
                                style="background-color: rgba(2, 6, 23, .82);"
                            >
                                @switch($hotspot['icon'])
                                    @case('logo')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V6.75A2.25 2.25 0 0 1 5.25 4.5h13.5A2.25 2.25 0 0 1 21 6.75v9.75m-18 0v.75a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 17.25v-.75M3 16.5l4.72-4.72a2.25 2.25 0 0 1 3.18 0l1.1 1.1 2.22-2.22a2.25 2.25 0 0 1 3.18 0L21 14.25M8.25 8.25h.008v.008H8.25V8.25Z" /></svg>
                                        @break
                                    @case('category')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.57 3.44a2.25 2.25 0 0 1 1.59-.66h6.06a2.25 2.25 0 0 1 2.25 2.25v6.06a2.25 2.25 0 0 1-.66 1.59l-7.5 7.5a2.25 2.25 0 0 1-3.18 0l-4.31-4.31a2.25 2.25 0 0 1 0-3.18l7.75-7.75ZM15.75 7.5h.008v.008h-.008V7.5Z" /></svg>
                                        @break
                                    @case('title')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 5.25h15m-7.5 0v13.5m-3.75 0h7.5" /></svg>
                                        @break
                                    @case('section')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 4.5h12M6 19.5h12M12 7.5v9m0-9-2.25 2.25M12 7.5l2.25 2.25M12 16.5l-2.25-2.25M12 16.5l2.25-2.25" /></svg>
                                        @break
                                    @case('excerpt')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15m-15 5.25h15m-15 5.25h9" /></svg>
                                        @break
                                    @case('horizontal')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18m-18 0 3-3m-3 3 3 3m15-3-3-3m3 3-3 3" /></svg>
                                        @break
                                    @default
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0 0-3-3m3 3 3-3M12 3 9 6m3-3 3 3" /></svg>
                                @endswitch
                            </button>

                                <div
                                    id="social-image-hotspot-{{ $format }}-{{ $hotspotKey }}"
                                    x-cloak
                                    x-show="open"
                                    x-on:click.stop
                                    x-transition.opacity.duration.150ms
                                    class="absolute z-50 w-64 overflow-y-auto rounded-xl border border-gray-200 bg-white p-3 text-left shadow-2xl"
                                    style="{{ $hotspot['panelStyle'] }} max-height: min(420px, 70vh);"
                                >
                                <p class="mb-3 text-xs font-extrabold uppercase tracking-wider text-gray-500">{{ $hotspot['label'] }}</p>

                                @if($hotspot['logoVariant'] ?? false)
                                    <div class="mb-3">
                                        <label for="social-image-logo-{{ $format }}" class="mb-1 block text-xs font-semibold text-gray-700">Logo-Variante</label>
                                        <select
                                            id="social-image-logo-{{ $format }}"
                                            wire:model.live="logoVariant"
                                            class="block min-h-[44px] w-full rounded-lg border-gray-300 py-2 pl-3 pr-9 text-sm text-gray-800 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-200"
                                        >
                                            @foreach($logoVariants as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @foreach($hotspot['keys'] as $controlKey)
                                    @php($control = $layoutControls[$controlKey])
                                    <div class="mb-3 last:mb-0">
                                        <label for="social-image-setting-{{ $format }}-{{ $controlKey }}" class="mb-1 block text-xs font-semibold text-gray-700">
                                            {{ $control['label'] }}
                                        </label>
                                        @if(($control['input'] ?? 'select') === 'number')
                                            <div class="relative max-w-40">
                                                <input
                                                    id="social-image-setting-{{ $format }}-{{ $controlKey }}"
                                                    type="number"
                                                    min="{{ $control['min'] }}"
                                                    max="{{ $control['max'] }}"
                                                    step="{{ $control['step'] }}"
                                                    inputmode="numeric"
                                                    wire:model.live.debounce.500ms="layoutSettings.{{ $format }}.{{ $controlKey }}"
                                                    wire:loading.attr="disabled"
                                                    aria-describedby="social-image-setting-{{ $format }}-{{ $controlKey }}-hint"
                                                    class="block min-h-[44px] w-full rounded-lg border-gray-300 py-2 pl-3 pr-10 text-sm text-gray-800 shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200 disabled:cursor-wait disabled:opacity-60"
                                                    @if($errors->has("layoutSettings.{$format}.{$controlKey}")) aria-invalid="true" @endif
                                                >
                                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-gray-400">{{ $control['unit'] }}</span>
                                            </div>
                                            <p id="social-image-setting-{{ $format }}-{{ $controlKey }}-hint" class="mt-1 text-[11px] text-gray-500">
                                                Ganze Pixel von {{ $control['min'] }} bis {{ $control['max'] }}.
                                            </p>
                                        @elseif(($control['input'] ?? 'select') === 'range')
                                            <div class="flex items-center gap-2">
                                                <div class="relative w-24 shrink-0">
                                                    <input
                                                        id="social-image-setting-{{ $format }}-{{ $controlKey }}"
                                                        type="number"
                                                        min="{{ $control['min'] }}"
                                                        max="{{ $control['max'] }}"
                                                        step="{{ $control['step'] }}"
                                                        wire:model.live.debounce.500ms="layoutSettings.{{ $format }}.{{ $controlKey }}"
                                                        wire:loading.attr="disabled"
                                                        class="block min-h-[44px] w-full rounded-lg border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-800 shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200 disabled:cursor-wait disabled:opacity-60"
                                                        @if($errors->has("layoutSettings.{$format}.{$controlKey}")) aria-invalid="true" @endif
                                                    >
                                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-gray-400">{{ $control['unit'] }}</span>
                                                </div>
                                                <input
                                                    type="range"
                                                    min="{{ $control['min'] }}"
                                                    max="{{ $control['max'] }}"
                                                    step="{{ $control['step'] }}"
                                                    wire:model.live.debounce.500ms="layoutSettings.{{ $format }}.{{ $controlKey }}"
                                                    wire:loading.attr="disabled"
                                                    aria-label="{{ $control['label'] }} als Schieberegler"
                                                    class="h-2 min-h-[44px] w-full cursor-pointer accent-primary disabled:cursor-wait disabled:opacity-60"
                                                >
                                            </div>
                                        @else
                                            <select
                                                id="social-image-setting-{{ $format }}-{{ $controlKey }}"
                                                wire:model.live="layoutSettings.{{ $format }}.{{ $controlKey }}"
                                                wire:loading.attr="disabled"
                                                class="block min-h-[44px] w-full rounded-lg border-gray-300 py-2 pl-3 pr-9 text-sm text-gray-800 shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200 disabled:cursor-wait disabled:opacity-60"
                                                @if($errors->has("layoutSettings.{$format}.{$controlKey}")) aria-invalid="true" @endif
                                            >
                                                @foreach($control['options'] as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @endif

                                        @error("layoutSettings.{$format}.{$controlKey}")
                                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($settingsStatus)
                        <p class="sr-only" role="status" aria-live="polite">{{ $settingsStatus }}</p>
                    @endif
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            @if($postId)
                <button
                    type="button"
                    wire:click="resetCurrentFormat"
                    class="mr-auto inline-flex min-h-[44px] items-center justify-center rounded-lg px-3 py-2 text-xs font-semibold text-gray-500 underline-offset-4 transition hover:text-gray-800 hover:underline focus:outline-none focus:ring-2 focus:ring-primary-300"
                >
                    {{ $formats[$format]['label'] }} zurücksetzen
                </button>
            @endif

            <button
                type="button"
                wire:click="closeModal"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
            >
                Schließen
            </button>

            @if($postId)
                <a
                    href="{{ route('admin.news.social-image.download', ['post' => $postId, 'format' => $format, 'logo' => $logoVariant]) }}"
                    class="ml-2 inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-light"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Herunterladen
                </a>
            @endif
        </x-slot>
    </x-dialog-modal>
</div>
