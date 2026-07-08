<div>
    <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">News verwalten</h1>
            <p class="text-sm text-gray-500">Artikel fuer den oeffentlichen News-Bereich erstellen und pflegen.</p>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <label class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
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
                onclick="Livewire.dispatch('open-news-modal', { postId: null })"
                class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                News erstellen
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full table-auto text-left">
            <thead>
                <tr class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="px-4 py-3">Titel</th>
                    <th class="px-4 py-3">Layout</th>
                    <th class="px-4 py-3">Bilder</th>
                    <th class="px-4 py-3">Veröffentlicht</th>
                    <th class="px-4 py-3 text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($posts as $post)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">{{ $post->title }}</div>
                            <div class="mt-1 text-xs text-gray-500">{{ $post->published_at ? $post->published_at->format('d.m.Y H:i') : 'Kein Datum' }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ str_replace('_', ' ', $post->layout ?? 'image_top') }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ count($post->newsImages()) }}</td>
                        <td class="px-4 py-3">
                            @if($post->published)
                                <span class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">Ja</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Nein</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    onclick="Livewire.dispatch('open-news-modal', { postId: {{ $post->id }} })"
                                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    Bearbeiten
                                </button>
                                <button
                                    type="button"
                                    wire:click="delete({{ $post->id }})"
                                    wire:confirm="Diese News wirklich loeschen?"
                                    class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
                                >
                                    Löschen
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Keine News gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <livewire:admin.cms.web-content.news.news-edit-create />
</div>
