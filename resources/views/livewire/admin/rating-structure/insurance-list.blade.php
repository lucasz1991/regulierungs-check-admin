<div class="px-2">
    <div class="flex justify-between mb-4">
        <h1 class="flex items-center justify-center text-lg px-2 py-1 w-max">
            <span class="w-max">Versicherungen</span>
            <span class="ml-2 mr-4 bg-white text-sky-600 text-xs shadow border border-sky-200 font-bold aspect-square px-2 py-1 flex items-center justify-center rounded-full  h-7  leading-none">
            {{ $insurancesAllCount }}
            </span>
            <x-list-comps.search-field wire:model.live.debounce.500ms="search" :results-count="$insurances->total()" />
        </h1>
        <div class="flex items-center gap-2">
            <x-link-button href="#" @click.prevent="$dispatch('open-insurance-form')" class="btn-xs py-0 leading-[0]">
                <svg  class="w-3 aspect-square" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M416 208H272V64c0-17.67-14.33-32-32-32h-32c-17.67 0-32 14.33-32 32v144H32c-17.67 0-32 14.33-32 32v32c0 17.67 14.33 32 32 32h144v144c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32V304h144c17.67 0 32-14.33 32-32v-32c0-17.67-14.33-32-32-32z"/></svg>
            </x-link-button>
            <x-dropdown class="">
                <x-slot name="trigger">
                    <x-link-button href="#" class="btn-xs py-0 leading-[0]">
                        <svg  class="w-3 aspect-square" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8.046 40.2299"><defs><style>.a{fill:#2e2f30;}</style></defs><path class="a" d="M4.023,8.046A4.023,4.023,0,1,1,8.046,4.023,4.02293,4.02293,0,0,1,4.023,8.046Zm0,16.0919a4.023,4.023,0,1,1,4.023-4.023A4.02293,4.02293,0,0,1,4.023,24.1379Zm0,16.092a4.023,4.023,0,1,1,4.023-4.023A4.02293,4.02293,0,0,1,4.023,40.2299Z"/></svg>
                    </x-link-button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link wire:click="analyzeAllInsuranceOnlineViaGpt" class="cursor-pointer">
                        <svg class="w-3 aspect-square mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M80 160l26.66-53.33L160 80l-53.34-26.67L80 0 53.34 53.34 0 80l53.34 26.67L80 160zm144-64l16-32 32-16-32-16-16-32-16 32-32 16 32 16 16 32zm234.66 245.33L432 288l-26.66 53.33L352 368l53.34 26.67L432 448l26.66-53.33L512 368l-53.34-26.67zM400 192c8.84 0 16-7.16 16-16v-27.96l91.87-101.83c5.72-6.32 5.48-16.02-.55-22.05L487.84 4.69c-6.03-6.03-15.73-6.27-22.05-.55L186.6 256H144c-8.84 0-16 7.16-16 16v36.87L10.53 414.84c-13.57 12.28-14.1 33.42-1.16 46.36l41.43 41.43c12.94 12.94 34.08 12.41 46.36-1.16L376.34 192H400z"/></svg>
                       Ai Analyze All 
                    </x-dropdown-link>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
    @livewire('admin.rating-structure.insurance.create-edit')
    @if (session()->has('message'))
        <div class="bg-green-100 text-green-700 p-2 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 text-red-700 p-2 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif
    <div class="w-full">
        <div class="grid grid-cols-12 bg-gray-100 p-2 font-semibold text-gray-700 border-b border-gray-300 text-left">
            <div class="col-span-6">Name</div>
            <div class="col-span-2">Versicherungentypen</div>
            <div class="col-span-2">Status</div>
            <div class="col-span-2">Erstellung</div>
        </div>
        <div class="min-w-max lg:min-w-full" x-sort="$dispatch('orderInsurance', { item: $item, position: $position })">
            @foreach ($insurances as $insurance)
                <div x-sort:item="{{ $insurance }}">
                    <div class="grid grid-cols-12 relative border-b py-2 px-2 items-center">
                        <div class="col-span-6 pr-4">
                            <div>
                                <div class="flex gap-4 justify-start items-center">
                                    <div class="shrink-0 flex-none ">
                                        @if ($insurance->logo)
                                                <img src="{{ $insurance->getLogoImageUrlAttribute() }}"
                                                    alt=""
                                                    class=" h-6 mx-auto object-contain rounded">
                                            @else
                                                <div class=" w-min rounded flex items-center justify-center text-sm border px-1 font-medium shadow-sm" style="background-color: {{ $insurance->style['bg_color'] ?? '#eee' }}; color: {{ $insurance->style['font_color'] ?? '#333' }}; border-color: {{ $insurance->style['border_color'] ?? '#ccc' }};">
                                                    {{ strtoupper(substr( $insurance->initials, 0 ,8)) }}
                                                </div>
                                            @endif
                                    </div>
                                    <div class="grow max-w-full">
                                        <h2 class=" break-words w-full truncate ">
                                            {{ strlen($insurance->name) > 25 ? substr($insurance->name, 0, 25) . '...' : $insurance->name }}
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-2 text-xs text-gray-700">
                            <span class="bg-gray-200 px-2 py-1 rounded text-xs">{{ $insurance->insuranceTypes->count() }}</span>
                        </div>

                        <div class="col-span-2 text-sm">
                            @if($insurance->is_active)
                                <span class="text-green-700 bg-green-50 px-2 py-1 text-xs rounded">Aktiv</span>
                            @else
                                <span class="text-red-700 bg-red-50 px-2 py-1 text-xs rounded">Inaktiv</span>
                            @endif
                        </div>

                        <div class="col-span-2 text-xs text-gray-500">
                            <span>{{ $insurance->created_at->locale('de')->diffForHumans() }}</span>
                            <br>
                            <small>(Bearbeitet: {{ $insurance->updated_at->locale('de')->diffForHumans() }})</small>
                        </div>

                        <div class="absolute right-0">
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="w-max text-center px-4 py-2 text-xl font-semibold hover:bg-gray-100 rounded-lg">
                                    &#x22EE;
                                </button>
                                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-lg z-10">
                                    <ul>
                                        <li>
                                            <a href="#"    @click.prevent="$dispatch('open-insurance-form', [{{ $insurance->id }}])"
                                            class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">
                                                Bearbeiten
                                            </a>
                                        </li>
                                        <li>
                                            <button wire:click="toggleActive({{ $insurance->id }})" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">
                                                {{ $insurance->is_active ? 'Deaktivieren' : 'Aktivieren' }}
                                            </button>
                                        </li>
                                        <li>
                                            <button wire:click="deleteInsurance({{ $insurance->id }})" @click="open = false" class="block w-full text-left px-4 py-2 text-red-600 hover:bg-gray-100">
                                                Löschen
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">
            {{ $insurances->links() }}
        </div>
    </div>
</div>
