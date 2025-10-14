<div>
    <x-dialog-modal wire:model="showModal">
        <x-slot name="title">
            <div class="flex items-center justify-between space-x-2">
                <div>
                    {{ $insuranceId ? 'Versicherung bearbeiten' : 'Neue Versicherung erstellen' }}
                </div>
                <div>
                    @if($insuranceId)
                    <x-dropdown class="">
                        <x-slot name="trigger">
                            <x-link-button href="#" class="btn-xs py-0 leading-[0]">
                                <svg  class="w-3 aspect-square" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8.046 40.2299"><defs><style>.a{fill:#2e2f30;}</style></defs><path class="a" d="M4.023,8.046A4.023,4.023,0,1,1,8.046,4.023,4.02293,4.02293,0,0,1,4.023,8.046Zm0,16.0919a4.023,4.023,0,1,1,4.023-4.023A4.02293,4.02293,0,0,1,4.023,24.1379Zm0,16.092a4.023,4.023,0,1,1,4.023-4.023A4.02293,4.02293,0,0,1,4.023,40.2299Z"/></svg>
                            </x-link-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link wire:click.stop="analyzeInsuranceOnlineViaGpt()" class="cursor-pointer">
                                <svg class="w-3 aspect-square mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M80 160l26.66-53.33L160 80l-53.34-26.67L80 0 53.34 53.34 0 80l53.34 26.67L80 160zm144-64l16-32 32-16-32-16-16-32-16 32-32 16 32 16 16 32zm234.66 245.33L432 288l-26.66 53.33L352 368l53.34 26.67L432 448l26.66-53.33L512 368l-53.34-26.67zM400 192c8.84 0 16-7.16 16-16v-27.96l91.87-101.83c5.72-6.32 5.48-16.02-.55-22.05L487.84 4.69c-6.03-6.03-15.73-6.27-22.05-.55L186.6 256H144c-8.84 0-16 7.16-16 16v36.87L10.53 414.84c-13.57 12.28-14.1 33.42-1.16 46.36l41.43 41.43c12.94 12.94 34.08 12.41 46.36-1.16L376.34 192H400z"/></svg>
                                Ai Analyze
                            </x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                    @endif
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="mb-4 grid grid-cols-5 gap-4">
                <div class="col-span-4 mb-4">
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" wire:model.defer="name" class="mt-1 block w-full border rounded px-4 py-2">
                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-1 mb-6 flex items-end">
                        <label for="is_active" class="flex items-end cursor-pointer">
                            <input 
                                id="is_active" 
                                name="is_active" 
                                type="checkbox" 
                                wire:model.live="is_active" 
                                class="sr-only peer" 
                            />
                            <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            <span class="ms-3 text-sm font-medium ">Aktiv</span>
                        </label>
                    </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                <textarea wire:model.defer="description" rows="3" class="mt-1 block w-full border rounded px-4 py-2"></textarea>
                @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Hilf Text</label>
                <textarea wire:model.defer="helptext" rows="3" class="mt-1 block w-full border rounded px-4 py-2"></textarea>
                @error('helptext') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>


            <div x-data="{ openTab: 'incuranceType' }" class="">
                <!-- Tabs -->
                <div class="flex -mb-[1px] space-x-2">
                    
                    <button @click="openTab = 'incuranceType'" 
                        :class="openTab === 'incuranceType' ? 'text-blue-600 border-blue-600 bg-gray-100 border-b-0' : 'text-gray-500 bg-white'" 
                        class="px-4 py-2  text-sm font-medium transition-all border  border-gray-300 rounded-t-lg z-30">
                        <h1 class="flex items-center justify-center">
                            <span class="w-max">Versicherungstypen</span>
                            <span class="ml-2 bg-white text-sky-600 text-xs shadow border border-sky-200 font-bold aspect-square px-2 py-1 flex items-center justify-center rounded-full h-7 leading-none">
                                {{ count($assignedInsuranceTypes) }}
                            </span>
                        </h1>
                    </button>
                    <button @click="openTab = 'incuranceDisplaySettings'" 
                        :class="openTab === 'incuranceDisplaySettings' ? 'text-blue-600 border-blue-600 bg-gray-100 border-b-0' : 'text-gray-500 bg-white'" 
                        class="px-4 py-2  text-sm font-medium transition-all border  border-gray-300 rounded-t-lg z-30">
                        <h1 class="flex items-center justify-center">
                            <span class="w-max">Darstellung</span>
                        </h1>
                    </button>
                </div>

                <div x-show="openTab === 'incuranceType'" class="">
                    <div class="space-y-4 bg-gray-100 p-4 rounded-b-lg rounded-se-lg border border-gray-300  z-10">
                        <!-- Sortierbare Liste -->
                        <div class="mt-4" x-data="{ addInsuranceTypeOpen: false }">
                                <div class="flex justify-end items-center mb-3">
                                    
                                    <!-- Dropdown für neue Versicherung -->
                                    <div class="relative" >
                                        <button @click="addInsuranceTypeOpen = !addInsuranceTypeOpen" type="button" class="flex items-center text-sm px-2 py-1 bg-white border rounded shadow-sm hover:bg-gray-50">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Hinzufügen
                                        </button>
                                        
                                    </div>
                                </div>
                                <div x-show="addInsuranceTypeOpen" @click.away="addInsuranceTypeOpen = false" class="mt-2 mb-5  bg-white border rounded">
                                            <div class="p-2 flex items-center space-x-3">
                                                <select wire:model="insuranceTypeToAdd" class="w-full border rounded px-2 py-1">
                                                    <option value="">Bitte auswählen</option>
                                                    @foreach ($availableInsuranceTypes as $availableInsuranceType)
                                                        <option value="{{ $availableInsuranceType->id }}">{{ $availableInsuranceType->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button  wire:click="addInsuranceType" class="flex items-center text-sm px-2 py-1 bg-white border rounded shadow-sm hover:bg-gray-50">
                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                    
                                                </button>
                                            </div>
                                        </div>
                                <div class="min-w-max lg:min-w-full max-h-96 overflow-y-scroll p-3 bg-white scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 shadow-inner border scroll-container" x-sort="$dispatch('reorderAssignedInsuranceSubTypes', { item: $item, position: $position })">
                                    @foreach ($assignedInsuranceTypes as $assignedInsuranceType)
                                        <div x-sort:item="{ id: {{ $assignedInsuranceType['id'] }}, name: '{{ $assignedInsuranceType['name'] }}' }">
                                            <div class="bg-blue-50 px-3 py-2 rounded flex justify-between items-center border mb-2">
                                                <span class="text-sm">{{ $assignedInsuranceType['name'] }}</span>
                                                <button type="button" class="text-red-500" wire:click="removeInsuranceType({{ $assignedInsuranceType['id'] }})">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>                                                
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                    </div>
                </div>

                <div x-show="openTab === 'incuranceDisplaySettings'" class="">
                    <div class="space-y-4 bg-gray-100 p-4 rounded-b-lg rounded-se-lg border border-gray-300  z-10">
                        <div class="mb-4 grid grid-cols-5 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Initialen</label>
                                <input type="text" wire:model.defer="initials" class="mt-1 block w-full border rounded px-4 py-2">
                                @error('initials') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Schrift</label>
                                <input type="text" wire:model.defer="style.font_color" class="mt-1 block w-full border rounded px-4 py-2">
                                @error('style.font_color') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hintergrund</label>
                                <input type="text" wire:model.defer="style.bg_color" class="mt-1 block w-full border rounded px-4 py-2">
                                @error('style.bg_color') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Umrandung </label>
                                <input type="text" wire:model.defer="style.border_color" class="mt-1 block w-full border rounded px-4 py-2">
                                @error('style.border_color') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <div>
                                <label class="font-semibold">Logo </label>
                                <input type="file" wire:model="logo_image_file">
                                @if($logo_image_file)
                                    <img src="{{ $insurance->getLogoImageUrlAttribute() }}" class="h-24 mt-2 rounded">
                                @endif
                            </div>
                        </div>
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
