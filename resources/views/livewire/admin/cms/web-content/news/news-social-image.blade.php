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

                <div class="mb-5 text-center">
                    <p class="text-sm font-bold text-gray-800">Layout direkt am Bild anpassen</p>
                    <p class="mt-1 text-xs leading-5 text-gray-500">
                        Mit Mauszeiger, Tastaturfokus oder Tippen einen Marker öffnen. Jede Auswahl wird sofort gespeichert und neu gerendert.
                    </p>
                </div>

                @php
                    $hotspots = [
                        'logo' => [
                            'label' => 'Logo',
                            'keys' => ['logo_size'],
                            'style' => 'left: 8px; top: 8px;',
                            'panelStyle' => 'left: 0; top: 44px;',
                            'logoVariant' => true,
                        ],
                        'category' => [
                            'label' => 'Kategorie',
                            'keys' => ['badge_font_size'],
                            'style' => 'right: 8px; top: 8px;',
                            'panelStyle' => 'right: 0; top: 44px;',
                        ],
                        'title' => [
                            'label' => 'Titel',
                            'keys' => ['title_font_size', 'title_line_height'],
                            'style' => 'left: 8px; top: 61%;',
                            'panelStyle' => 'left: 44px; top: 0;',
                        ],
                        'section' => [
                            'label' => 'Textabstand',
                            'keys' => ['section_spacing'],
                            'style' => 'right: 8px; top: 71%;',
                            'panelStyle' => 'right: 44px; top: 0;',
                        ],
                        'excerpt' => [
                            'label' => 'Kurztext',
                            'keys' => ['excerpt_font_size', 'excerpt_line_height'],
                            'style' => 'left: 8px; top: 79%;',
                            'panelStyle' => 'left: 44px; bottom: 0;',
                        ],
                        'horizontal' => [
                            'label' => 'Seitenabstand',
                            'keys' => ['horizontal_padding'],
                            'style' => 'left: -44px; top: 50%; transform: translateY(-50%);',
                            'panelStyle' => 'left: 44px; top: 50%; transform: translateY(-50%);',
                        ],
                        'bottom' => [
                            'label' => 'Unterer Abstand',
                            'keys' => ['bottom_spacing'],
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
                    <div class="relative overflow-visible">
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
                                x-on:mouseenter="open = true"
                                x-on:mouseleave="open = false"
                                x-on:focusin="open = true"
                                x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false"
                                x-on:click.outside="open = false"
                                class="absolute z-30"
                                data-social-hotspot="{{ $hotspotKey }}"
                                style="{{ $hotspot['style'] }}"
                            >
                            <button
                                type="button"
                                x-on:click="open = true"
                                x-bind:aria-expanded="open.toString()"
                                aria-controls="social-image-hotspot-{{ $format }}-{{ $hotspotKey }}"
                                aria-label="{{ $hotspot['label'] }} einstellen"
                                title="{{ $hotspot['label'] }} einstellen"
                                class="flex h-9 w-9 items-center justify-center rounded-full border border-white/70 text-white shadow-lg backdrop-blur transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-300 focus:ring-offset-2"
                                style="background-color: rgba(2, 6, 23, .82);"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 1 0 0 4m0-4a2 2 0 1 1 0 4m-6 8a2 2 0 1 0 0 4m0-4v-2m0 6v2m12-6a2 2 0 1 0 0 4m0-4v-2m0 6v2M6 12V4m0 8v4m6-6v10m6-16v10" />
                                </svg>
                            </button>

                                <div
                                    id="social-image-hotspot-{{ $format }}-{{ $hotspotKey }}"
                                    x-cloak
                                    x-show="open"
                                    x-transition.opacity.duration.150ms
                                    class="absolute z-50 w-64 rounded-xl border border-gray-200 bg-white p-3 text-left shadow-2xl"
                                    style="{{ $hotspot['panelStyle'] }}"
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

                                        @error("layoutSettings.{$format}.{$controlKey}")
                                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2 text-xs text-gray-500">
                        <span class="font-semibold text-gray-700">{{ $formats[$format]['width'] }} × {{ $formats[$format]['height'] }}</span>
                        <span aria-hidden="true">·</span>
                        <span>PNG</span>
                        <span wire:loading class="font-semibold text-primary">· Speichert und rendert …</span>
                    </div>

                    @if($settingsStatus)
                        <p class="mt-2 text-center text-xs font-semibold text-emerald-700" role="status">{{ $settingsStatus }}</p>
                    @endif

                    <div class="mt-3 text-center">
                        <button
                            type="button"
                            wire:click="resetCurrentFormat"
                            class="min-h-[44px] rounded-lg px-3 py-2 text-xs font-semibold text-gray-500 underline-offset-4 transition hover:text-gray-800 hover:underline focus:outline-none focus:ring-2 focus:ring-primary-300"
                        >
                            {{ $formats[$format]['label'] }} auf Standard zurücksetzen
                        </button>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
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
