{{--
    Vorschau des Social-Media-Bildes: Formatumschalter, Bild, Status, Buttons.

    Die Sichtbarkeit steuert ausschliesslich die Livewire-Eigenschaft $show
    ueber x-dialog-modal. Ohne gesetzte postId wird nichts gerendert, im
    geschlossenen Zustand laeuft also auch keine Bildanfrage.
--}}
<div>
    <x-dialog-modal id="news-social-image-modal" wire:model="show" :maxWidth="'lg'">
        <x-slot name="title">
            <span class="text-base font-bold text-gray-900">Social-Media-Bild</span>
            @if($title !== '')
                <span class="mt-0.5 block truncate text-sm font-normal text-gray-500">{{ $title }}</span>
            @endif
        </x-slot>

        <x-slot name="content">
            @if($postId)
                {{-- Formatumschalter --}}
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

                {{--
                    wire:key enthaelt das Format: beim Wechsel ersetzt Livewire den
                    Teilbaum, das <img> startet mit frischem Ladezustand.
                    wire:ignore haelt den Rest des DOM-Abgleichs davon fern, damit
                    eine laufende Bildanfrage nicht abgebrochen wird - genau daran
                    blieb zuvor die Fehlermeldung ueber dem fertigen Bild stehen.
                --}}
                <div
                    wire:ignore
                    wire:key="social-image-{{ $postId }}-{{ $format }}"
                    x-data="{ loading: true, failed: false }"
                    class="mx-auto w-full"
                    style="max-width: {{ $formats[$format]['height'] > $formats[$format]['width'] ? '260px' : '420px' }};"
                >
                    <div
                        class="relative overflow-hidden rounded-xl border border-gray-200 bg-gray-50 shadow-inner"
                        style="aspect-ratio: {{ $formats[$format]['width'] }} / {{ $formats[$format]['height'] }};"
                    >
                        <img
                            src="{{ route('admin.news.social-image.preview', ['post' => $postId, 'format' => $format]) }}"
                            {{-- Ein erfolgreicher Ladevorgang raeumt einen frueheren Fehler wieder ab. --}}
                            x-on:load="loading = false; failed = false"
                            x-on:error="loading = false; failed = true"
                            alt="Vorschau des Social-Media-Bildes"
                            class="h-full w-full object-cover transition-opacity duration-300"
                            :class="loading || failed ? 'opacity-0' : 'opacity-100'"
                        >

                        <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-gray-50">
                            <span class="h-8 w-8 animate-spin rounded-full border-[3px] border-gray-200 border-t-primary" aria-hidden="true"></span>
                            <span class="sr-only">Bild wird erzeugt</span>
                        </div>

                        <div x-show="failed" x-cloak class="absolute inset-0 flex items-center justify-center bg-gray-50 px-4 text-center">
                            <span class="text-sm font-semibold text-red-700">Vorschau fehlgeschlagen</span>
                        </div>
                    </div>

                    {{-- Statuszeile --}}
                    <p class="mt-3 flex items-center justify-center gap-2 text-xs font-semibold">
                        <span
                            class="h-2 w-2 rounded-full"
                            :class="loading ? 'bg-amber-400 animate-pulse' : (failed ? 'bg-red-500' : 'bg-emerald-500')"
                            aria-hidden="true"
                        ></span>
                        <span
                            class="text-gray-600"
                            x-text="loading ? 'Bild wird erzeugt …' : (failed ? 'Fehlgeschlagen' : '{{ $formats[$format]['width'] }} × {{ $formats[$format]['height'] }}')"
                        ></span>
                    </p>
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
                    href="{{ route('admin.news.social-image.download', ['post' => $postId, 'format' => $format]) }}"
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
