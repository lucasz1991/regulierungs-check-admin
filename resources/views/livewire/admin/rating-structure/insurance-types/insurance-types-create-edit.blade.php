<div>
    <x-dialog-modal wire:model="showModal">
        <x-slot name="title">
            {{ $insuranceTypeId ? 'Versicherungstyp bearbeiten' : 'Neuer Versicherungstyp' }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
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
                <div class="flex items-center mt-6">
                    <input type="checkbox" wire:model.defer="is_active" class="mr-2">
                    <label class="text-sm font-medium text-gray-700">Aktiv</label>
                </div>

                <!-- Sortierbare Liste -->
                <div class="mt-4">
                    <div class="flex justify-between items-center mb-3">
                        <label class="block text-sm font-medium text-gray-700">Zugeordnete Versicherungen</label>
                        <!-- Dropdown für neue Versicherung -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" type="button" class="flex items-center text-sm px-2 py-1 bg-white border rounded shadow-sm hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-64 bg-white border rounded shadow-md z-10">
                                <div class="p-2 flex items-center space-x-3">
                                    <select wire:model="insuranceToAdd" class="w-full border rounded px-2 py-1">
                                        <option value="">Bitte auswählen</option>
                                        @foreach ($availableInsurances as $insurance)
                                            <option value="{{ $insurance->id }}">{{ $insurance->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-button class="mt-2 w-full" wire:click="addInsurance">Hinzufügen</x-button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="min-w-max lg:min-w-full" x-sort="$dispatch('reorderInsurance', { item: $item, position: $position })">
                        @foreach ($assignedInsurances as $insurance)
                            <div x-sort:item="{ id: {{ $insurance['id'] }}, name: '{{ $insurance['name'] }}' }">
                                <div class="bg-gray-100 px-3 py-2 rounded flex justify-between items-center border mb-2">
                                    <span class="text-sm">{{ $insurance['name'] }}</span>
                                    <button type="button" class="text-red-500 text-xs" wire:click="removeInsurance({{ $insurance['id'] }})">
                                        Entfernen
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

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
