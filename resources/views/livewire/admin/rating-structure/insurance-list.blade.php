<div class="px-2">
    <div class="flex justify-between mb-4">
        <h1 class="flex items-center justify-center text-lg px-2 py-1 w-max">
            <span class="w-max">Versicherungen</span>
            <span class="ml-2 bg-white text-sky-600 text-xs shadow border border-sky-200 font-bold aspect-square px-2 py-1 flex items-center justify-center rounded-full  h-7  leading-none">
            {{ $insurances->total() }}
            </span>
        </h1>
        <x-link-button href="#" @click.prevent="$dispatch('open-insurance-form')" class="btn-xs py-0 leading-[0]">
            +
        </x-link-button>
    </div>
    <div>
            <button wire:click.stop="analyzeAllInsuranceOnlineViaGpt()"
            class="block w-full text-left px-4 py-2 hover:bg-blue-100">Analyze All Insurance Online</button>
            </div>
    @livewire('admin.rating-structure.insurance.create-edit')

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
