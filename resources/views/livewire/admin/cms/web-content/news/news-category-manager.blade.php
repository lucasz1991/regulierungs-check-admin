<x-dialog-modal wire:model="show" :maxWidth="'2xl'">
    <x-slot name="title">
        <div class="flex flex-col gap-1">
            <span>News-Kategorien verwalten</span>
            <span class="text-sm font-normal text-gray-500">Name, Farbe und Reihenfolge der Kategorie-Badges pflegen.</span>
        </div>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-5">
            <section class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ $categoryId ? 'Kategorie bearbeiten' : 'Neue Kategorie' }}</h3>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,1fr)_120px_120px]">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Name</label>
                        <input
                            type="text"
                            wire:model.defer="name"
                            class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="z.B. Urteil"
                        >
                        @error('name') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Farbe</label>
                        <input
                            type="color"
                            wire:model.defer="color"
                            class="mt-1 block h-9 w-full cursor-pointer rounded-lg border border-gray-200 p-1"
                        >
                        @error('color') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Reihenfolge</label>
                        <input
                            type="number"
                            min="0"
                            wire:model.defer="sort_order"
                            class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        @error('sort_order') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div>
                    <label class="mt-3 block text-xs font-semibold text-gray-700">Icon (optional, FontAwesome-Klasse)</label>
                    <input
                        type="text"
                        wire:model.defer="icon"
                        class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="z.B. fa-scale-balanced"
                    >
                    @error('icon') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    @if($categoryId)
                        <button type="button" wire:click="resetForm" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Abbrechen
                        </button>
                    @endif
                    <button type="button" wire:click="save" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        {{ $categoryId ? 'Aktualisieren' : 'Hinzuf&uuml;gen' }}
                    </button>
                </div>
            </section>

            <section class="space-y-2">
                @forelse($categories as $category)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3" wire:key="news-category-{{ $category->id }}">
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                                style="background-color: {{ $category->color }};"
                            >
                                @if($category->icon)
                                    <i class="fal {{ $category->icon }} text-[10px]"></i>
                                @endif
                                {{ $category->name }}
                            </span>
                            <span class="truncate text-xs text-gray-500">{{ $category->posts_count }} News</span>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button type="button" wire:click="edit({{ $category->id }})" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                Bearbeiten
                            </button>
                            <button
                                type="button"
                                wire:click="delete({{ $category->id }})"
                                wire:confirm="Kategorie wirklich l&ouml;schen? Zugeordnete News verlieren ihre Kategorie."
                                class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
                            >
                                L&ouml;schen
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5 text-center text-sm text-gray-500">
                        Noch keine Kategorien angelegt.
                    </div>
                @endforelse
            </section>
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-button wire:click="$set('show', false)" class="justify-center">Schlie&szlig;en</x-button>
    </x-slot>
</x-dialog-modal>
