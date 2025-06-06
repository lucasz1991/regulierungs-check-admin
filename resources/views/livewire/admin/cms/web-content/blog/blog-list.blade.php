<div>
    <h1 class="text-xl font-bold mb-4">Blogbeiträge verwalten</h1>
    <button onclick="Livewire.dispatch('open-blog-modal', { postId: null })">
                            Erstellen
                        </button>
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
