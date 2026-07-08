@php
    $layoutLabels = [
        'image_top' => 'Bild oben',
        'image_bottom' => 'Bild unten',
        'image_left' => 'Bild links',
        'image_right' => 'Bild rechts',
    ];
@endphp

<x-dialog-modal wire:model="show">
    <x-slot name="title">
        {{ $postId ? 'News bearbeiten' : 'News erstellen' }}
    </x-slot>

    <x-slot name="content">
        <form wire:submit.prevent="save" class="space-y-4">
            <div x-data="{ openTab: 'content' }">
                <div class="flex -mb-[1px] gap-2">
                    <button type="button"
                        @click="openTab = 'content'"
                        :class="openTab === 'content' ? 'text-blue-700 border-blue-600 bg-gray-100 border-b-0' : 'text-gray-500 bg-white hover:bg-gray-50'"
                        class="rounded-t-xl border border-gray-300 px-4 py-2 text-sm font-semibold">
                        Content
                    </button>
                    <button type="button"
                        @click="openTab = 'media'"
                        :class="openTab === 'media' ? 'text-blue-700 border-blue-600 bg-gray-100 border-b-0' : 'text-gray-500 bg-white hover:bg-gray-50'"
                        class="rounded-t-xl border border-gray-300 px-4 py-2 text-sm font-semibold">
                        Layout & Bilder
                    </button>
                    <button type="button"
                        @click="openTab = 'publish'"
                        :class="openTab === 'publish' ? 'text-blue-700 border-blue-600 bg-gray-100 border-b-0' : 'text-gray-500 bg-white hover:bg-gray-50'"
                        class="rounded-t-xl border border-gray-300 px-4 py-2 text-sm font-semibold">
                        Veröffentlichung
                    </button>
                </div>

                <div x-show="openTab === 'content'" x-cloak>
                    <div class="space-y-4 rounded-b-xl rounded-se-xl border border-gray-300 bg-gray-100 p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Titel</label>
                                <input type="text" wire:model.defer="title"
                                    class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-4 py-2 shadow-sm">
                                @error('title') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Kurztext</label>
                                <textarea wire:model.defer="excerpt" rows="2"
                                    class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-4 py-2 shadow-sm"></textarea>
                                @error('excerpt') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-800">Inhalt</label>
                            <x-ui.editor.trix wireModel="body" placeholder="News-Inhalt schreiben..." />
                            @error('body') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div x-show="openTab === 'media'" x-cloak>
                    <div class="space-y-4 rounded-b-xl rounded-se-xl border border-gray-300 bg-gray-100 p-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800">Layout</label>
                            <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-4">
                                @foreach($layoutLabels as $value => $label)
                                    <label class="cursor-pointer rounded-xl border bg-white p-3 text-sm shadow-sm transition {{ $layout === $value ? 'border-blue-500 ring-2 ring-blue-100' : 'border-gray-200' }}">
                                        <input type="radio" wire:model.live="layout" value="{{ $value }}" class="sr-only">
                                        <span class="font-semibold text-gray-900">{{ $label }}</span>
                                        <span class="mt-1 block text-xs text-gray-500">{{ str_replace('_', ' ', $value) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('layout') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800">Bilder hinzufügen</label>
                            <input type="file" wire:model="imageFiles" multiple
                                class="mt-1 block w-full text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-white hover:file:bg-blue-700">
                            @error('imageFiles.*') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                            <div wire:loading wire:target="imageFiles" class="mt-2 text-xs text-gray-500">Upload wird vorbereitet...</div>
                        </div>

                        <div class="space-y-3">
                            @forelse($images as $index => $image)
                                <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-3 md:grid-cols-[120px_1fr_auto]">
                                    <div class="overflow-hidden rounded-lg bg-gray-100">
                                        @if(!empty($image['url']))
                                            <img src="{{ $image['url'] }}" alt="{{ $image['alt'] ?? $title }}" class="h-24 w-full object-cover">
                                        @else
                                            <div class="flex h-24 items-center justify-center text-xs text-gray-500">Kein Bild</div>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700">Alt-Text</label>
                                            <input type="text" wire:model.defer="images.{{ $index }}.alt"
                                                class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700">Caption</label>
                                            <input type="text" wire:model.defer="images.{{ $index }}.caption"
                                                class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 md:flex-col">
                                        <button type="button" wire:click="moveImageUp({{ $index }})" class="rounded-lg border border-gray-200 px-2 py-1 text-xs hover:bg-gray-50">Hoch</button>
                                        <button type="button" wire:click="moveImageDown({{ $index }})" class="rounded-lg border border-gray-200 px-2 py-1 text-xs hover:bg-gray-50">Runter</button>
                                        <button type="button" wire:click="removeImage({{ $index }})" class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">Entfernen</button>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-gray-300 bg-white p-5 text-center text-sm text-gray-500">
                                    Noch keine Bilder hinterlegt.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div x-show="openTab === 'publish'" x-cloak>
                    <div class="space-y-4 rounded-b-xl rounded-se-xl border border-gray-300 bg-gray-100 p-4">
                        <label class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white p-4 text-sm font-semibold text-gray-800">
                            <input type="checkbox" wire:model.defer="published" class="rounded border-gray-300 text-blue-600">
                            Veröffentlicht
                        </label>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800">Veröffentlichungsdatum</label>
                            <input type="datetime-local" wire:model.defer="published_at"
                                class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-4 py-2 shadow-sm">
                            @error('published_at') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </x-slot>

    <x-slot name="footer">
        <div class="flex w-full items-center justify-between gap-3">
            <x-button wire:click="$set('show', false)">Abbrechen</x-button>
            <x-button wire:click="save" class="bg-blue-600 text-white hover:bg-blue-700">Speichern</x-button>
        </div>
    </x-slot>
</x-dialog-modal>
