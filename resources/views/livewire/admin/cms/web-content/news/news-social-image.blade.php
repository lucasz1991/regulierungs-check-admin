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
                    Das Bild ist von Haus aus sichtbar.

                    Vorher haing seine Sichtbarkeit an einer Alpine-Bindung
                    (:class mit opacity-0 als Ausgangszustand). Lief Alpine hier
                    nicht an oder war das load-Ereignis bereits vorbei, blieb das
                    fertig geladene Bild dauerhaft unsichtbar. Jetzt ist der
                    Ausgangszustand "sichtbar"; das Skelett liegt darunter und
                    wird vom Bild einfach ueberdeckt, sobald es gezeichnet ist.
                    Ohne JavaScript funktioniert die Vorschau damit ebenfalls.
                --}}
                <div
                    wire:key="social-image-{{ $postId }}-{{ $format }}"
                    x-data="{ failed: false }"
                    class="mx-auto w-full"
                    style="max-width: {{ $formats[$format]['height'] > $formats[$format]['width'] ? '268px' : '440px' }};"
                >
                    <div
                        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 shadow-lg ring-1 ring-black/5"
                        style="aspect-ratio: {{ $formats[$format]['width'] }} / {{ $formats[$format]['height'] }};"
                    >
                        {{-- Skelett liegt unter dem Bild und braucht kein JavaScript. --}}
                        <div class="absolute inset-0 z-0 flex flex-col items-center justify-center gap-3 text-slate-400">
                            <span class="h-7 w-7 animate-spin rounded-full border-[3px] border-slate-300 border-t-primary" aria-hidden="true"></span>
                            <span class="text-xs font-medium">Vorschau wird erzeugt …</span>
                        </div>

                        <img
                            src="{{ route('admin.news.social-image.preview', ['post' => $postId, 'format' => $format]) }}"
                            x-on:error="failed = true"
                            x-on:load="failed = false"
                            alt="Vorschau des Social-Media-Bildes für {{ $title }}"
                            class="relative z-10 h-full w-full object-cover"
                        >

                        {{-- Nur bei echtem Ladefehler, liegt ueber allem. --}}
                        <div
                            x-show="failed"
                            x-cloak
                            class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-2 bg-white/95 px-6 text-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                            <span class="text-sm font-semibold text-gray-900">Vorschau konnte nicht geladen werden</span>
                            <span class="text-xs text-gray-500">Details stehen im Anwendungsprotokoll.</span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-center gap-2 text-xs text-gray-500">
                        <span class="font-semibold text-gray-700">{{ $formats[$format]['width'] }} × {{ $formats[$format]['height'] }}</span>
                        <span aria-hidden="true">·</span>
                        <span>PNG</span>
                    </div>

                    <p class="mt-4 rounded-xl bg-amber-50 px-3.5 py-2.5 text-xs leading-5 text-amber-900 ring-1 ring-inset ring-amber-200">
                        Der Button im Bild ist aufgemalt und nicht klickbar. Den Link zur News setzt du wie gewohnt
                        im Beitragstext oder in der Story-Verlinkung.
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
