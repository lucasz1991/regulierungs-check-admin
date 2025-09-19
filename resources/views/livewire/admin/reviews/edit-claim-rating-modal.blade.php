<div
    x-data="{
        modalIsOpen: @entangle('showFormModal'),
        insuranceTypeId: @entangle('insuranceTypeId'),
        insuranceSubTypeId: @entangle('insuranceSubTypeId'),
        insuranceId: @entangle('insuranceId'),
        regulationType: @entangle('regulationType'),
        selectedDates: @entangle('selectedDates'),
        is_closed: @entangle('is_closed'),
        initPickr(refName) {
            const mode = this.is_closed ? 'range' : 'single';
            flatpickr(refName, {
                dateFormat: 'd.m.Y',
                defaultDate: this.selectedDates || null,
                locale: 'de',
                inline: true,
                allowInput: true,
                mode: mode,
                onChange: (dates, str) => { this.selectedDates = str; },
                onReady: (d, str, inst) => {
                    if (!this.selectedDates) {
                        const t = new Date();
                        t.setMonth(t.getMonth() - 6);
                        inst.changeMonth(t.getMonth(), false);
                        inst.changeYear(t.getFullYear());
                    }
                }
            });
        },
        initChoices(el, onChange) {
            const inst = new Choices(el, {
                removeItemButton: false,
                shouldSort: false,
                searchEnabled: true,
                placeholder: true,
                searchChoices: true,
                searchResultLimit: 100,
                fuseOptions: { includeScore: true, threshold: 0.8 },
                position: 'bottom',
                itemSelectText: '',
                searchPlaceholderValue: 'Suchen...',
            });
            el.addEventListener('change', (e) => onChange(e.target.value));
        }
    }"
    x-cloak
