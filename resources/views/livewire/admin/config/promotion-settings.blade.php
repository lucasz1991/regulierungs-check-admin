<section aria-labelledby="promotion-settings-title" class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-medium text-blue-600">Straßenpromotion</p>
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
            Die Promotion bleibt gesperrt, bis die Einlöseadresse gespeichert und der interne Audit-Schutz automatisch eingerichtet ist.
        </div>
    @endif

    <form wire:submit="save" class="space-y-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <label for="promotion-enabled" class="flex cursor-pointer items-start gap-3">
                <input
                    id="promotion-enabled"
                    type="checkbox"
                    wire:model="enabled"
                    class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                >
                <span>
                    <span class="block text-sm font-semibold text-gray-900">Promotion freigeben</span>
                    <span class="mt-1 block text-sm text-gray-600">Die Freigabe wird nur wirksam, wenn alle Sicherheitsangaben gültig gespeichert sind.</span>
                </span>
            </label>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div>
                <label for="promotion-redemption-url" class="block text-sm font-medium text-gray-700">Öffentliche Einlöseadresse</label>
                <input
                    id="promotion-redemption-url"
                    type="url"
                    wire:model="redemptionBaseUrl"
                    autocomplete="url"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    placeholder="https://www.example.de"
                >
                <p class="mt-1 text-xs text-gray-500">Basisadresse der Teilnehmer-App; ohne abschließenden Schrägstrich.</p>
                @error('redemptionBaseUrl') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="promotion-qr-ttl" class="block text-sm font-medium text-gray-700">QR-Gültigkeit in Minuten</label>
                <input
                    id="promotion-qr-ttl"
                    type="number"
                    min="5"
                    max="120"
                    step="1"
                    wire:model="qrTtlMinutes"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                >
                <p class="mt-1 text-xs text-gray-500">Zulässiger Bereich: 5 bis 120 Minuten.</p>
                @error('qrTtlMinutes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-lg border border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-gray-900">Interner Audit-Schutz</p>
                <p class="mt-1 text-sm text-gray-600">
                    @if ($auditKeyConfigured)
                        Sicher eingerichtet. Jede Gewinnaktion wird unmittelbar im selben Webaufruf protokolliert; der Schlüssel wird niemals angezeigt.
                    @else
                        Wird beim ersten Speichern automatisch sicher erzeugt.
                    @endif
                </p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold {{ $auditKeyConfigured ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                {{ $auditKeyConfigured ? 'Eingerichtet' : 'Noch nicht eingerichtet' }}
            </span>
        </div>

        <div class="flex justify-end">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save">Einstellungen speichern</span>
                <span wire:loading wire:target="save">Wird gespeichert…</span>
            </button>
        </div>
    </form>
</section>
