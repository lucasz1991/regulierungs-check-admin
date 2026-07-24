@php
    $publicationStatus = 'Entwurf';

    if ($published) {
        $publicationStatus = $published_at
            && $published_at > now('Europe/Berlin')->format('Y-m-d\TH:i')
                ? 'Geplant'
                : 'Veröffentlicht';
    }
@endphp

<x-dialog-modal id="news-edit-create-modal" wire:model="show" :maxWidth="'4xl'">
    <x-slot name="title">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <span class="block text-xl font-semibold tracking-tight text-gray-950">
                    {{ $postId ? 'News bearbeiten' : 'News erstellen' }}
                </span>
                <span class="mt-1 block max-w-2xl text-sm font-normal leading-5 text-gray-500">
                    Metadaten, Veröffentlichung und Bilder pflegen. Den vollständigen Inhalt gestaltest du anschließend im Page Builder.
                </span>
            </div>

            @if($isDirty)
                <span class="inline-flex w-fit shrink-0 items-center gap-2 rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                    Ungespeicherte Änderungen
                </span>
            @endif
        </div>
    </x-slot>

    <x-slot name="content">
        <form
            id="news-edit-create-form"
            wire:submit.prevent="save"
            class="space-y-5"
            novalidate
        >
            @if($errors->any())
                <div
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-900"
                    role="alert"
                    aria-labelledby="news-validation-title"
                >
                    <div class="flex gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-sm font-bold text-red-700" aria-hidden="true">
                            !
                        </span>
                        <div class="min-w-0">
                            <p id="news-validation-title" class="font-semibold">Bitte prüfe die markierten Felder.</p>
                            <ul class="mt-1 list-disc space-y-0.5 pl-5 text-xs leading-5 text-red-700">
                                @foreach(array_unique($errors->all()) as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
                <div class="min-w-0 space-y-5">
                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm shadow-gray-200/40">
                        <header class="border-b border-gray-100 bg-gray-50/80 px-5 py-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-xs font-bold text-primary-800" aria-hidden="true">
                                    1
                                </span>
                                <div>
                                    <h3 class="font-semibold text-gray-950">Titel und Kurztext</h3>
                                    <p class="mt-0.5 text-xs leading-5 text-gray-500">
                                        Diese Angaben erscheinen in Listen, Teasern und Suchergebnissen.
                                    </p>
                                </div>
                            </div>
                        </header>

                        <div class="space-y-5 p-5">
                            <div>
                                <div class="flex items-end justify-between gap-3">
                                    <label for="news-title" class="block text-sm font-semibold text-gray-800">
                                        Titel <span class="text-red-600" aria-hidden="true">*</span>
                                    </label>
                                    <span class="text-xs tabular-nums text-gray-400">{{ mb_strlen($title) }}/255</span>
                                </div>
                                <input
                                    id="news-title"
                                    type="text"
                                    wire:model.live.debounce.400ms="title"
                                    maxlength="255"
                                    autocomplete="off"
                                    class="mt-1.5 block w-full rounded-lg px-3.5 py-2.5 text-sm text-gray-950 shadow-sm transition focus:ring-2 focus:ring-primary-200 focus:ring-offset-0 {{ $errors->has('title') ? 'border-red-400 focus:border-red-500' : 'border-gray-300 focus:border-primary-500' }}"
                                    placeholder="Aussagekräftiger Titel der News"
                                    aria-required="true"
                                    aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}"
                                    aria-describedby="news-title-help{{ $errors->has('title') ? ' news-title-error' : '' }}"
                                >
                                <p id="news-title-help" class="mt-1.5 text-xs leading-5 text-gray-500">
                                    Mindestens 3 Zeichen. Aus dem Titel wird automatisch die URL erzeugt.
                                </p>
                                @error('title')
                                    <p id="news-title-error" class="mt-1 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <div class="flex items-end justify-between gap-3">
                                    <label for="news-excerpt" class="block text-sm font-semibold text-gray-800">Kurztext</label>
                                    <span class="text-xs tabular-nums text-gray-400">{{ mb_strlen((string) $excerpt) }}/255</span>
                                </div>
                                <textarea
                                    id="news-excerpt"
                                    wire:model.live.debounce.400ms="excerpt"
                                    rows="3"
                                    maxlength="255"
                                    class="mt-1.5 block w-full resize-y rounded-lg px-3.5 py-2.5 text-sm leading-6 text-gray-950 shadow-sm transition focus:ring-2 focus:ring-primary-200 focus:ring-offset-0 {{ $errors->has('excerpt') ? 'border-red-400 focus:border-red-500' : 'border-gray-300 focus:border-primary-500' }}"
                                    placeholder="Kurzer Teaser für Übersichten und Vorschauen"
                                    aria-invalid="{{ $errors->has('excerpt') ? 'true' : 'false' }}"
                                    aria-describedby="news-excerpt-help{{ $errors->has('excerpt') ? ' news-excerpt-error' : '' }}"
                                ></textarea>
                                <p id="news-excerpt-help" class="mt-1.5 text-xs leading-5 text-gray-500">
                                    Optional. Formuliere den wichtigsten Inhalt möglichst in ein bis zwei Sätzen.
                                </p>
                                @error('excerpt')
                                    <p id="news-excerpt-error" class="mt-1 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="rounded-xl border border-primary-100 bg-primary-50 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-semibold text-primary-900">Vollständiger News-Inhalt</h4>
                                        <p class="mt-1 text-xs leading-5 text-primary-800/80">
                                            Layout, Fließtext und weitere Inhaltsblöcke werden im Page Builder bearbeitet.
                                        </p>
                                        @if($postId && $isDirty)
                                            <p class="mt-1 text-xs font-medium text-amber-800">
                                                Speichere die Änderungen, bevor du den Page Builder öffnest.
                                            </p>
                                        @endif
                                    </div>

                                    @if($postId)
                                        <button
                                            type="button"
                                            wire:click="openPagebuilder"
                                            wire:loading.attr="disabled"
                                            wire:target="openPagebuilder"
                                            @disabled($isDirty)
                                            class="inline-flex shrink-0 items-center justify-center rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <span wire:loading.remove wire:target="openPagebuilder">Page Builder öffnen</span>
                                            <span wire:loading wire:target="openPagebuilder">Wird geöffnet …</span>
                                        </button>
                                    @else
                                        <span class="inline-flex shrink-0 rounded-lg bg-white px-3 py-2 text-xs font-medium text-primary-800 ring-1 ring-inset ring-primary-200">
                                            Nach dem ersten Speichern verfügbar
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm shadow-gray-200/40">
                        <header class="border-b border-gray-100 bg-gray-50/80 px-5 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-xs font-bold text-primary-800" aria-hidden="true">
                                        2
                                    </span>
                                    <div>
                                        <h3 class="font-semibold text-gray-950">Bilder</h3>
                                        <p class="mt-0.5 text-xs leading-5 text-gray-500">
                                            Das erste Bild der Reihenfolge wird als Titelbild verwendet.
                                        </p>
                                    </div>
                                </div>

                                <label
                                    for="news-image-files"
                                    class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-primary-200 bg-white px-4 py-2.5 text-sm font-semibold text-primary-800 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 focus-within:ring-2 focus-within:ring-primary-300 focus-within:ring-offset-2"
                                >
                                    Bilder auswählen
                                    <input
                                        id="news-image-files"
                                        type="file"
                                        wire:model="imageFiles"
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                        multiple
                                        class="sr-only"
                                    >
                                </label>
                            </div>
                        </header>

                        <div class="space-y-4 p-5">
                            <p class="text-xs leading-5 text-gray-500">
                                Erlaubt sind JPG, PNG, GIF und WebP mit jeweils höchstens 4 MB.
                            </p>

                            @error('imageFiles')
                                <p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                            @enderror
                            @error('imageFiles.*')
                                <p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                            @enderror

                            <div
                                wire:loading
                                wire:target="imageFiles"
                                class="rounded-lg border border-primary-100 bg-primary-50 px-3 py-2 text-xs font-medium text-primary-800"
                                role="status"
                            >
                                Die ausgewählten Bilder werden vorbereitet …
                            </div>

                            @if(count($imageFiles) > 0)
                                <div class="rounded-xl border border-primary-100 bg-primary-50/60 p-3">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold text-primary-900">
                                            {{ count($imageFiles) }} {{ count($imageFiles) === 1 ? 'neues Bild' : 'neue Bilder' }} vorgemerkt
                                        </p>
                                        <span class="text-xs text-primary-700">Upload erfolgt beim Speichern</span>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach($imageFiles as $index => $file)
                                            <div
                                                class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 ring-1 ring-inset ring-primary-100"
                                                wire:key="pending-news-image-{{ $index }}-{{ md5($file->getClientOriginalName()) }}"
                                            >
                                                <div class="min-w-0">
                                                    <p class="truncate text-xs font-semibold text-gray-800">{{ $file->getClientOriginalName() }}</p>
                                                    <p class="mt-0.5 text-xs tabular-nums text-gray-500">
                                                        {{ number_format(max(0, (int) $file->getSize()) / 1024 / 1024, 1, ',', '.') }} MB
                                                    </p>
                                                </div>
                                                <button
                                                    type="button"
                                                    wire:click="removeImageFile({{ $index }})"
                                                    class="shrink-0 rounded-md px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-300"
                                                    aria-label="{{ $file->getClientOriginalName() }} aus der Auswahl entfernen"
                                                >
                                                    Entfernen
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-3">
                                @forelse($images as $index => $image)
                                    <article
                                        class="grid grid-cols-1 gap-4 rounded-xl border border-gray-200 bg-gray-50/80 p-3 sm:grid-cols-[112px_minmax(0,1fr)] xl:grid-cols-[112px_minmax(0,1fr)_112px]"
                                        wire:key="news-image-{{ md5((string) ($image['path'] ?? $index)) }}"
                                    >
                                        <div class="relative overflow-hidden rounded-lg bg-gray-100">
                                            @if(!empty($image['url']))
                                                <img
                                                    src="{{ $image['url'] }}"
                                                    alt="{{ $image['alt'] ?? $title }}"
                                                    class="h-28 w-full object-cover sm:h-full sm:min-h-28"
                                                >
                                            @else
                                                <div class="flex h-28 items-center justify-center px-3 text-center text-xs text-gray-500 sm:h-full sm:min-h-28">
                                                    Vorschau nicht verfügbar
                                                </div>
                                            @endif

                                            <span class="absolute left-2 top-2 rounded-md bg-gray-950/80 px-2 py-1 text-[10px] font-semibold text-white backdrop-blur-sm">
                                                {{ $index === 0 ? 'Titelbild' : 'Bild '.($index + 1) }}
                                            </span>
                                        </div>

                                        <div class="min-w-0 space-y-3">
                                            <div>
                                                <div class="flex items-center justify-between gap-2">
                                                    <label for="news-image-{{ $index }}-alt" class="block text-xs font-semibold text-gray-700">
                                                        Alternativtext
                                                    </label>
                                                    <span class="text-[10px] text-gray-400">für Screenreader</span>
                                                </div>
                                                <input
                                                    id="news-image-{{ $index }}-alt"
                                                    type="text"
                                                    wire:model.blur="images.{{ $index }}.alt"
                                                    maxlength="255"
                                                    class="mt-1 block w-full rounded-lg px-3 py-2 text-sm shadow-sm transition focus:ring-2 focus:ring-primary-200 focus:ring-offset-0 {{ $errors->has("images.{$index}.alt") ? 'border-red-400 focus:border-red-500' : 'border-gray-300 focus:border-primary-500' }}"
                                                    placeholder="Was ist auf dem Bild zu sehen?"
                                                    aria-invalid="{{ $errors->has("images.{$index}.alt") ? 'true' : 'false' }}"
                                                    aria-describedby="{{ $errors->has("images.{$index}.alt") ? "news-image-{$index}-alt-error" : '' }}"
                                                >
                                                @error("images.{$index}.alt")
                                                    <p id="news-image-{{ $index }}-alt-error" class="mt-1 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="news-image-{{ $index }}-caption" class="block text-xs font-semibold text-gray-700">
                                                    Bildunterschrift
                                                </label>
                                                <input
                                                    id="news-image-{{ $index }}-caption"
                                                    type="text"
                                                    wire:model.blur="images.{{ $index }}.caption"
                                                    maxlength="255"
                                                    class="mt-1 block w-full rounded-lg px-3 py-2 text-sm shadow-sm transition focus:ring-2 focus:ring-primary-200 focus:ring-offset-0 {{ $errors->has("images.{$index}.caption") ? 'border-red-400 focus:border-red-500' : 'border-gray-300 focus:border-primary-500' }}"
                                                    placeholder="Optionaler Hinweis unter dem Bild"
                                                    aria-invalid="{{ $errors->has("images.{$index}.caption") ? 'true' : 'false' }}"
                                                    aria-describedby="{{ $errors->has("images.{$index}.caption") ? "news-image-{$index}-caption-error" : '' }}"
                                                >
                                                @error("images.{$index}.caption")
                                                    <p id="news-image-{{ $index }}-caption-error" class="mt-1 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            @error("images.{$index}.path")
                                                <p class="text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="flex flex-col gap-2 sm:col-start-2 sm:flex-row sm:items-center xl:col-start-auto xl:flex-col xl:items-stretch">
                                            <button
                                                type="button"
                                                wire:click="moveImageUp({{ $index }})"
                                                @disabled($index === 0)
                                                class="flex-1 rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-40 xl:flex-none"
                                                aria-label="Bild {{ $index + 1 }} nach oben verschieben"
                                            >
                                                Nach oben
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="moveImageDown({{ $index }})"
                                                @disabled($index === count($images) - 1)
                                                class="flex-1 rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-40 xl:flex-none"
                                                aria-label="Bild {{ $index + 1 }} nach unten verschieben"
                                            >
                                                Nach unten
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="removeImage({{ $index }})"
                                                class="flex-1 rounded-lg border border-red-200 bg-white px-2.5 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-300 xl:mt-auto xl:flex-none"
                                                aria-label="Bild {{ $index + 1 }} entfernen"
                                            >
                                                Entfernen
                                            </button>
                                        </div>
                                    </article>
                                @empty
                                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-5 py-8 text-center">
                                        <p class="text-sm font-semibold text-gray-700">Noch keine Bilder hinterlegt</p>
                                        <p class="mt-1 text-xs leading-5 text-gray-500">
                                            Wähle oben ein oder mehrere Bilder aus. Das erste Bild wird zum Titelbild.
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-5">
                    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm shadow-gray-200/40">
                        <header class="border-b border-gray-100 bg-gray-50/80 px-4 py-3.5">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="font-semibold text-gray-950">Veröffentlichung</h3>
                                <span @class([
                                    'inline-flex rounded-md px-2 py-1 text-[10px] font-bold',
                                    'bg-gray-100 text-gray-700' => $publicationStatus === 'Entwurf',
                                    'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200' => $publicationStatus === 'Geplant',
                                    'bg-green-50 text-green-800 ring-1 ring-inset ring-green-200' => $publicationStatus === 'Veröffentlicht',
                                ])>
                                    {{ $publicationStatus }}
                                </span>
                            </div>
                        </header>

                        <div class="space-y-4 p-4">
                            <label for="news-published" class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-gray-200 bg-gray-50 p-3">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-gray-800">News veröffentlichen</span>
                                    <span class="mt-0.5 block text-xs leading-5 text-gray-500">
                                        {{ $published ? 'Ab dem gewählten Zeitpunkt sichtbar' : 'Nur im Admin-Bereich sichtbar' }}
                                    </span>
                                </span>
                                <span class="relative inline-flex shrink-0 items-center">
                                    <input
                                        id="news-published"
                                        type="checkbox"
                                        wire:model.live="published"
                                        class="peer sr-only"
                                        role="switch"
                                        aria-invalid="{{ $errors->has('published') ? 'true' : 'false' }}"
                                    >
                                    <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary-300 peer-focus-visible:ring-offset-2"></span>
                                    <span class="pointer-events-none absolute left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                            @error('published')
                                <p class="text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                            @enderror

                            <div>
                                <label for="news-published-at" class="block text-sm font-semibold text-gray-800">
                                    Veröffentlichung ab
                                    @if($published)
                                        <span class="text-red-600" aria-hidden="true">*</span>
                                    @endif
                                </label>
                                <input
                                    id="news-published-at"
                                    type="datetime-local"
                                    wire:model.blur="published_at"
                                    step="60"
                                    class="mt-1.5 block w-full rounded-lg px-3 py-2.5 text-sm shadow-sm transition focus:ring-2 focus:ring-primary-200 focus:ring-offset-0 {{ $errors->has('published_at') ? 'border-red-400 focus:border-red-500' : 'border-gray-300 focus:border-primary-500' }}"
                                    aria-required="{{ $published ? 'true' : 'false' }}"
                                    aria-invalid="{{ $errors->has('published_at') ? 'true' : 'false' }}"
                                    aria-describedby="news-published-at-help{{ $errors->has('published_at') ? ' news-published-at-error' : '' }}"
                                >
                                <p id="news-published-at-help" class="mt-1.5 text-xs leading-5 text-gray-500">
                                    Zeitzone: Europe/Berlin
                                </p>
                                @error('published_at')
                                    <p id="news-published-at-error" class="mt-1 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <fieldset class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm shadow-gray-200/40">
                        <legend class="sr-only">News-Kategorie</legend>
                        <div class="border-b border-gray-100 bg-gray-50/80 px-4 py-3.5">
                            <h3 class="font-semibold text-gray-950" aria-hidden="true">Kategorie</h3>
                            <p class="mt-1 text-xs leading-5 text-gray-500">
                                Die Kategorie erscheint als farbiges Badge.
                            </p>
                        </div>

                        <div class="max-h-80 space-y-2 overflow-y-auto p-3">
                            <label class="flex cursor-pointer items-center justify-between rounded-lg border p-3 text-sm transition focus-within:ring-2 focus-within:ring-primary-300 {{ empty($news_category_id) ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-100' : 'border-gray-200 bg-white hover:bg-gray-50' }}">
                                <span class="font-semibold text-gray-700">Keine Kategorie</span>
                                <input
                                    type="radio"
                                    wire:model.live="news_category_id"
                                    value=""
                                    class="text-primary focus:ring-primary-300"
                                >
                            </label>

                            @foreach($categories as $category)
                                <label class="flex cursor-pointer items-center justify-between rounded-lg border p-3 text-sm transition focus-within:ring-2 focus-within:ring-primary-300 {{ (string) $news_category_id === (string) $category->id ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-100' : 'border-gray-200 bg-white hover:bg-gray-50' }}">
                                    <span class="flex min-w-0 items-center gap-2.5">
                                        <span
                                            class="inline-block h-3.5 w-3.5 shrink-0 rounded-full ring-2 ring-white shadow-sm"
                                            style="background-color: {{ $category->color }};"
                                            aria-hidden="true"
                                        ></span>
                                        <span class="truncate font-semibold text-gray-900">{{ $category->name }}</span>
                                    </span>
                                    <input
                                        type="radio"
                                        wire:model.live="news_category_id"
                                        value="{{ $category->id }}"
                                        class="shrink-0 text-primary focus:ring-primary-300"
                                    >
                                </label>
                            @endforeach
                        </div>

                        @error('news_category_id')
                            <p class="border-t border-red-100 bg-red-50 px-4 py-2.5 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </aside>
            </div>
        </form>
    </x-slot>

    <x-slot name="footer">
        <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-h-5 text-left">
                <span wire:loading wire:target="imageFiles" class="text-xs font-medium text-primary-800" role="status">
                    Bilder werden vorbereitet …
                </span>
                <span wire:loading wire:target="save" class="text-xs font-medium text-primary-800" role="status">
                    Änderungen werden sicher gespeichert …
                </span>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
                <button
                    type="button"
                    wire:click="closeModal"
                    x-on:click="$dispatch('close')"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-300 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Abbrechen
                </button>
                <button
                    type="submit"
                    form="news-edit-create-form"
                    wire:loading.attr="disabled"
                    wire:target="save, imageFiles"
                    class="inline-flex min-w-36 items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="save">
                        {{ $postId ? 'Änderungen speichern' : 'News speichern' }}
                    </span>
                    <span wire:loading wire:target="save">Wird gespeichert …</span>
                </button>
            </div>
        </div>
    </x-slot>
</x-dialog-modal>
