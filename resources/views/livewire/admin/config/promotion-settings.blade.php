<section aria-labelledby="promotion-settings-title" class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-medium text-teal-700">Straßenpromotion</p>
            <h2 id="promotion-settings-title" class="mt-1 text-2xl font-semibold text-gray-900">Promotion-Glücksrad</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">
                Diese Einstellungen gelten gemeinsam für Mitarbeiter- und Teilnehmerbereich. Alles Notwendige wird direkt hier verwaltet.
            </p>
        </div>

        @if ($effectiveEnabled)
            <span class="inline-flex w-fit items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                Aktiv und freigegeben
            </span>
        @elseif ($isConfigured)
            <span class="inline-flex w-fit items-center rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800">
                Konfiguriert, aber deaktiviert
            </span>
        @else
            <span class="inline-flex w-fit items-center rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800">
                Freigabe gesperrt
            </span>
        @endif
    </div>

    @if (! $isConfigured)
        <div role="status" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Die Promotion bleibt gesperrt, bis die öffentliche Teilnehmer-Adresse gespeichert und die interne Sicherheitskonfiguration vollständig ist.
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Öffentliche Teilnehmer-Adresse</p>
                <p class="mt-2 break-all font-mono text-sm font-semibold text-gray-900">{{ $redemptionBaseUrl !== '' ? $redemptionBaseUrl : 'Noch nicht eingerichtet' }}</p>
                <p class="mt-2 text-sm text-gray-600">Freigabe: <strong>{{ $enabled ? 'aktiviert' : 'deaktiviert' }}</strong>. Änderungen werden im Standardmodal vorgenommen.</p>
            </div>
            <button type="button" wire:click="openSettingsModal" class="rounded-lg bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2">Einstellungen bearbeiten</button>
        </div>
    </div>

    <x-dialog-modal id="promotion-settings-modal" wire:model.live="showSettingsModal" :maxWidth="'2xl'">
        <x-slot name="title">Promotion-Einstellungen bearbeiten</x-slot>
        <x-slot name="content">
            <form id="promotion-settings-form" wire:submit="save" class="space-y-6 text-left" novalidate>
                @error('settings')
                    <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">
                        {{ $message }}
                    </div>
                @enderror
                @error('qrTtlMinutes')
                    <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800">
                        {{ $message }}
                    </div>
                @enderror

                <x-promotion.form-checkbox
                    id="promotion-enabled"
                    field="enabled"
                    label="Promotion freigeben"
                    hint="Die Freigabe wird nur wirksam, wenn die Teilnehmer-Adresse und eine öffentliche Kampagne vollständig eingerichtet sind."
                    :accent="true"
                    wire:model="enabled"
                />
                <x-promotion.form-input
                    id="promotion-redemption-url"
                    field="redemptionBaseUrl"
                    label="Öffentliche Teilnehmer-Adresse"
                    type="url"
                    hint="Basisadresse der Teilnehmer-App; der dauerhaft gedruckte Poster-QR verweist automatisch auf /gluecksrad."
                    :required="true"
                    wire:model="redemptionBaseUrl"
                    autocomplete="url"
                    inputmode="url"
                    placeholder="https://www.example.de"
                />
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" wire:click="closeSettingsModal" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Abbrechen</button>
            <button type="submit" form="promotion-settings-form" wire:loading.attr="disabled" wire:target="save" class="ml-3 rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="save">Einstellungen speichern</span><span wire:loading wire:target="save">Wird gespeichert…</span></button>
        </x-slot>
    </x-dialog-modal>
</section>
