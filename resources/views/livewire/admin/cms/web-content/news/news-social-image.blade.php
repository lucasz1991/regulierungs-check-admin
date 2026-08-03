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
                    Logo-Auswahl. wire:model.live rendert die Komponente beim
                    Wechsel neu; ueber den wire:key des Vorschaubereichs wird
                    das Bild dabei frisch angefordert.
                --}}
                <div class="mb-4 flex items-center justify-center gap-2">
                    <label for="social-image-logo" class="text-xs font-semibold text-gray-600">Logo</label>
                    <select
                        id="social-image-logo"
                        wire:model.live="logoVariant"
                        class="rounded-lg border-gray-300 py-2 pl-3 pr-9 text-xs font-semibold text-gray-800 shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200"
                    >
                        @foreach($logoVariants as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{--
                    Reines HTML, kein JavaScript.

                    Das Bild ist von Haus aus sichtbar, das Ladeskelett liegt
                    darunter und wird davon ueberdeckt, sobald es gezeichnet ist.

                    Bewusst ohne Fehler-Overlay: Livewire tauscht beim Abgleich
                    das src-Attribut, was am <img> ein error-Ereignis ausloest,
                    obwohl das Bild einwandfrei geladen ist. Das Overlay legte
                    sich dadurch faelschlich ueber die fertige Vorschau. Ein
                    tatsaechlicher Fehlschlag ist ohnehin sichtbar - dann bleibt
                    das Skelett stehen - und steht mit Ursache im Protokoll.
                --}}
                <div
                    wire:key="social-image-{{ $postId }}-{{ $format }}-{{ $logoVariant }}"
                    class="mx-auto w-full"
                    style="max-width: {{ $formats[$format]['height'] > $formats[$format]['width'] ? '268px' : '440px' }};"
                >
                    <div
                        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 shadow-lg ring-1 ring-black/5"
                        style="aspect-ratio: {{ $formats[$format]['width'] }} / {{ $formats[$format]['height'] }};"
                    >
                        <div class="absolute inset-0 z-0 flex items-center justify-center">
                            <span class="h-7 w-7 animate-spin rounded-full border-[3px] border-slate-300 border-t-primary" aria-hidden="true"></span>
                        </div>

                        <img
                            src="{{ route('admin.news.social-image.preview', ['post' => $postId, 'format' => $format, 'logo' => $logoVariant]) }}"
                            alt="Vorschau des Social-Media-Bildes für {{ $title }}"
                            class="relative z-10 h-full w-full object-cover"
                        >
                    </div>

                    <div class="mt-3 flex items-center justify-center gap-2 text-xs text-gray-500">
                        <span class="font-semibold text-gray-700">{{ $formats[$format]['width'] }} × {{ $formats[$format]['height'] }}</span>
                        <span aria-hidden="true">·</span>
                        <span>PNG</span>
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
