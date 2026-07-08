@php
    $layoutLabels = [
        'image_top' => 'Bild oben',
        'image_bottom' => 'Bild unten',
        'image_left' => 'Bild links',
        'image_right' => 'Bild rechts',
    ];
@endphp

<x-dialog-modal wire:model="show" :maxWidth="'4xl'">
    <x-slot name="title">
        <div class="flex flex-col gap-1">
            <span>{{ $postId ? 'News bearbeiten' : 'News erstellen' }}</span>
            <span class="text-sm font-normal text-gray-500">Inhalt, Sichtbarkeit, Layout und Bilder in einer Ansicht pflegen.</span>
        </div>
    </x-slot>

    <x-slot name="content">
        <form wire:submit.prevent="save" class="space-y-5">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
                <div class="space-y-5">
                    <section class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-900">Text</h3>
                            <p class="text-xs text-gray-500">Titel, Kurztext und vollstaendiger News-Inhalt.</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Titel</label>
                                <input
                                    type="text"
                                    wire:model.defer="title"
                                    class="mt-1 block w-full rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Titel der News"
                                >
                                @error('title') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Kurztext</label>
                                <textarea
                                    wire:model.defer="excerpt"
                                    rows="3"
                                    class="mt-1 block w-full rounded-lg border border-gray-200 bg-white px-4 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Kurzer Teaser fuer Listen und Vorschauen"
                                ></textarea>
                                @error('excerpt') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-800">Inhalt</label>
                                <div class="rounded-lg border border-gray-200 bg-white p-2">
                                    <x-ui.editor.trix wireModel="body" placeholder="News-Inhalt schreiben..." />
                                </div>
                                @error('body') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Bilder</h3>
                                <p class="text-xs text-gray-500">Reihenfolge, Alt-Text und Caption der News-Bilder.</p>
                            </div>

                            <label class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                                Bilder hinzufuegen
                                <input type="file" wire:model="imageFiles" multiple class="sr-only">
                            </label>
                        </div>

                        @error('imageFiles.*') <div class="mb-3 text-xs text-red-600">{{ $message }}</div> @enderror
                        <div wire:loading wire:target="imageFiles" class="mb-3 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-700">
                            Upload wird vorbereitet...
                        </div>

                        <div class="space-y-3">
                            @forelse($images as $index => $image)
                                <div class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 md:grid-cols-[112px_minmax(0,1fr)_96px]">
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
                                            <input
                                                type="text"
                                                wire:model.defer="images.{{ $index }}.alt"
                                                class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-700">Caption</label>
                                            <input
                                                type="text"
                                                wire:model.defer="images.{{ $index }}.caption"
                                                class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"
                                            >
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 md:flex-col md:items-stretch">
                                        <button type="button" wire:click="moveImageUp({{ $index }})" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs hover:bg-gray-50">Hoch</button>
                                        <button type="button" wire:click="moveImageDown({{ $index }})" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs hover:bg-gray-50">Runter</button>
                                        <button type="button" wire:click="removeImage({{ $index }})" class="rounded-lg border border-red-200 bg-white px-2 py-1 text-xs text-red-600 hover:bg-red-50">Entfernen</button>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5 text-center text-sm text-gray-500">
                                    Noch keine Bilder hinterlegt.
                                </div>
                            @endforelse
                        </div>
                    </section>
                </div>

                <aside class="space-y-5">
                    <section class="rounded-lg border border-gray-200 bg-white p-4">
                        <h3 class="text-sm font-semibold text-gray-900">Ver&ouml;ffentlichung</h3>
                        <div class="mt-4 space-y-4">
                            <label class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm font-semibold text-gray-800">
                                <span>
                                    <span class="block">Status</span>
                                    <span class="block text-xs font-normal text-gray-500">{{ $published ? 'Online sichtbar' : 'Als Entwurf speichern' }}</span>
                                </span>
                                <input type="checkbox" wire:model.defer="published" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </label>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Datum</label>
                                <input
                                    type="datetime-local"
                                    wire:model.defer="published_at"
                                    class="mt-1 block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                @error('published_at') <div class="mt-1 text-xs text-red-600">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-4">
                        <h3 class="text-sm font-semibold text-gray-900">Layout</h3>
                        <div class="mt-4 space-y-2">
                            @foreach($layoutLabels as $value => $label)
                                <label class="flex cursor-pointer items-center justify-between rounded-lg border p-3 text-sm transition {{ $layout === $value ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-100' : 'border-gray-200 bg-white hover:bg-gray-50' }}">
                                    <span>
                                        <span class="block font-semibold text-gray-900">{{ $label }}</span>
                                        <span class="block text-xs text-gray-500">{{ str_replace('_', ' ', $value) }}</span>
                                    </span>
                                    <input type="radio" wire:model.live="layout" value="{{ $value }}" class="text-blue-600 focus:ring-blue-500">
                                </label>
                            @endforeach
                        </div>
                        @error('layout') <div class="mt-2 text-xs text-red-600">{{ $message }}</div> @enderror
                    </section>
                </aside>
            </div>
        </form>
    </x-slot>

    <x-slot name="footer">
        <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end">
            <x-button wire:click="$set('show', false)" class="justify-center">Abbrechen</x-button>
            <x-button wire:click="save" class="justify-center bg-blue-600 text-white hover:bg-blue-700">
                Speichern
            </x-button>
        </div>
    </x-slot>
</x-dialog-modal>
