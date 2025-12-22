@php
    use Illuminate\Support\Str;

    // ⚠️ besser als Livewire Property führen; nur als Fallback/Beispiel:
    $editorKey ??= 'editor-' . ($postId ?? 'new') . '-' . Str::uuid();
@endphp

<x-dialog-modal wire:model="show">
    <x-slot name="title">
        {{ $postId ? 'Beitrag bearbeiten' : 'Neuen Blogbeitrag erstellen' }}
    </x-slot>

    <x-slot name="content">
        <form wire:submit.prevent="save" class="space-y-4">

            {{-- Tabs --}}
            <div x-data="{ openTab: 'content', showNewCategory: false }" class="">

                {{-- Tab Buttons --}}
                <div class="flex -mb-[1px] space-x-2">
                    <button type="button"
                        @click="openTab = 'content'"
                        :class="openTab === 'content'
                            ? 'text-primary-700 border-primary-600 bg-gray-100 border-b-0'
                            : 'text-gray-500 bg-white hover:bg-gray-50'"
                        class="px-4 py-2 text-sm font-semibold transition-all border border-gray-300 rounded-t-xl z-40">
                        <span class="w-max">Content</span>
                    </button>

                    <button type="button"
                        @click="openTab = 'settings'"
                        :class="openTab === 'settings'
                            ? 'text-primary-700 border-primary-600 bg-gray-100 border-b-0'
                            : 'text-gray-500 bg-white hover:bg-gray-50'"
                        class="px-4 py-2 text-sm font-semibold transition-all border border-gray-300 rounded-t-xl z-30">
                        <span class="w-max">Settings</span>
                    </button>

                    <button type="button"
                        @click="openTab = 'media'"
                        :class="openTab === 'media'
                            ? 'text-primary-700 border-primary-600 bg-gray-100 border-b-0'
                            : 'text-gray-500 bg-white hover:bg-gray-50'"
                        class="px-4 py-2 text-sm font-semibold transition-all border border-gray-300 rounded-t-xl z-20">
                        <span class="w-max">Media & Publish</span>
                    </button>
                </div>

                {{-- TAB: CONTENT --}}
                <div x-show="openTab === 'content'">
                    <div class="space-y-4 bg-gray-100 p-4 rounded-b-xl rounded-se-xl border border-gray-300">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Titel</label>
                                <input type="text" wire:model.defer="title"
                                    class="mt-1 block w-full border border-gray-200 rounded-xl px-4 py-2 bg-white shadow-sm focus:ring-2 focus:ring-primary-200 focus:border-primary-400">
                                @error('title') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Kurztext</label>
                                <textarea wire:model.defer="excerpt" rows="2"
                                    class="mt-1 block w-full border border-gray-200 rounded-xl px-4 py-2 bg-white shadow-sm focus:ring-2 focus:ring-primary-200 focus:border-primary-400"></textarea>
                                @error('excerpt') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Inhalt</label>
                            <div class="w-full border border-gray-200 bg-white rounded-2xl p-2 shadow-sm"
                                 wire:key="{{ $editorKey }}">
                                <x-ui.editor.toast wireModel="body" />
                            </div>
                            @error('body') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- TAB: SETTINGS --}}
                <div x-show="openTab === 'settings'">
                    <div class="space-y-4 bg-gray-100 p-4 rounded-b-xl rounded-se-xl border border-gray-300">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Typ</label>
                                <select wire:model.defer="type"
                                    class="mt-1 block w-full border border-gray-200 rounded-xl px-4 py-2 bg-white shadow-sm focus:ring-2 focus:ring-primary-200 focus:border-primary-400">
                                    <option value="blog">Blog</option>
                                    <option value="news">News</option>
                                    <option value="info">Info</option>
                                </select>
                                @error('type') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-semibold text-gray-800">Kategorie</label>

                                    <button type="button"
                                        @click="showNewCategory = !showNewCategory"
                                        class="text-xs font-semibold text-primary-700 hover:underline">
                                        + Neue Kategorie
                                    </button>
                                </div>

                                <select wire:model.defer="category_id"
                                    class="mt-1 block w-full border border-gray-200 rounded-xl px-4 py-2 bg-white shadow-sm focus:ring-2 focus:ring-primary-200 focus:border-primary-400">
                                    <option value="">– Keine –</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- New Category Box --}}
                        <div x-show="showNewCategory" x-collapse
                             x-on:close-new-category.window="showNewCategory = false"
                             class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700">Name</label>
                                    <input type="text" wire:model.defer="newCategoryName"
                                        class="mt-1 block w-full border border-gray-200 rounded-xl px-4 py-2 bg-white focus:ring-2 focus:ring-primary-200 focus:border-primary-400">
                                    @error('newCategoryName') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700">Slug (optional)</label>
                                    <input type="text" wire:model.defer="newCategorySlug"
                                        class="mt-1 block w-full border border-gray-200 rounded-xl px-4 py-2 bg-white focus:ring-2 focus:ring-primary-200 focus:border-primary-400">
                                </div>
                            </div>

                            <div class="flex justify-end gap-2">
                                <button type="button"
                                    @click="showNewCategory = false"
                                    class="px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-semibold hover:bg-gray-50">
                                    Abbrechen
                                </button>

                                <button type="button"
                                    wire:click.prevent="createNewCategory"
                                    class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                                    Speichern
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB: MEDIA --}}
                <div x-show="openTab === 'media'">
                    <div class="space-y-4 bg-gray-100 p-4 rounded-b-xl rounded-se-xl border border-gray-300">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Bild</label>
                                <input type="file" wire:model="cover_image_file"
                                    class="mt-1 block w-full text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-white hover:file:bg-primary-700">
                                @error('cover_image_file') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Vorschau</label>
                                <div class="mt-1 bg-white border border-gray-200 rounded-2xl p-2 shadow-sm">
                                    @if($cover_image)
                                        <img src="{{ $post->getCoverImageUrlAttribute() }}" class="h-28 w-full object-cover rounded-xl">
                                    @else
                                        <div class="h-28 rounded-xl flex items-center justify-center text-xs text-gray-500 bg-gray-50">
                                            Kein Bild gewählt
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                    <input type="checkbox" wire:model.defer="published"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-200">
                                    Veröffentlicht
                                </label>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">Veröffentlichungsdatum</span>
                                    <input type="datetime-local" wire:model.defer="published_at"
                                        class="border border-gray-200 rounded-xl px-4 py-2 bg-white text-sm shadow-sm focus:ring-2 focus:ring-primary-200 focus:border-primary-400">
                                </div>
                            </div>

                            @error('published_at') <div class="text-red-600 text-xs mt-2">{{ $message }}</div> @enderror
                        </div>

                    </div>
                </div>

            </div>
        </form>
    </x-slot>

    <x-slot name="footer">
        <div class="flex items-center justify-between w-full gap-3">
            <x-button wire:click="$set('show', false)">Abbrechen</x-button>
            <x-button wire:click="save" class="bg-primary hover:bg-primary-700 text-white">Speichern</x-button>
        </div>
    </x-slot>
</x-dialog-modal>

