<div class="" wire:loading.class="cursor-wait">
    <h1 class="text-2xl font-semibold mb-6">Konfiguration</h1>
    <!-- Tabs Navigation -->
    <div x-data="{ activeTab: 'none' }">
        <div class="border-b mb-6">
            <nav class="-mb-px flex space-x-8">
                <button 
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'none', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'none' }"
                    @click="activeTab = 'none'"
                >
                    Übersicht
                </button>
                <button 
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'basis', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'basis' }"
                    @click="activeTab = 'basis'"
                >
                    Basis
                </button>
                <button 
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'buchung', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'buchung' }"
                    @click="activeTab = 'buchung'"
                >
                    Buchung
                </button>
                <button 
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'produkte', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'produkte' }"
                    @click="activeTab = 'produkte'"
                >
                    Produkte
                </button>
                <button 
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'verkaufsflaeche', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'verkaufsflaeche' }"
                    @click="activeTab = 'verkaufsflaeche'"
                >
                    Verkaufsfläche
                </button>
                <button 
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'mails', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'mails' }"
                    @click="activeTab = 'mails'"
                >
                    Mail's
                </button>
                <button 
                    class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'api', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'api' }"
                    @click="activeTab = 'api'"
                >
                    Api's
                </button>
            </nav>
        </div>
        <!-- Tab Content -->
        <div>
            <!-- Kein Tab ausgewählt -->
            <div x-show="activeTab === 'none'" x-cloak class="space-y-10" x-collapse.duration.400ms>
                <div class="">
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 hidden">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23.625 23.625" fill="currentColor" aria-hidden="true">
                                    <path d="M11.812 0C5.289 0 0 5.289 0 11.812s5.289 11.813 11.812 11.813 11.813-5.29 11.813-11.813S18.335 0 11.812 0zm2.459 18.307c-.608.24-1.092.422-1.455.548a3.838 3.838 0 0 1-1.262.189c-.736 0-1.309-.18-1.717-.539s-.611-.814-.611-1.367c0-.215.015-.435.045-.659a8.23 8.23 0 0 1 .147-.759l.761-2.688c.067-.258.125-.503.171-.731.046-.23.068-.441.068-.633 0-.342-.071-.582-.212-.717-.143-.135-.412-.201-.813-.201-.196 0-.398.029-.605.09-.205.063-.383.12-.529.176l.201-.828c.498-.203.975-.377 1.43-.521a4.225 4.225 0 0 1 1.29-.218c.731 0 1.295.178 1.692.53.395.353.594.812.594 1.376 0 .117-.014.323-.041.617a4.129 4.129 0 0 1-.152.811l-.757 2.68a7.582 7.582 0 0 0-.167.736 3.892 3.892 0 0 0-.073.626c0 .356.079.599.239.728.158.129.435.194.827.194.185 0 .392-.033.626-.097.232-.064.4-.121.506-.17l-.203.827zm-.134-10.878a1.807 1.807 0 0 1-1.275.492c-.496 0-.924-.164-1.28-.492a1.57 1.57 0 0 1-.533-1.193c0-.465.18-.865.533-1.196a1.812 1.812 0 0 1 1.28-.497c.497 0 .923.165 1.275.497.353.331.53.731.53 1.196 0 .467-.177.865-.53 1.193z" data-original="#030104" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm">
                                    <strong class="text-lg">Achtung! Änderungen haben sofortige Auswirkungen auf den Shop!</strong> <br>
                                    Jede Änderung, die du hier vornimmst, wird umgehend in deinem Shop sichtbar und kann die Funktionsweise direkt beeinflussen. Bitte überlege sorgfältig, bevor du Anpassungen vornimmst. Wenn du dir nicht sicher bist, wie sich eine Änderung auswirkt oder welche Auswirkungen sie haben könnte, wende dich bitte an den Systemadministrator, um Missverständnisse oder unerwünschte Konsequenzen zu vermeiden. Eine gut durchdachte Entscheidung ist hier entscheidend.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Abschnitt: Buchungen -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-700">Buchungsverwaltung</h3>
                            <p class="text-sm text-gray-600 mt-2">
                                Passe die Einstellungen für Datumsauswahl und verfügbare Verkaufsflächen an, um eine optimale Auslastung sicherzustellen.
                            </p>
                            <a @click="activeTab = 'buchung'" class="text-blue-500 mt-3 inline-block font-medium cursor-pointer">Zu den Buchungseinstellungen →</a>
                        </div>
                        <!-- Abschnitt: Produkte -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-700">Produktmanagement</h3>
                            <p class="text-sm text-gray-600 mt-2">
                                Konfiguriere Produkte, verwalte Kategorien und Tags, und optimiere die Suchfunktionalität für eine bessere Nutzererfahrung.
                            </p>
                            <a @click="activeTab = 'produkte'" class="text-blue-500 mt-3 inline-block font-medium cursor-pointer">Produkte konfigurieren →</a>
                        </div>
                        <!-- Abschnitt: Verkaufsflächen -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-700">Verkaufsflächen</h3>
                            <p class="text-sm text-gray-600 mt-2">
                                Verwalte gesperrte Regale und prüfe, welche Verkaufsflächen veröffentlicht sind.
                            </p>
                            <a @click="activeTab = 'verkaufsflaeche'" class="text-blue-500 mt-3 inline-block font-medium cursor-pointer">Verkaufsflächen verwalten →</a>
                        </div>
                        <!-- Abschnitt: E-Mails -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-700">E-Mail-Einstellungen</h3>
                            <p class="text-sm text-gray-600 mt-2">
                                Konfiguriere die Haupt-E-Mail-Adresse für Systemnachrichten und lege fest, wann und wie E-Mails gesendet werden sollen.
                            </p>
                            <a  @click="activeTab = 'mails'" class="text-blue-500 mt-3 inline-block font-medium cursor-pointer">E-Mail-Konfiguration →</a>
                        </div>
                        <!-- Abschnitt: API -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-700">API-Integrationen</h3>
                            <p class="text-sm text-gray-600 mt-2">
                                Verwalte API-Schlüssel und integriere Drittanbieter-Dienste, um die Funktionalität deiner Plattform zu erweitern.
                            </p>
                            <a @click="activeTab = 'api'" class="text-blue-500 mt-3 inline-block font-medium cursor-pointer">API-Einstellungen →</a>
                        </div>
                    </div>
                    <div class="mt-6">
                        <p class="text-sm text-gray-500 text-center">
                            Stelle sicher, dass alle Einstellungen korrekt vorgenommen werden, um ein reibungsloses Nutzererlebnis zu garantieren. Bei Fragen oder Problemen kannst du den Support kontaktieren.
                        </p>
                    </div>
                </div>
            </div>
            <!-- Buchung Tab -->
            <div x-show="activeTab === 'basis'" x-cloak class="space-y-10" x-collapse.duration.400ms>
                @livewire('admin.config.basic-settings')
            </div>
            <!-- Buchung Tab -->
            <div x-show="activeTab === 'buchung'" x-cloak class="space-y-10" x-collapse.duration.400ms>
                    <h2 class="text-2xl font-semibold">Buchung</h2>
                                <!-- Neue Settings für Buchungsoptimierung -->
                        <x-settings-collapse>
                            <x-slot name="trigger">
                                Buchungsoptimierung
                            </x-slot>
                            <x-slot name="content">
                                <!-- Kalender- und Regalauswahl Optimierung -->
                                <div>
                                    <div class="mb-4 p-4 bg-blue-100 text-blue-800 rounded-lg border border-blue-300"> <p class="text-sm"> <strong>Hinweis:</strong> Die folgenden Optimierungsoptionen helfen dabei, eine wirtschaftlich effiziente Nutzung der verfügbaren Ressourcen sicherzustellen: </p> <ul class="list-disc pl-6 mt-2 text-sm"> <li> <strong>Kalenderauswahl optimieren:</strong> Diese Funktion stellt sicher, dass Kunden verfügbare Zeiträume in einer Weise auswählen, die eine höhere Auslastung gewährleistet. Zeiträume werden so präsentiert, dass Lücken in der Vermietung minimiert werden. <br> Beispiel: Anstatt beliebige Zeiträume zu buchen, wird der Kunde angeleitet, aufeinanderfolgende oder lückenlose Buchungen vorzunehmen. Dies sorgt dafür, dass keine Leertage entstehen und die verfügbaren Kapazitäten optimal genutzt werden können. <br> <span class="text-green-600">Vorteil für Sie:</span> Sie erhalten mehr Auswahlmöglichkeiten, da die Option automatisch verfügbare, zusammenhängende Zeiträume priorisiert. </li> <li> <strong>Regalauswahl optimieren:</strong> Diese Funktion fokussiert sich auf die möglichst effiziente Belegung der Regale im Shop. Kunden werden dazu angeleitet, Regale in einer Weise zu wählen, die eine maximale Auslastung und eine wirtschaftlich sinnvolle Platzierung ermöglicht. <br> Beispiel: Wenn mehrere Regale verfügbar sind, wird das System Sie automatisch zu den Regalen mit der besten Belegung oder Platzierungsstrategie führen, um ungenutzte Lücken in der Regalaufteilung zu vermeiden. <br> <span class="text-green-600">Vorteil für Sie:</span> Durch diese Auswahl profitieren Sie von einer strategisch optimierten Platzierung Ihrer Produkte, was Ihre Verkaufschancen erhöhen kann. </li> </ul> <p class="mt-4 text-sm"> <strong>Warum sind diese Optimierungen wichtig?</strong> Diese Funktionen sorgen dafür, dass alle verfügbaren Kapazitäten des Shops effizient genutzt werden. Eine höhere Auslastung bedeutet mehr Möglichkeiten für alle Kunden, ihre Produkte erfolgreich anzubieten, während gleichzeitig die Mietpreise stabil bleiben können. Zudem helfen diese Optionen dabei, Ihre Buchung schnell und unkompliziert abzuwickeln, indem sie Ihnen die wirtschaftlich sinnvollsten Alternativen anzeigen. </p> <p class="text-red-600 mt-2"><strong>Hinweis:</strong> Diese Funktionen befinden sich derzeit in der Entwicklung und werden kontinuierlich verbessert.</p> </div>
                                    <form wire:submit.prevent="saveBookingOptimization">
                                        <div class="mb-4">
                                            <label class="inline-flex items-center mb-5 cursor-pointer">
                                                    <input wire:model="optimizeCalendar" name="terms" type="checkbox" class="sr-only peer" >
                                                    <div class="relative w-9 h-5  min-w-9 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300  rounded-full peer  peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all  peer-checked:bg-blue-600"></div>
                                                    <span class="ms-3 text-sm font-medium text-gray-900 ">Kalenderauswahl optimieren</span>
                                                </label>
                                        </div>
                                        <div class="mb-4">
                                            <label class="inline-flex items-center mb-5 cursor-pointer">
                                                    <input wire:model="optimizeShelfSelection" name="terms" type="checkbox" class="sr-only peer" >
                                                    <div class="relative w-9 h-5  min-w-9 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300  rounded-full peer  peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all  peer-checked:bg-blue-600"></div>
                                                    <span class="ms-3 text-sm font-medium text-gray-900 ">Regalauswahl optimieren</span>
                                                </label>
                                        </div>
                                        <button 
                                            type="submit" 
                                            class="mt-4 bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600"
                                        >
                                            Speichern
                                        </button>
                                    </form>
                                </div>
                            </x-slot>
                        </x-settings-collapse>
                        <x-settings-collapse>
                            <x-slot name="trigger">
                                Perioden und Preise
                            </x-slot>
                            <x-slot name="content">
                                <!-- Perioden und Preisbeschreibung -->
                                <div>
                                    <!-- Perioden anzeigen -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                        @foreach($periods as $period)
                                            @php
                                                $periodData = json_decode($period->value, true);
                                            @endphp
                                            <div x-data="{ openDropdown: false }" class="relative bg-white border rounded-lg p-4 shadow-lg">
                                                <div class="absolute top-0 right-0 mt-2 mr-2">
                                                    <!-- Status Anzeige -->
                                                        <span class="w-2.5 h-2.5 rounded-full" :class="{'bg-green-500': {{ $periodData['is_active'] }} == 1, 'bg-yellow-500': {{ $periodData['is_active'] }} == 0}"></span>
                                                    <button @click="openDropdown = ! openDropdown" class="text-gray-500">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                                            <path fill-rule="evenodd" d="M5.293 6.707a1 1 0 011.414 0L10 9.586l3.293-2.879a1 1 0 111.414 1.414l-4 3.5a1 1 0 01-1.414 0l-4-3.5a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                    <!-- Dropdown Menu -->
                                                    <div @click.away="openDropdown = false" x-show="openDropdown" x-transition class="absolute right-0 z-30 mt-2 w-32 bg-white border rounded-lg shadow-lg">
                                                        <button wire:click="editPeriod({{ $period->id }})" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">bearbeiten</button>
                                                        <button 
                                                            wire:click="toggleActivation({{ $period->id }})" 
                                                            class="w-full text-left px-4 py-2 text-sm mt-2"
                                                            :class="{
                                                                'text-green-500 hover:bg-green-100': !{{$periodData['is_active']}},
                                                                'text-gray-500 hover:bg-yellow-100': {{$periodData['is_active']}}
                                                            }"
                                                        >
                                                            {{ $periodData['is_active'] ? 'Deaktivieren' : 'Aktivieren' }}
                                                        </button>
                                                        <div x-data="{ openModal: false, periodToDelete: null }" class="">
                                                            <!-- Button für Löschen -->
                                                            <button @click="openModal = true; periodToDelete = {{ $period->id }}" class="w-full text-left px-4 py-2 text-sm mt-2 hover:text-white text-red-500 hover:bg-red-500">
                                                                Löschen
                                                            </button>
                                                            <!-- Modal für Bestätigung -->
                                                            <div x-show="openModal" @keydown.escape.window="openModal = false" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-800 bg-opacity-50">
                                                                <div @click.away="openModal = false" class="bg-white p-6 rounded-lg shadow-lg w-96">
                                                                    <h2 class="text-lg font-semibold">Bist du sicher?</h2>
                                                                    <p class="mt-2 text-sm text-gray-700">
                                                                        Möchtest du diese Periode wirklich löschen?<br> Dies kann nicht rückgängig gemacht werden.
                                                                    </p>
                                                                    <div class="mt-4 flex justify-between">
                                                                        <!-- Bestätigungsbutton, ruft die Livewire-Methode auf -->
                                                                        <button wire:click="deletePeriod({{ $period->id }})" @click="openModal = false"  class="px-4 py-2 bg-red-500 text-white rounded-lg">
                                                                            Ja, Löschen
                                                                        </button>
                                                                        <!-- Abbrechen Button -->
                                                                        <button @click="openModal = false" class="px-4 py-2 bg-gray-300 text-black rounded-lg mr-2">
                                                                            Abbrechen
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>                                <!-- Aktivieren/Deaktivieren Button -->
                                                    </div>
                                                </div>
                                                <p class="text-sm text-gray-500">Dauer: {{ $periodData['duration']  ?? 'Nicht definiert' }} Tage</p>
                                                <p class="text-sm">Preis: {{ number_format($periodData['price'], 2) ?? 'Nicht definiert'  }} €</p>
                                                <p class="text-sm mt-2">{{ $periodData['description']  ?? 'Nicht definiert' }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div x-data="{ openForm: false, periodisEditing: @entangle('periodisEditing') }" >
                                                    <!-- Button zum Öffnen des Formulars -->
                                                    <button 
                                                        @click="openForm = !openForm" 
                                                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm my-4"
                                                    >
                                                        Neue Periode
                                                    </button>
                                                    <!-- Dropdown für das Formular -->
                                                    <div x-show="openForm || periodisEditing" @keydown.escape.window="openForm = false" x-cloak  x-collapse.duration.400ms class="mt-4 w-full">
                                                        <div class="bg-white p-6 rounded-lg shadow-lg border">
                                                            <form wire:submit.prevent="saveOrUpdatePeriod">
                                                                <h2 class="text-xl font-semibold mb-4">
                                                                    {{ $periodisEditing ? 'Periode bearbeiten' : 'Neue Periode hinzufügen' }}
                                                                </h2>
                                                                <div class="space-y-4 sm:space-y-0 sm:grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                                                    <!-- Dauer und Preis nebeneinander bei größeren Bildschirmen -->
                                                                    <div class="mb-4">
                                                                        <label for="newPeriodDuration" class="block text-sm font-medium">Dauer (in Tagen)</label>
                                                                        <input 
                                                                            type="number" 
                                                                            id="newPeriodDuration" 
                                                                            wire:model="newPeriodDuration" 
                                                                            class="rounded px-4 py-2 w-full text-sm"
                                                                            placeholder="Dauer des Zeitraums in Tagen"
                                                                            required
                                                                        >
                                                                    </div>
                                                                    <div class="mb-4">
                                                                        <label for="newPeriodPrice" class="block text-sm font-medium">Preis</label>
                                                                        <input 
                                                                            type="number" 
                                                                            id="newPeriodPrice" 
                                                                            wire:model="newPeriodPrice" 
                                                                            class="rounded px-4 py-2 w-full text-sm"
                                                                            placeholder="Preis für den Zeitraum"
                                                                            required
                                                                        >
                                                                    </div>
                                                                    <!-- Beschreibung als volle Breite -->
                                                                    <div class="mb-4 sm:col-span-2">
                                                                        <label for="newPeriodDescription" class="block text-sm font-medium">Beschreibung</label>
                                                                        <textarea 
                                                                            id="newPeriodDescription" 
                                                                            wire:model="newPeriodDescription" 
                                                                            class="rounded px-4 py-2 w-full text-sm"
                                                                            placeholder="Beschreibung des Zeitraums"
                                                                            required
                                                                        ></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="flex justify-end gap-4 mt-4">
                                                                    <button 
                                                                        type="submit" 
                                                                        class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600 text-sm"
                                                                    >
                                                                        {{ $periodisEditing ? 'Periode bearbeiten' : 'Speichern' }}
                                                                    </button>
                                                                    <!-- Abbrechen Button -->
                                                                    <button 
                                                                        type="button" 
                                                                        @click="openForm = false; $wire.periodReset()" 
                                                                        class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 text-sm"
                                                                    >
                                                                        Abbrechen
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                </div>
                            </x-slot>
                        </x-settings-collapse>
                        <x-settings-collapse>
                            <x-slot name="trigger">
                                Feiertage
                            </x-slot>
                            <x-slot name="content">
                                <!-- Feiertage -->
                                <div>
                                    <table class="bg-white table-auto w-full mb-6">
                                        <thead>
                                            <tr class="">
                                                <th class="px-4 py-2 text-left">Datum</th>
                                                <th class="px-4 py-2 text-left">Name</th>
                                                <th class="px-4 py-2">Aktionen</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($holidays as $holiday)
                                                <tr>
                                                    <td class="border px-4 py-2">{{ $holiday->value }}</td>
                                                    <td class="border px-4 py-2">{{ $holiday->key }}</td>
                                                    <td class="border px-4 py-2 text-center">
                                                        <button wire:click="deleteHoliday({{ $holiday->id }})" class="text-red-500 hover:text-red-700">Löschen</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <form wire:submit.prevent="addHoliday" class="flex items-center gap-4">
                                        <input type="date" wire:model="newHolidayDate" required class="border rounded px-4 py-2">
                                        <input type="text" wire:model="newHolidayName" placeholder="Name des Feiertags" required class="border rounded px-4 py-2 flex-1">
                                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Hinzufügen</button>
                                    </form>
                                </div>
                            </x-slot>
                        </x-settings-collapse>
                        <x-settings-collapse>
                            <x-slot name="trigger">
                                 Gesperrte Wochentage
                            </x-slot>
                            <x-slot name="content">
                                <!-- Gesperrte Wochentage -->
                                <div>
                                    <form wire:submit.prevent="saveBlockedDays">
                                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-4">
                                            @foreach (['0' => 'Sonntag', '1' => 'Montag', '2' => 'Dienstag', '3' => 'Mittwoch', '4' => 'Donnerstag', '5' => 'Freitag', '6' => 'Samstag'] as $key => $day)
                                                <label class="inline-flex items-center mb-5 cursor-pointer">
                                                    <input wire:model="selectedBlockedDays" name="terms" type="checkbox" value="{{ $key }}" class="sr-only peer" {{ $blockedDays->contains('value', $key) ? 'checked' : '' }}>
                                                    <div class="relative w-9 h-5  min-w-9 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300  rounded-full peer  peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all  peer-checked:bg-blue-600"></div>
                                                    <span class="ms-3 text-sm font-medium text-gray-900 ">{{ $day }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <button 
                                            type="submit" 
                                            class="mt-4 bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600"
                                        >
                                            Speichern
                                        </button>
                                    </form>
                                </div>
                            </x-slot>
                        </x-settings-collapse>
                </div>
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <!-- Produkte Tab -->
                <div x-show="activeTab === 'produkte'" x-cloak class="space-y-10" x-collapse.duration.400ms>
                    <h2 class="text-2xl font-semibold">Produkte</h2>
                    <x-settings-collapse>
                        <x-slot name="trigger">
                             Kategorien
                        </x-slot>
                        <x-slot name="content">
                                <ul>
                                    @foreach ($categoriesData as $parent)
                                        <li class="mb-4">
                                            <!-- Formular für Eltern-Kategorie -->
                                            <form method="POST" wire:submit.prevent="saveCategory({{ $parent['id'] }})" class="flex items-center gap-4">
                                                @csrf
                                                <input 
                                                    type="text" 
                                                    name="name" 
                                                    value="{{ $parent['name'] }}" 
                                                    placeholder="Name" 
                                                    class="border rounded px-2 py-1"
                                                >
                                                <input 
                                                    type="text" 
                                                    name="slug" 
                                                    value="{{ $parent['slug'] }}" 
                                                    placeholder="Slug" 
                                                    class="border rounded px-2 py-1"
                                                >
                                                <button 
                                                    type="submit" 
                                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                                >
                                                    Speichern
                                                </button>
                                                <button 
                                                    type="button" 
                                                    wire:click="deleteCategory({{ $parent['id'] }})" 
                                                    class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                                                >
                                                    Löschen
                                                </button>
                                            </form>
                                            <!-- Kind-Kategorien -->
                                            @if (!empty($parent['children']))
                                                <ul class="pl-8 mt-2">
                                                    @foreach ($parent['children'] as $child)
                                                        <li class="mb-4">
                                                            <!-- Formular für Kind-Kategorie -->
                                                            <form method="POST" wire:submit.prevent="saveCategory({{ $child['id'] }})" class="flex items-center gap-4">
                                                                @csrf
                                                                <input 
                                                                    type="text" 
                                                                    name="name" 
                                                                    value="{{ $child['name'] }}" 
                                                                    placeholder="Name" 
                                                                    class="border rounded px-2 py-1"
                                                                >
                                                                <input 
                                                                    type="text" 
                                                                    name="slug" 
                                                                    value="{{ $child['slug'] }}" 
                                                                    placeholder="Slug" 
                                                                    class="border rounded px-2 py-1"
                                                                >
                                                                <button 
                                                                    type="submit" 
                                                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                                                >
                                                                    Speichern
                                                                </button>
                                                                <button 
                                                                    type="button" 
                                                                    wire:click="deleteCategory({{ $child['id'] }})" 
                                                                    class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                                                                >
                                                                    Löschen
                                                                </button>
                                                            </form>
                                                            <!-- Enkel-Kategorien -->
                                                            @if (!empty($child['children']))
                                                                <ul class="pl-8 mt-2">
                                                                    @foreach ($child['children'] as $grandchild)
                                                                        <li class="mb-4">
                                                                            <!-- Formular für Enkel-Kategorie -->
                                                                            <form method="POST" wire:submit.prevent="saveCategory({{ $grandchild['id'] }})" class="flex items-center gap-4">
                                                                                @csrf
                                                                                <input 
                                                                                    type="text" 
                                                                                    name="name" 
                                                                                    value="{{ $grandchild['name'] }}" 
                                                                                    placeholder="Name" 
                                                                                    class="border rounded px-2 py-1"
                                                                                >
                                                                                <input 
                                                                                    type="text" 
                                                                                    name="slug" 
                                                                                    value="{{ $grandchild['slug'] }}" 
                                                                                    placeholder="Slug" 
                                                                                    class="border rounded px-2 py-1"
                                                                                >
                                                                                <button 
                                                                                    type="submit" 
                                                                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                                                                >
                                                                                    Speichern
                                                                                </button>
                                                                                <button 
                                                                                    type="button" 
                                                                                    wire:click="deleteCategory({{ $grandchild['id'] }})" 
                                                                                    class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                                                                                >
                                                                                    Löschen
                                                                                </button>
                                                                            </form>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                <!-- Neue Kategorie hinzufügen -->
                                <div class="">
                                    <h2 class="text-xl font-bold mb-4">Neue Kategorie hinzufügen</h2>
                                    <form wire:submit.prevent="addCategory" class="flex items-center gap-4">
                                        <input 
                                            type="text" 
                                            wire:model="newCategoryName" 
                                            class="border rounded px-2 py-1 flex-1" 
                                            placeholder="Name der Kategorie"
                                        >
                                        <input 
                                            type="text" 
                                            wire:model="newCategorySlug" 
                                            class="border rounded px-2 py-1 flex-1" 
                                            placeholder="Slug der Kategorie"
                                        >
                                        <select 
                                            wire:model="newCategoryParentId" 
                                            class="border rounded px-2 py-1"
                                        >
                                            <option value="">Keine übergeordnete Kategorie</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <button 
                                            type="submit" 
                                            class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                                        >
                                            Hinzufügen
                                        </button>
                                    </form>
                                </div>
                        </x-slot>
                    </x-settings-collapse>
                    <x-settings-collapse>
                        <x-slot name="trigger">
                             Schlagwörter
                        </x-slot>
                        <x-slot name="content">
                            <!-- Tags Accordion -->
                                    <ul>
                                        @foreach ($tags as $tag)
                                            <li class="mb-2 flex items-center justify-between">
                                                <span>{{ $tag->name }}</span>
                                                <button wire:click="deleteTag({{ $tag->id }})" class="text-red-500 hover:text-red-700">Löschen</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <form wire:submit.prevent="addTag" class="flex items-center gap-4 mt-4">
                                        <input 
                                            type="text" 
                                            wire:model="newTagName" 
                                            placeholder="Neues Schlagwörter" 
                                            class="border rounded px-4 py-2 flex-1"
                                        >
                                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                            Hinzufügen
                                        </button>
                                    </form>
                        </x-slot>
                    </x-settings-collapse>
                    <x-settings-collapse>
                        <x-slot name="trigger">
                        Mindestpreis & Höchstpreis
                        </x-slot>
                        <x-slot name="content">
                              <!-- Preisspanne Collapse -->
                            <form wire:submit.prevent="updatePriceRange" class="flex items-center gap-4">
                                <div class="flex-1">
                                    <label for="minPrice" class="block text-sm font-medium text-gray-700">Mindestpreis</label>
                                    <input 
                                        type="number" 
                                        wire:model="minPrice" 
                                        id="minPrice" 
                                        class="border rounded px-2 py-1 w-full"
                                        placeholder="Min"
                                    >
                                </div>
                                <div class="flex-1">
                                    <label for="maxPrice" class="block text-sm font-medium text-gray-700">Höchstpreis</label>
                                    <input 
                                        type="number" 
                                        wire:model="maxPrice" 
                                        id="maxPrice" 
                                        class="border rounded px-2 py-1 w-full"
                                        placeholder="Max"
                                    >
                                </div>
                                <button 
                                    type="submit" 
                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                >
                                    Speichern
                                </button>
                            </form>
                        </x-slot>
                    </x-settings-collapse>
                    <x-settings-collapse>
                        <x-slot name="trigger">
                            Provision ändern
                        </x-slot>
                        <x-slot name="content">
                        <form wire:submit.prevent="saveOrUpdateProvision" class="flex items-center gap-4">
                            <div class="flex-1">
                                <label for="provision" class="block text-sm font-medium text-gray-700">Provision (%)</label>
                                <input 
                                    type="number" 
                                    wire:model="provision" 
                                    id="provision" 
                                    class="border rounded px-2 py-1 w-full"
                                    placeholder="Provision"
                                    step="0.01" 
                                    min="0" 
                                    max="100"
                                >
                            </div>
                            <button 
                                type="submit" 
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                            >
                                Speichern
                            </button>
                        </form>
                        </x-slot>
                    </x-settings-collapse>
                </div>
                <!-- Verkaufsfläche Tab -->
                <!-- Verkaufsfläche Tab -->
                <!-- Verkaufsfläche Tab -->
                <!-- Verkaufsfläche Tab -->
                <!-- Verkaufsfläche Tab -->
                <!-- Verkaufsfläche Tab -->
                <!-- Verkaufsfläche Tab -->
                <!-- Verkaufsfläche Tab -->
                <!-- Verkaufsfläche Tab -->
                <!-- Verkaufsfläche Tab -->
                <div x-show="activeTab === 'verkaufsflaeche'" x-cloak class="space-y-10" x-collapse.duration.400ms>
                    <h2 class="text-2xl font-semibold">Verkaufsfläche</h2>
                    <x-settings-collapse>
                        <x-slot name="trigger">
                            Gesperrte Regale
                        </x-slot>
                        <x-slot name="content">
                            <!-- Liste der gesperrten Tage -->
                            <h3 class="text-lg font-semibold mb-4">Liste der gesperrten Tage</h3>
                            <ul class="mb-6 space-y-2">
                            @forelse($AdminblockedShelves as $blockedShelf)
                                <li class="flex justify-between items-center p-2 bg-white border border-gray-300 shadow rounded">
                                    <div>
                                        <p class="text-sm font-medium">Standort: {{ $blockedShelf->retailSpace->location->name }}</p>
                                        <p class="text-sm text-gray-600">Regal-ID: <strong>{{ $blockedShelf->shelve->floor_number }}</strong></p>
                                        <p class="text-sm text-gray-600">Von: {{ $blockedShelf->start_date }} - Bis: {{ $blockedShelf->end_date }}</p>
                                    </div>
                                    <!-- Alpine.js Dropdown Menü -->
                                    <div x-data="{ isDropdownOpen: false }" class="relative">
                                        <!-- Button um das Dropdown zu öffnen -->
                                        <button @click="isDropdownOpen = !isDropdownOpen" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        <svg class="w-6 h-10 text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-width="3" d="M12 6h.01M12 12h.01M12 18h.01"/>
                                        </svg>
                                        </button>
                                        <!-- Dropdown Menu -->
                                        <div 
                                            x-show="isDropdownOpen" 
                                            @click.away="isDropdownOpen = false" 
                                            class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg"
                                        >
                                            <!-- Löschen Button im Dropdown -->
                                            <button 
                                                wire:click="deleteBlockedShelf({{ $blockedShelf->id }})" 
                                                class="w-full text-red-600 hover:text-red-800 text-sm font-medium p-2 text-left"
                                            >
                                                Sperrung aufheben
                                            </button>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <p class="text-sm text-gray-500">Keine gesperrten Regale gefunden.</p>
                            @endforelse
                            </ul>
                            <div x-data="{ formOpen: false }">
                                <!-- Button zum Öffnen des Formulars -->
                                <button 
                                    @click="formOpen = !formOpen" 
                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-4"
                                >
                                    Neuen gesperrten Tag hinzufügen
                                </button>
                                <!-- Dropdown Formular -->
                                <div x-show="formOpen" @click.away="formOpen = false" class="bg-white shadow-md rounded-lg p-6 w-full">
                                    <form wire:submit.prevent="addBlockedDay" class="space-y-4">
                                        <!-- Standort auswählen -->
                                            <div>
                                                <label for="newBlockedDayLocation" class="block text-sm font-medium text-gray-700">Standort</label>
                                                <select 
                                                    wire:model.live="newBlockedDayLocation" 
                                                    id="newBlockedDayLocation" 
                                                    class="border rounded px-2 py-1 w-full"
                                                >
                                                    <option value="">Bitte auswählen</option>
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('newBlockedDayLocation') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                            </div>
                                            <!-- Verkaufsfläche auswählen -->
                                            <div>
                                                <label for="newBlockedDayRetailSpaceId" class="block text-sm font-medium text-gray-700">Verkaufsfläche</label>
                                                <select 
                                                    wire:model.live="newBlockedDayRetailSpaceId" 
                                                    id="newBlockedDayRetailSpaceId" 
                                                    class="border rounded px-2 py-1 w-full"
                                                >
                                                    <option value="">Bitte auswählen</option>
                                                    @foreach($retailSpaces as $retailSpace)
                                                        <option value="{{ $retailSpace->id }}">{{ $retailSpace->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('newBlockedDayRetailSpaceId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                            </div>
                                            <!-- Regal-ID auswählen -->
                                            <div>
                                                <label for="newBlockedDayShelfId" class="block text-sm font-medium text-gray-700">Regal-ID</label>
                                                <select 
                                                    wire:model.live="newBlockedDayShelfId" 
                                                    id="newBlockedDayShelfId" 
                                                    class="border rounded px-2 py-1 w-full"
                                                >
                                                    <option value="">Bitte Regal auswählen</option>
                                                    @foreach($shelves as $shelf)
                                                        <option value="{{ $shelf->id }}">{{ $shelf->floor_number }}</option>
                                                    @endforeach
                                                </select>
                                                @error('newBlockedDayShelfId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                            </div>
                                        <!-- Zeitraum auswählen -->
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label for="newBlockedDayStartDate" class="block text-sm font-medium text-gray-700">Von</label>
                                                <input 
                                                    type="date" 
                                                    wire:model="newBlockedDayStartDate" 
                                                    id="newBlockedDayStartDate" 
                                                    class="border rounded px-2 py-1 w-full"
                                                >
                                                @error('newBlockedDayStartDate') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label for="newBlockedDayEndDate" class="block text-sm font-medium text-gray-700">Bis</label>
                                                <input 
                                                    type="date" 
                                                    wire:model="newBlockedDayEndDate" 
                                                    id="newBlockedDayEndDate" 
                                                    class="border rounded px-2 py-1 w-full"
                                                >
                                                @error('newBlockedDayEndDate') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                        <!-- Speichern Button -->
                                        <button 
                                            type="submit" 
                                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                                        >
                                            Speichern
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </x-slot>
                    </x-settings-collapse>
                </div>
                <!-- Mails Tab -->
                <div x-show="activeTab === 'mails'" x-cloak class="space-y-10" x-collapse.duration.400ms>
                <h2 class="text-2xl font-semibold">Mails</h2>
                    <!-- Admin E-Mail Adresse -->
                    <x-settings-collapse>
                        <x-slot name="trigger">
                            Admin E-Mail Adresse
                        </x-slot>
                        <x-slot name="content">
                            <div class="bg-blue-100 text-blue-700 p-4 rounded-md border border-blue-200 mb-4">
                                <strong>Hinweis:</strong> Hier kannst du die E-Mail-Adresse des Administrators angeben. Diese Adresse wird für Benachrichtigungen und systemweite E-Mails verwendet.
                            </div>
                            <div class="mb-4">
                                <label for="admin_email" class="block text-sm font-medium text-gray-700">Admin E-Mail Adresse</label>
                                <input 
                                    type="email" 
                                    id="admin_email" 
                                    wire:model="adminEmail" 
                                    class="mt-1 p-2 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                    required
                                >
                                @error('adminEmail')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button 
                                wire:click="saveAdminEmail" 
                                class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600"
                            >
                                E-Mail Adresse speichern
                            </button>
                        </x-slot>
                    </x-settings-collapse>
                    <!-- Automatische Admin Mails -->
                    <x-settings-collapse>
                        <x-slot name="trigger">
                            Automatische Admin E-Mails
                        </x-slot>
                        <x-slot name="content">
                            <div class="bg-blue-100 text-blue-700 p-4 rounded-md border border-blue-200 mb-4">
                                <strong>Hinweis:</strong> Wähle aus, welche automatischen Benachrichtigungen an den Admin gesendet werden sollen.
                            </div>
                            <!-- Checkboxen für Admin Mails -->
                            <div class="space-y-3">
                                @foreach ($adminEmailNotifications as $key => $value)
                                    <div class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            id="admin_mail_{{ $key }}" 
                                            wire:model="adminEmailNotifications.{{ $key }}" 
                                            class="mr-2"
                                        >
                                        <label for="admin_mail_{{ $key }}" class="text-sm font-medium text-gray-700">
                                            {{ __("mails.admin.$key") }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <!-- Speichern Button -->
                            <button 
                                wire:click="saveAdminMailSettings" 
                                class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 mt-4"
                            >
                                Änderungen speichern
                            </button>
                        </x-slot>
                    </x-settings-collapse>
                    <!-- Benutzer Mails -->
                    <x-settings-collapse>
                        <x-slot name="trigger">
                            Benutzer E-Mails
                        </x-slot>
                        <x-slot name="content">
                            <div class="bg-blue-100 text-blue-700 p-4 rounded-md border border-blue-200 mb-4">
                                <strong>Hinweis:</strong> Wähle aus, welche automatischen E-Mails an Benutzer gesendet werden sollen.
                            </div>
                            <!-- Checkboxen für Benutzer Mails -->
                            <div class="space-y-3">
                                @foreach ($userEmailNotifications as $key => $value)
                                    <div class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            id="user_mail_{{ $key }}" 
                                            wire:model="userEmailNotifications.{{ $key }}" 
                                            class="mr-2"
                                        >
                                        <label for="user_mail_{{ $key }}" class="text-sm font-medium text-gray-700">
                                            {{ __("mails.user.$key") }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <!-- Speichern Button -->
                            <button 
                                wire:click="saveUserMailSettings" 
                                class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 mt-4"
                            >
                                Änderungen speichern
                            </button>
                        </x-slot>
                    </x-settings-collapse>
                </div>
                <!-- Api Tab -->
                <div x-show="activeTab === 'api'" x-cloak class="space-y-10" x-collapse.duration.400ms>
                    <h2 class="text-2xl font-semibold">API Einstellungen</h2>
                    <!-- Kassen-API -->
                    <x-settings-collapse>
                        <x-slot name="trigger">
                            Kassen-API
                        </x-slot>
                        <x-slot name="content">
                            <div class="bg-blue-100 text-blue-700 p-4 rounded-md border border-blue-200 mb-4">
                                <strong>Hinweis:</strong> Hier kannst du die API-Verbindung zur Kasse eintragen. Diese API ermöglicht die Kommunikation mit deinem Kassensystem.
                            </div>
                            <!-- API URL Eingabe für Kassen-API -->
                            <div class="mb-4">
                                <label for="cash_register_api_url" class="block text-sm font-medium text-gray-700">Kassen-API URL</label>
                                <input 
                                    type="url" 
                                    id="cash_register_api_url" 
                                    wire:model="apiSettings.cash_register_api_url" 
                                    class="mt-1 p-2 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                    required
                                >
                                @error('apiSettings.cash_register_api_url')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <!-- API Key Eingabe für Kassen-API -->
                            <div class="mb-4">
                                <label for="cash_register_api_key" class="block text-sm font-medium text-gray-700">Kassen-API Schlüssel</label>
                                <input 
                                    type="text" 
                                    id="cash_register_api_key" 
                                    wire:model="apiSettings.cash_register_api_key" 
                                    class="mt-1 p-2 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                    required
                                >
                                @error('apiSettings.cash_register_api_key')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <!-- Speichern Button -->
                            <button 
                                wire:click="saveApiSettings" 
                                class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600"
                            >
                                speichern
                            </button>
                        </x-slot>
                    </x-settings-collapse>
                    <!-- PayPal Buchungs-API -->
                    <x-settings-collapse>
                        <x-slot name="trigger">
                            PayPal Buchungs-API
                        </x-slot>
                        <x-slot name="content">
                            <div class="bg-blue-100 text-blue-700 p-4 rounded-md border border-blue-200 mb-4">
                                <strong>Hinweis:</strong> Gib hier die API-Zugangsdaten für die PayPal-Buchungs-API ein. Diese API wird verwendet, um Zahlungen und Buchungen über PayPal zu verwalten.
                            </div>
                            <!-- API Key Eingabe für PayPal -->
                            <div class="mb-4">
                                <label for="paypal_api_client_id" class="block text-sm font-medium text-gray-700">API Client Id</label>
                                <input 
                                    type="text" 
                                    id="paypal_api_client_id" 
                                    wire:model="apiSettings.paypal_api_client_id" 
                                    class="mt-1 p-2 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                    required
                                >
                                @error('apiSettings.paypal_api_client_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label for="paypal_api" class="block text-sm font-medium text-gray-700">API Schlüssel</label>
                                <input 
                                    type="text" 
                                    id="paypal_api" 
                                    wire:model="apiSettings.paypal_api" 
                                    class="mt-1 p-2 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                    required
                                >
                                @error('apiSettings.paypal_api')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <!-- Speichern Button -->
                            <button 
                                wire:click="saveApiSettings" 
                                class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600"
                            >
                                speichern
                            </button>
                        </x-slot>
                    </x-settings-collapse>
                    <!-- API-Keys Verwaltung -->
                    <x-settings-collapse>
                        <x-slot name="trigger">API-Schlüssel verwalten</x-slot>
                        <x-slot name="content">
                            <div class="mb-4">
                                <x-button 
                                    wire:click="generateApiKey" 
                                    class="bg-blue-500 text-white py-1 px-2 rounded hover:bg-blue-600">
                                    Neuen API-Schlüssel generieren
                                </x-button>
                            </div>
                            <ul>
                                @foreach ($apiKeys as $key => $value)
                                    <li class="flex items-center justify-between mb-2 bg-white  px-2 py-1 rounded">
                                        <span class="text-sm font-mono">{{ $value }}</span>
                                        <button 
                                            wire:click="deleteApiKey('{{ $key }}')" 
                                            class="text-red-500 hover:underline">
                                            Löschen
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </x-slot>
                    </x-settings-collapse>
                </div>
            </div>
        </div>
</div>

