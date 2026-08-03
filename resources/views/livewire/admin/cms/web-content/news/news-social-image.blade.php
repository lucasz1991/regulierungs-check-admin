{{--
    Vorschau des Social-Media-Bildes.

    Die Sichtbarkeit steuert ausschliesslich die Livewire-Eigenschaft $show
    ueber x-dialog-modal. Ohne gesetzte postId wird gar nichts gerendert, damit
    im geschlossenen Zustand auch keine Bildanfrage laeuft.
--}}
<div>
    <x-dialog-modal id="news-social-image-modal" wire:model="show" :maxWidth="'4xl'">
        <x-slot name="title">
            <span class="text-base font-bold text-gray-900">Social-Media-Bild</span>
            @if($title !== '')
                <span class="mt-0.5 block truncate text-sm font-normal text-gray-500">{{ $title }}</span>
            @endif
        </x-slot>

        <x-slot name="content">
            @if($postId)
                <div
                    class="grid gap-6 md:grid-cols-[minmax(0,260px)_1fr]"
                    wire:key="social-image-{{ $postId }}"
                    x-data="{ loading: true, failed: false }"
                >
                    <div class="mx-auto w-full max-w-[260px]">
                        <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-gray-50 shadow-inner" style="aspect-ratio: 9 / 16;">
                            <div x-show="loading" class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-sm text-gray-500">
                                <span class="h-7 w-7 animate-spin rounded-full border-[3px] border-gray-200 border-t-primary" aria-hidden="true"></span>
                                Bild wird erzeugt …
                            </div>

                            <div x-show="failed" x-cloak class="absolute inset-0 flex flex-col items-center justify-center gap-2 px-4 text-center text-sm text-red-700">
                                <span class="font-semibold">Vorschau fehlgeschlagen</span>
                                <span class="text-xs text-gray-500">Bitte Modal schließen und erneut öffnen.</span>
                            </div>

                            <img
                                src="{{ route('admin.news.social-image.preview', $postId) }}"
                                x-show="! loading &amp;&amp; ! failed"
                                x-on:load="loading = false"
                                x-on:error="loading = false; failed = true"
                                alt="Vorschau des Social-Media-Bildes"
                                class="h-full w-full object-cover"
                            >
                        </div>
                    </div>

                    <div class="space-y-3 text-sm leading-6 text-gray-600">
                        <p>
                            Hochformat <span class="font-semibold text-gray-800">1080 × 1920</span> für Instagram Story,
                            Reels-Cover, Facebook und LinkedIn.
                        </p>
                        <p>
                            Enthalten sind Logo, Kategorie-Badge, Titel und Kurztext dieser News sowie das Beitragsbild
                            als Hintergrund.
                        </p>
                        <p class="rounded-lg bg-amber-50 px-3 py-2.5 text-xs text-amber-900 ring-1 ring-inset ring-amber-200">
                            Der Button im Bild ist nur aufgemalt und nicht klickbar. Den Link zur News setzt du wie
                            gewohnt in den Beitragstext beziehungsweise in die Story-Verlinkung.
                        </p>
                        <p class="text-xs text-gray-500">
                            Das Bild wird bei jedem Aufruf neu erzeugt und nicht auf dem Server gespeichert.
                        </p>
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
                    href="{{ route('admin.news.social-image.download', $postId) }}"
                    class="ml-2 inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-light"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Bild herunterladen
                </a>
            @endif
        </x-slot>
    </x-dialog-modal>
</div>
