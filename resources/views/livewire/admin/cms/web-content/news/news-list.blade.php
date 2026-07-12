<div>
    @php($newsPreviewBaseUrl = rtrim((string) config('news-preview.base_url', 'https://www.regulierungs-check.de'), '/'))

    <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">News verwalten</h1>
            <p class="text-sm text-gray-500">{{ $posts->total() }} Artikel fuer den oeffentlichen News-Bereich.</p>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <a
                href="{{ $newsPreviewBaseUrl }}/"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-800 shadow-sm transition hover:border-teal-300 hover:bg-teal-100"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                User-Vorschau
            </a>

            <label class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">User-Bereich sichtbar</span>
                    <span class="block text-xs text-gray-500">{{ $newsEnabled ? 'News ist online sichtbar' : 'News bleibt fuer User ausgeblendet' }}</span>
                </span>
                <span class="relative inline-flex cursor-pointer items-center">
                    <input type="checkbox" wire:model.live="newsEnabled" class="peer sr-only">
                    <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-green-500"></span>
                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                </span>
            </label>

            <button
                type="button"
                onclick="Livewire.dispatch('open-news-category-manager')"
                class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Kategorien
            </button>

            <button
                type="button"
                onclick="Livewire.dispatch('open-news-modal', { postId: null })"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                News erstellen
            </button>
        </div>
    </div>

    <x-ui.datatables.table-wrapper
        label="News"
        columns="md:grid-cols-[minmax(220px,1.6fr)_minmax(120px,0.7fr)_100px_150px_72px] lg:grid-cols-[minmax(260px,1.7fr)_minmax(150px,0.8fr)_110px_160px_72px]"
    >
        <x-slot:header>
            <div data-role="datatable-heading" role="columnheader" class="min-w-0">Titel</div>
            <div data-role="datatable-heading" role="columnheader" class="hidden min-w-0 md:block">Kategorie</div>
            <div data-role="datatable-heading" role="columnheader" class="hidden min-w-0 md:block">Bilder</div>
            <div data-role="datatable-heading" role="columnheader" class="min-w-0">Status</div>
            <div data-role="datatable-heading" role="columnheader" class="text-right">Aktion</div>
        </x-slot:header>

        @forelse($posts as $post)
            @php
                $imageCount = count($post->newsImages());
            @endphp

            <x-ui.datatables.table-item
                :item="$post->id"
                columns="md:grid-cols-[minmax(220px,1.6fr)_minmax(120px,0.7fr)_100px_150px_72px] lg:grid-cols-[minmax(260px,1.7fr)_minmax(150px,0.8fr)_110px_160px_72px]"
                wire:key="news-row-{{ $post->id }}"
            >
                <div data-role="datatable-cell" data-label="Titel" role="cell" class="min-w-0">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 md:hidden">Titel</span>
                    <div class="truncate font-semibold text-gray-900">{{ $post->title }}</div>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                        <span>{{ $post->published_at ? $post->published_at->format('d.m.Y H:i') : 'Kein Datum' }}</span>
                        @if($post->excerpt)
                            <span class="hidden max-w-[360px] truncate text-gray-400 lg:inline">{{ $post->excerpt }}</span>
                        @endif
                    </div>
                </div>

                <div data-role="datatable-cell" data-label="Kategorie" role="cell" class="hidden min-w-0 text-gray-700 md:block">
                    @if($post->newsCategory)
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                            style="background-color: {{ $post->newsCategory->color }};"
                        >
                            {{ $post->newsCategory->name }}
                        </span>
                    @else
                        <span class="text-xs text-gray-400">Keine</span>
                    @endif
                </div>

                <div data-role="datatable-cell" data-label="Bilder" role="cell" class="hidden min-w-0 text-gray-700 md:block">
                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                        {{ $imageCount }} {{ $imageCount === 1 ? 'Bild' : 'Bilder' }}
                    </span>
                </div>

                <div data-role="datatable-cell" data-label="Status" role="cell" class="min-w-0">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 md:hidden">Status</span>
                    @if($post->published)
                        <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">Ver&ouml;ffentlicht</span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Entwurf</span>
                    @endif
                </div>

                <div data-role="datatable-cell" data-label="Aktion" role="cell" class="relative flex justify-end">
                    <div x-data="{ open: false }" class="relative">
                        <button
                            type="button"
                            @click="open = !open"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:bg-gray-100"
                            aria-label="News-Aktionen"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3ZM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3ZM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0Z" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-cloak
                            class="absolute right-0 z-50 mt-2 w-44 rounded-lg border border-gray-200 bg-white py-1 text-left text-sm shadow-lg"
                        >
                            <a
                                href="{{ $newsPreviewBaseUrl }}/news/{{ rawurlencode($post->slug) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                @click="open = false"
                                class="block w-full px-4 py-2 text-left text-teal-700 hover:bg-teal-50 hover:text-teal-900"
                            >
                                Auf User-Seite ansehen
                            </a>
                            <button
                                type="button"
                                @click="open = false; Livewire.dispatch('open-news-modal', { postId: {{ $post->id }} })"
                                class="block w-full px-4 py-2 text-left text-gray-700 hover:bg-blue-50 hover:text-blue-700"
                            >
                                Bearbeiten
                            </button>
                            <button
                                type="button"
                                wire:click="delete({{ $post->id }})"
                                wire:confirm="Diese News wirklich loeschen?"
                                class="block w-full px-4 py-2 text-left text-red-600 hover:bg-red-50"
                            >
                                L&ouml;schen
                            </button>
                        </div>
                    </div>
                </div>
            </x-ui.datatables.table-item>
        @empty
            <div data-role="datatable-empty" class="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500 md:rounded-none md:border-0">
                Keine News gefunden.
            </div>
        @endforelse

        <x-slot:footer>
            {{ $posts->links() }}
        </x-slot:footer>
    </x-ui.datatables.table-wrapper>

    <livewire:admin.cms.web-content.news.news-edit-create />
    <livewire:admin.cms.web-content.news.news-category-manager />
</div>
