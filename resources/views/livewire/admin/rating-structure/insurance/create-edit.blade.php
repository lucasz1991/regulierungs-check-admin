<div>
    <x-dialog-modal wire:model="showModal">
        <x-slot name="title">
            {{ $insuranceId ? 'Versicherung bearbeiten' : 'Neue Versicherung erstellen' }}
        </x-slot>

        <x-slot name="content">
            <div class="mb-4 grid grid-cols-5 gap-4">
                <div class="col-span-4 mb-4">
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" wire:model.defer="name" class="mt-1 block w-full border rounded px-4 py-2">
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-1 mb-4">
                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                    <input type="text" wire:model.defer="slug" class="mt-1 block w-full border rounded px-4 py-2">
                    @error('slug') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                <textarea wire:model.defer="description" rows="3" class="mt-1 block w-full border rounded px-4 py-2"></textarea>
                @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4 grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Initialen</label>
                    <input type="text" wire:model.defer="initials" class="mt-1 block w-full border rounded px-4 py-2">
                    @error('initials') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Farbe (HEX)</label>
                    <input type="text" wire:model.defer="color" class="mt-1 block w-full border rounded px-4 py-2">
                    @error('color') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="flex items-center mt-6">
                    <input type="checkbox" wire:model.defer="is_active" class="mr-2">
                    <label class="text-sm font-medium text-gray-700">Aktiv</label>
                </div>
            </div>

            
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center space-x-3">
                <x-button wire:click="save" class="btn-xs text-sm">
                    Speichern
                </x-button>
                <x-button wire:click="$set('showModal', false)" class="btn-xs text-sm">
                    Schließen
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
