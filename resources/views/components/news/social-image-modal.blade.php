{{--
    Modal mit der Vorschau des Social-Media-Bildes einer News.

    Wird einmal pro Seite eingebunden und ueber das Fenster-Event
    `open-news-social-image` mit { postId, title } geoeffnet. Die Vorschau ist
    ein <img> auf die Stream-Route; erst beim Oeffnen wird sie geladen und beim
    Schliessen wieder verworfen, damit im Hintergrund nichts gerendert wird.

    Auf dem Server entsteht dabei keine Datei - das Bild wird bei jedem Aufruf
    frisch erzeugt und direkt gestreamt.
--}}
<div
    x-data="{
        open: false,
        postId: null,
        title: '',
        loading: false,
        failed: false,
        previewSrc: '',

        show(detail) {
            this.postId = detail?.postId ?? null;
            this.title = detail?.title ?? '';
            if (! this.postId) return;

            this.failed = false;
            this.loading = true;
            // Zeitstempel erzwingt ein frisches Rendering statt Browser-Cache.
            this.previewSrc = `/admin/news/${this.postId}/social-image?t=${Date.now()}`;
            this.open = true;
        },

        close() {
            this.open = false;
            // Quelle leeren: der Browser verwirft das Bild, es bleibt nichts liegen.
            this.previewSrc = '';
            this.postId = null;
            this.loading = false;
            this.failed = false;
        },

        download() {
            if (! this.postId) return;
            // Eigener Aufruf der Download-Route; der Server streamt die Datei
            // als Anhang, ohne sie vorher abzulegen.
            window.location.href = `/admin/news/${this.postId}/social-image/download`;
        },
    }"
    x-on:open-news-social-image.window="show($event.detail)"
    x-on:keydown.escape.window="open && close()"
    x-cloak
>
    <div x-show="open" class="fixed inset-0 z-[70] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="social-image-modal-title">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="close()"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="relative z-10 flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4">
                <div class="min-w-0">
                    <h2 id="social-image-modal-title" class="text-base font-bold text-gray-900">Social-Media-Bild</h2>
                    <p class="mt-0.5 truncate text-sm text-gray-500" x-text="title"></p>
                </div>
                <button
                    type="button"
                    @click="close()"
                    class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    aria-label="Schließen"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid gap-6 overflow-y-auto px-6 py-6 md:grid-cols-[minmax(0,260px)_1fr]">
                <div class="mx-auto w-full max-w-[260px]">
                    <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-gray-50 shadow-inner" style="aspect-ratio: 9 / 16;">
                        <div x-show="loading" class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-sm text-gray-500">
                            <span class="h-7 w-7 animate-spin rounded-full border-[3px] border-gray-200 border-t-primary-500" aria-hidden="true"></span>
                            Bild wird erzeugt …
                        </div>

                        <div x-show="failed" x-cloak class="absolute inset-0 flex flex-col items-center justify-center gap-2 px-4 text-center text-sm text-red-700">
                            <span class="font-semibold">Vorschau fehlgeschlagen</span>
                            <span class="text-xs text-gray-500">Bitte erneut versuchen.</span>
                        </div>

                        <img
                            x-show="! loading && ! failed"
                            :src="previewSrc"
                            {{-- x-on:… statt @…, sonst frisst Blade das @error als eigene Direktive --}}
                            x-on:load="loading = false"
                            x-on:error="loading = false; failed = true"
                            alt="Vorschau des Social-Media-Bildes"
                            class="h-full w-full object-cover"
                        >
                    </div>
                </div>

                <div class="flex flex-col justify-between gap-6">
                    <div class="space-y-3 text-sm leading-6 text-gray-600">
                        <p>
                            Hochformat <span class="font-semibold text-gray-800">1080 × 1920</span> für Instagram Story,
                            Reels-Cover, Facebook und LinkedIn.
                        </p>
                        <p>
                            Enthalten sind Logo, Kategorie-Badge, Titel und Kurztext aus dieser News sowie das
                            Beitragsbild als Hintergrund.
                        </p>
                        <p class="rounded-lg bg-amber-50 px-3 py-2.5 text-xs text-amber-900 ring-1 ring-inset ring-amber-200">
                            Der Button im Bild ist nur aufgemalt und nicht klickbar. Setze den Link zur News wie gewohnt
                            in den Beitragstext beziehungsweise in die Story-Verlinkung.
                        </p>
                        <p class="text-xs text-gray-500">
                            Das Bild wird bei jedem Aufruf neu erzeugt und nicht auf dem Server gespeichert.
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            @click="download()"
                            :disabled="loading || failed"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-light focus:outline-none focus:ring-2 focus:ring-primary-200 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Bild herunterladen
                        </button>

                        <button
                            type="button"
                            @click="close()"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            Schließen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
