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
                    <span class="mt-1 block text-sm text-gray-600">Die Freigabe wird nur wirksam, wenn die Teilnehmer-Adresse und eine öffentliche Kampagne vollständig eingerichtet sind.</span>
                </span>
            </label>
        </div>

        <div>
            <div>
                <label for="promotion-redemption-url" class="block text-sm font-medium text-gray-700">Öffentliche Teilnehmer-Adresse</label>
                <input
                    id="promotion-redemption-url"
                    type="url"
                    wire:model="redemptionBaseUrl"
                    autocomplete="url"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    placeholder="https://www.example.de"
                >
                <p class="mt-1 text-xs text-gray-500">Basisadresse der Teilnehmer-App; der dauerhaft gedruckte Poster-QR verweist auf <span class="font-mono">/gluecksrad</span>.</p>
                @error('redemptionBaseUrl') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
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