>
    <div>
        <div
            x-show="modalIsOpen"
            x-transition.opacity.duration.200ms
            x-trap.inert.noscroll="modalIsOpen"
            class="fixed inset-0 z-40 bg-black/20 px-4 pb-8 pt-14 backdrop-blur-md overflow-y-auto"
            role="dialog" aria-modal="true"
        >
            <div
                x-show="modalIsOpen"
                x-transition:enter="transition ease-out duration-200 delay-100 motion-reduce:transition-opacity"
                x-transition:enter-start="opacity-0 scale-50"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative mx-auto max-w-5xl w-full container bg-gray-50 border border-outline rounded-lg shadow-xl px-6 py-6"
            >
                <button
                    type="button"
                    @click="modalIsOpen = false"
                    class="absolute top-2 right-2 z-50 p-2 rounded-full bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Abbrechen"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <h2 class="text-xl font-bold mb-6">Bewertung bearbeiten</h2>

                {{-- Stammdaten --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div wire:ignore>
                        <label class="block text-sm font-medium mb-1">Versicherungskategorie</label>
                        <select x-init="initChoices($el, v => { insuranceTypeId = v })" class="w-full border rounded px-3 py-2">
                            <option value="">Bitte auswählen</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}" @selected($insuranceTypeId == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('insuranceTypeId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div wire:ignore>
                        <label class="block text-sm font-medium mb-1">Versicherungsart</label>
                        <select x-init="initChoices($el, v => { insuranceSubTypeId = v })" class="w-full border rounded px-3 py-2">
                            <option value="">Bitte auswählen</option>
                            @foreach ($insuranceSubTypes ?? [] as $st)
                                <option value="{{ $st->id }}" @selected($insuranceSubTypeId == $st->id)>{{ $st->name }}</option>
                            @endforeach
                        </select>
                        @error('insuranceSubTypeId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div wire:ignore>
                        <label class="block text-sm font-medium mb-1">Gesellschaft</label>
                        <select x-init="initChoices($el, v => { insuranceId = v })" class="w-full border rounded px-3 py-2">
                            <option value="">Bitte auswählen</option>
                            @foreach ($insurances ?? [] as $ins)
                                <option value="{{ $ins->id }}" @selected($insuranceId == $ins->id)>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                        @error('insuranceId')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Fremdversicherung --}}
                @if ($thirdPartyInsuranceAllowed)
                    <label class="inline-flex items-center mb-6">
                        <input type="checkbox" wire:model.live="thirdPartyInsurance" class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                        <span class="ml-2 text-sm text-gray-700">Fremdversicherung</span>
                    </label>
                @endif
                @if($rating->user?->isAdmin())
                
                {{-- Fallstatus --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Fallstatus</label>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $opts = [
                                'vollzahlung' => 'Vollzahlung',
                                'teilzahlung' => 'Teilzahlung',
                                'ablehnung'   => 'Ablehnung',
                                'austehend'   => 'Ausstehend',
                            ];
                        @endphp
                        @foreach ($opts as $val => $label)
                            <button type="button"
                                wire:click="$set('regulationType','{{ $val }}')"
                                class="px-3 py-2 rounded border
                                @if($regulationType === $val) border-blue-500 bg-blue-100 @else bg-white hover:bg-gray-100 @endif">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    @error('regulationType')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Details + Kommentar --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-secondary text-white rounded-lg p-4">
                        <h3 class="text-base font-semibold mb-3">Regulierungsdetails</h3>

                        @php
                            $detailSets = [
                                'vollzahlung' => [
                                    'Schnelle und unkomplizierte Abwicklung',
                                    'Gute Kommunikation und Transparenz',
                                    'Faire und angemessene Regulierung',
                                    'Hervorragender Kundenservice',
                                    'Erwartungen vollständig erfüllt',
                                    'Andere Gründe',
                                ],
                                'teilzahlung' => [
                                    'Nur ein Teil des Schadens wurde anerkannt',
                                    'Es gab eine Selbstbeteiligung',
                                    'Die Versicherung hat die Summe nach Gutachten gekürzt',
                                    'Kulanzzahlung statt voller Erstattung',
                                    'Unklare Kommunikation / keine nachvollziehbare Begründung',
                                    'Andere Gründe',
                                ],
                                'ablehnung' => [
                                    'Der Schaden sei nicht versichert',
                                    'Formfehler oder Fristversäumnis',
                                    'Verdacht auf Eigenverschulden',
                                    'Kein nachvollziehbarer Grund genannt',
                                    'Andere Gründe',
                                ],
                                'austehend' => [
                                    'Warte auf Rückmeldung der Versicherung',
                                    'Benötigte Unterlagen wurden noch nicht eingereicht',
                                    'Versicherung benötigt mehr Zeit zur Bearbeitung',
                                    'Unklare Kommunikation seitens der Versicherung',
                                    'Andere Gründe',
                                ],
                            ];
                            $current = $detailSets[$regulationType] ?? [];
                        @endphp

                        <div class="space-y-3">
                            @foreach ($current as $label)
                                <label class="flex items-center gap-3 border-b border-white/40 pb-3 last:border-0">
                                    <input type="checkbox" wire:model.live="regulationDetails" value="{{ $label }}" class="sr-only peer">
                                    <div class="relative h-6 w-11 rounded-full border border-outline bg-gray-300
                                        after:absolute after:left-[0.125rem] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all
                                        peer-checked:bg-blue-200 peer-checked:after:translate-x-5 peer-checked:after:bg-blue-500">
                                    </div>
                                    <span class="grow">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('regulationDetails')<p class="text-xs text-red-200 mt-2">{{ $message }}</p>@enderror
                    </div>

                    <div x-data="{ cnt: (@js(strlen($regulationComment ?? ''))) }">
                        <label class="block text-sm font-medium mb-2">Kommentar (optional)</label>
                        <textarea
                            wire:model.live.debounce.500ms="regulationComment"
                            x-on:input="cnt = $event.target.value.length"
                            maxlength="255"
                            class="w-full border rounded px-3 py-2 min-h-[140px]"></textarea>
                        <div class="text-xs mt-1" :class="cnt>=255 ? 'text-red-600' : 'text-gray-500'">
                            <span x-text="`${cnt}/255 Zeichen`"></span>
                        </div>
                        @error('regulationComment')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Vertrag --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-secondary rounded-lg p-4 text-white">
                        <h3 class="text-base font-semibold mb-3">Vertrag & Beträge</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm mb-1">Deckungssumme € (optional)</label>
                                <input x-mask:dynamic="$money($input, '.', '')" wire:model.live.debounce.250ms="contractDetails.contract_coverage_amount" class="w-full border rounded px-3 py-2 text-gray-900" />
                                @error('contractDetails.contract_coverage_amount')<p class="text-xs text-red-200 mt-1">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm mb-1">Selbstbeteiligung €</label>
                                <input x-mask:dynamic="$money($input, '.', '')" wire:model.live.debounce.250ms="contractDetails.contract_deductible_amount" class="w-full border rounded px-3 py-2 text-gray-900" />
                                @error('contractDetails.contract_deductible_amount')<p class="text-xs text-red-200 mt-1">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="block text-sm mb-1">Schadenshöhe €</label>
                                <input x-mask:dynamic="$money($input, '.', '')" wire:model.live.debounce.250ms="contractDetails.claim_amount" class="w-full border rounded px-3 py-2 text-gray-900" />
                                @error('contractDetails.claim_amount')<p class="text-xs text-red-200 mt-1">{{ $message }}</p>@enderror
                            </div>

                            @if (in_array($regulationType, ['vollzahlung','teilzahlung']))
                                <div>
                                    <label class="block text-sm mb-1">Regulierungshöhe €</label>
                                    <input x-mask:dynamic="$money($input, '.', '')" wire:model.live.debounce.250ms="contractDetails.claim_settlement_amount" class="w-full border rounded px-3 py-2 text-gray-900" />
                                    @error('contractDetails.claim_settlement_amount')<p class="text-xs text-red-200 mt-1">{{ $message }}</p>@enderror
                                </div>
                            @endif
                        </div>
                    </div>

                    <div x-data="{ cnt: (@js(strlen($contractDetails['textarea_value'] ?? ''))) }">
                        <label class="block text-sm font-medium mb-2">Weitere Angaben zum Vertrag</label>
                        <textarea
                            wire:model.live.debounce.500ms="contractDetails.textarea_value"
                            x-on:input="cnt = $event.target.value.length"
                            maxlength="255"
                            class="w-full border rounded px-3 py-2 min-h-[140px]"></textarea>
                        <div class="text-xs mt-1" :class="cnt>=255 ? 'text-red-600' : 'text-gray-500'">
                            <span x-text="`${cnt}/255 Zeichen`"></span>
                        </div>
                        @error('contractDetails.textarea_value')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Zeitraum --}}
                <div class="mb-8" x-data>
                    <h3 class="text-base font-semibold mb-4">Zeitraum</h3>

                    <div class="inline-flex mb-3">
                        <button type="button"
                                class="px-2 py-1 text-sm font-medium bg-white border rounded-l text-blue-600 border-blue-500">
                            {{ $started_at ?? 'Start' }}
                        </button>
                        <button type="button"
                                class="px-2 py-1 text-sm font-medium bg-white border rounded-r text-blue-600 border-blue-500">
                            {{ $is_closed ? ($ended_at ?? 'Ende') : 'offen' }}
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        <div>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" class="h-4 w-4" wire:model.live="is_closed" />
                                <span>Fall abgeschlossen</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">Wenn nicht abgeschlossen, wird nur ein Startdatum gespeichert.</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Datum wählen</label>
                            <input type="text" x-ref="dates" class="hidden" />
                            <div x-init="$nextTick(() => initPickr($refs.dates))"></div>
                            @error('started_at')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                            @error('ended_at')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Dynamische Fragen --}}
                @if (!empty($variableQuestions))
                    <div class="mb-8">
                        <h3 class="text-base font-semibold mb-4">Zusatzfragen</h3>
                        <div class="space-y-6">
                            @foreach ($variableQuestions as $q)
                                @php $field = "answers.".$q['title']; @endphp
                                <div>
                                    <p class="font-medium mb-2">{{ $q['question_text'] }}</p>

                                    @switch($q['type'])
                                        @case('text')
                                            <input type="text" class="w-full border rounded px-3 py-2" wire:model.defer="{{ $field }}">
                                            @break
                                        @case('number')
                                            <input type="number" class="w-full border rounded px-3 py-2" wire:model.defer="{{ $field }}">
                                            @break
                                        @case('date')
                                            <input type="text" class="w-full border rounded px-3 py-2" wire:model.defer="{{ $field }}" placeholder="z.B. 31.12.2025">
                                            @break
                                        @case('textarea')
                                            <textarea rows="4" class="w-full border rounded px-3 py-2" wire:model.defer="{{ $field }}"></textarea>
                                            @break
                                        @case('boolean')
                                            <div class="inline-flex rounded-full overflow-hidden bg-gray-50 border border-gray-300">
                                                <label class="px-4 py-2 cursor-pointer">
                                                    <input type="radio" class="hidden" wire:model.live="{{ $field }}" value="1"> Ja
                                                </label>
                                                <label class="px-4 py-2 cursor-pointer">
                                                    <input type="radio" class="hidden" wire:model.live="{{ $field }}" value="0"> Nein
                                                </label>
                                            </div>
                                            @break
                                        @case('rating')
                                            <div class="flex gap-1">
                                                @for ($i=1;$i<=5;$i++)
                                                    <label class="cursor-pointer">
                                                        <input type="radio" class="hidden" wire:model.live="{{ $field }}" value="{{ $i }}">
                                                        <svg class="w-8 h-8 {{ ((int)data_get($this,$field,0)) >= $i ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.204 3.698a1 1 0 00.95.69h3.894c.969 0 1.371 1.24.588 1.81l-3.15 2.286a1 1 0 00-.364 1.118l1.204 3.698c.3.921-.755 1.688-1.54 1.118l-3.15-2.286a1 1 0 00-1.176 0l-3.15 2.286c-.784.57-1.838-.197-1.539-1.118l1.203-3.698a1 1 0 00-.364-1.118L2.414 9.125c-.783-.57-.38-1.81.588-1.81h3.894a1 1 0 00.951-.69l1.202-3.698z"/>
                                                        </svg>
                                                    </label>
                                                @endfor
                                            </div>
                                            @break
                                        @default
                                            <input type="text" class="w-full border rounded px-3 py-2" wire:model.defer="{{ $field }}">
                                    @endswitch

                                    @error($field)<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @endif

                {{-- Footer --}}
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" class="px-4 py-2 rounded border bg-white hover:bg-gray-100"
                        @click="modalIsOpen=false">
                        Abbrechen
                    </button>
                    <button type="button" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700"
                        wire:click="update">
                        Speichern
                    </button>
                </div>
            </div>
        </div>
                        </div>
</div>
