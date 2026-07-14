<div>
    <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Blogbeitr&auml;ge verwalten</h1>
            <p class="text-sm text-gray-500">{{ $posts->count() }} Beitr&auml;ge. Die Inhalte bleiben auch bei deaktiviertem User-Bereich bearbeitbar.</p>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <label class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">User-Bereich sichtbar</span>
                    <span class="block text-xs text-gray-500">{{ $blogEnabled ? 'Blog ist online sichtbar' : 'Blog bleibt für User ausgeblendet' }}</span>
                </span>
                <span class="relative inline-flex cursor-pointer items-center">
                    <input type="checkbox" wire:model.live="blogEnabled" class="peer sr-only">
                    <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-green-500"></span>
                    <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                </span>
            </label>

            <button
                type="button"
                onclick="Livewire.dispatch('open-blog-modal', { postId: null })"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                Blogbeitrag erstellen
            </button>
        </div>
    </div>

    <table class="w-full table-auto text-left border border-gray-300">
        <thead>
            <tr class="bg-gray-100">
                <th class="px-3 py-2">Titel</th>
                <th class="px-3 py-2">Kategorie</th>
                <th class="px-3 py-2">Veröffentlicht</th>
                <th class="px-3 py-2">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            @forelse($posts as $post)
                <tr class="border-t">
                    <td class="px-3 py-2">{{ $post->title }}</td>
                    <td class="px-3 py-2">{{ optional($post->category)->name ?? '—' }}</td>
                    <td class="px-3 py-2">
                        @if($post->published)
                            <span class="text-green-600">Ja</span>
                        @else
                            <span class="text-gray-500">Nein</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 space-x-2">
                        <button onclick="Livewire.dispatch('open-blog-modal', { postId: {{ $post->id }} })">
                            Bearbeiten
                        </button>
                        <x-link-button wire:click="delete({{ $post->id }})" class="text-red-600">Löschen</x-link-button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-3 py-4 text-center text-gray-500">Keine Blogbeiträge gefunden.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Modal-Komponente am Ende --}}
    <livewire:admin.cms.web-content.blog.blog-edit-create />
</div>
