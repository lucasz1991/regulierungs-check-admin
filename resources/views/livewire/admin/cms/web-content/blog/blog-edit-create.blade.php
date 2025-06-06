<x-dialog-modal wire:model="show">
    <x-slot name="title">
        {{ $postId ? 'Beitrag bearbeiten' : 'Neuen Blogbeitrag erstellen' }}
    </x-slot>
    <x-slot name="content">
        <form wire:submit.prevent="save" class="space-y-4">
            <div>
                <label class="font-semibold">Titel</label>
                <input type="text" wire:model.defer="title" class="w-full border p-2 rounded" />
            </div>
            <div>
                <label class="font-semibold">Typ</label>
                <select wire:model.defer="type" class="w-full border p-2 rounded">
                    <option value="blog">Blog</option>
                    <option value="news">News</option>
                    <option value="info">Info</option>
                </select>
            </div>
            <div x-data="{ showNewCategory: false }" class="space-y-2">
                {{-- Kategorie-Dropdown --}}
                <div>
                    <label class="font-semibold">Kategorie</label>
                    <select wire:model.defer="category_id" class="w-full border p-2 rounded">
                        <option value="">– Keine –</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Button zum Anzeigen des Formulars --}}
                <div>
                    <button type="button"
                            @click="showNewCategory = !showNewCategory"
                            class="text-sm text-blue-600 hover:underline">
                        + Neue Kategorie anlegen
                    </button>
                </div>
                {{-- Eingabemaske für neue Kategorie --}}
                <div x-show="showNewCategory" x-collapse x-on:close-new-category.window="showNewCategory = false" class="bg-gray-100 p-4 rounded border space-y-2">
                    <div>
                        <label class="text-sm font-medium">Name</label>
                        <input type="text" wire:model.defer="newCategoryName" class="w-full border p-2 rounded">
                        @error('newCategoryName') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium">Slug (optional)</label>
                        <input type="text" wire:model.defer="newCategorySlug" class="w-full border p-2 rounded">
                    </div>
                    <div class="flex justify-end">
                        <button wire:click.prevent="createNewCategory"
                                type="button"
                                class="px-4 py-2 bg-green-600 text-white rounded">
                            Speichern
                        </button>
                    </div>
                </div>
            </div>
            <div>
                <label class="font-semibold">Kurztext</label>
                <textarea wire:model.defer="excerpt" class="w-full border p-2 rounded" rows="2"></textarea>
            </div>
            <div>
                <label class="font-semibold">Inhalt</label>
                <textarea wire:model.defer="body" class="w-full border p-2 rounded" rows="6"></textarea>
            </div>
            <div>
                <label class="font-semibold">Bild</label>
                <input type="file" wire:model="cover_image_file">
                @if($cover_image)
                    <img src="{{ $post->getCoverImageUrlAttribute() }}" class="h-24 mt-2 rounded">
                @endif
            </div>
            <div class="flex gap-4 items-center">
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model.defer="published"> Veröffentlicht
                </label>
                <input type="datetime-local" wire:model.defer="published_at" class="border p-2 rounded">
            </div>
        </form>
    </x-slot>
    <x-slot name="footer">
        <x-button wire:click="$set('show', false)">Abbrechen</x-button>
        <x-button wire:click="save">Speichern</x-button>
    </x-slot>
</x-dialog-modal>
