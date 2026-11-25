<div>
    <x-secondary-button wire:click="openModal">Kontakte importieren</x-secondary-button>
    <x-dialog-modal wire:model="showModal" maxWidth="4xl">
        <x-slot name="title">Kontakte importieren</x-slot>
        <x-slot name="content">
            <p class="mb-3 text-sm">CSV mit Kopfzeile oder gespeicherte Gelbe-Seiten-Ergebnisse als HTML oder TXT. Bis zu 10 MB und 2.000 Kontakte pro Datei. Neue Kontakte beginnen ohne Versandfreigabe.</p>
            <input type="file" aria-label="Kontaktdatei" accept=".csv,.html,.htm,.txt" wire:model="file">
            <x-input-error for="file" />
            @if($headers)
                <div class="my-3 grid gap-2 sm:grid-cols-2">
                    @foreach($headers as $i=>$header)
                        <label>{{ $header }}
                            <select class="block w-full rounded border-gray-300" wire:model.live="mapping.{{ $i }}">
                                <option value="">Überspringen</option>
                                @foreach(\App\Models\Contact::FIELDS as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
                <x-input-error for="mapping" />
            @endif
            @if($file)<x-secondary-button class="my-2" wire:click="analyzeFile" wire:loading.attr="disabled">Vorschau prüfen</x-secondary-button>@endif
            @if(collect($htmlRows)->contains(fn($r) => !empty($r['_detail']) && empty($r['email'])))
                <x-secondary-button wire:click="loadDetails" wire:loading.attr="disabled">Weitere Detailadressen ergänzen</x-secondary-button>
            @endif
            @if($rows)
                @php($invalid = collect($rows)->where('error','!=','')->count())
                @php($existing = collect($rows)->where('exists',true)->count())
                <p class="my-2">{{ count($rows)-$invalid-$existing }} neu · {{ $existing }} vorhanden · {{ $invalid }} übersprungen</p>
                <div class="max-h-64 overflow-auto"><table class="w-full text-left text-sm"><thead><tr><th>Zeile</th><th>Name</th><th>E-Mail</th><th>Ergebnis</th></tr></thead><tbody>
                    @foreach($rows as $row)<tr class="border-t"><td>{{ $row['number'] }}</td><td>{{ $row['fields']['name'] ?? '–' }}</td><td>{{ $row['fields']['email'] ?? '–' }}</td><td>{{ $row['error'] ?: ($row['exists'] ? 'Vorhanden' : 'Neu') }}</td></tr>@endforeach
                </tbody></table></div>
                <label class="mt-3 block"><input type="checkbox" wire:model="updateExisting"> Vorhandene Kontakte mit ausgefüllten Feldern aktualisieren</label>
            @endif
        </x-slot>
        <x-slot name="footer"><x-secondary-button wire:click="closeModal">Schließen</x-secondary-button>
            @if($rows)<x-button class="ml-2" wire:click="saveContacts" wire:loading.attr="disabled">Kontakte übernehmen</x-button>@endif
        </x-slot>
    </x-dialog-modal>
</div>
