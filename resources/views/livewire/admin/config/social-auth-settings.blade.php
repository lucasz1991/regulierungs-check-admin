<section aria-labelledby="social-auth-settings-title" class="space-y-5">
    <div>
        <p class="text-sm font-semibold text-teal-700">Anmeldung für Teilnehmer</p>
        <h2 id="social-auth-settings-title" class="mt-1 text-2xl font-bold text-gray-950">Google &amp; Apple</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">
            Zugangsdaten werden verschlüsselt in der gemeinsamen Datenbank gespeichert. OAuth-Zugriffs- und Refresh-Tokens werden nicht gespeichert.
        </p>
    </div>

    @if (! $schemaReady)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Die additive Glücksrad-V2-Migration ist noch nicht installiert. Social Login bleibt bis dahin deaktiviert.
        </div>
    @elseif ($configurationError)
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">{{ $configurationError }}</div>
    @else
        <div class="grid gap-5 xl:grid-cols-2">
            <form method="POST" action="{{ route('admin.social-auth.google.save') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 bg-white text-xl font-black text-blue-600 shadow-sm">G</span>
                        <h3 class="mt-4 text-lg font-bold text-gray-950">Google-Anmeldung</h3>
                        <p class="mt-1 text-sm text-gray-600">Für regulären Login, Registrierung und Glücksrad-Tickets.</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $google['enabled'] ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                        {{ $google['enabled'] ? 'Aktiv' : 'Aus' }}
                    </span>
                </div>

                <label class="mt-5 flex items-start gap-3 rounded-xl bg-gray-50 p-4">
                    <input type="hidden" name="google[enabled]" value="0">
                    <input type="checkbox" name="google[enabled]" value="1" @checked(old('google.enabled', $google['enabled'])) class="mt-1 rounded border-gray-300 text-teal-700 focus:ring-teal-600">
                    <span><strong class="block text-sm text-gray-900">Google anzeigen</strong><span class="mt-1 block text-xs text-gray-500">Nur bei vollständiger und geprüfter Konfiguration aktivieren.</span></span>
                </label>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="google-client-id" class="block text-sm font-semibold text-gray-800">Client ID</label>
                        <input id="google-client-id" name="google[client_id]" value="{{ old('google.client_id', $google['client_id']) }}" autocomplete="off" class="mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                    </div>
                    <div>
                        <label for="google-client-secret" class="block text-sm font-semibold text-gray-800">Client Secret</label>
                        <input id="google-client-secret" type="password" name="google[client_secret]" value="" autocomplete="new-password" class="mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-teal-600 focus:ring-teal-600" placeholder="{{ $google['configured'] ? 'Gespeichert · leer lassen zum Beibehalten' : 'Noch nicht gespeichert' }}">
                    </div>
                    <div>
                        <label for="google-redirect-uri" class="block text-sm font-semibold text-gray-800">Rücksprungadresse</label>
                        <input id="google-redirect-uri" type="url" name="google[redirect_uri]" value="{{ old('google.redirect_uri', $google['redirect_uri']) }}" autocomplete="off" class="mt-1 block w-full rounded-xl border-gray-300 font-mono text-xs focus:border-teal-600 focus:ring-teal-600">
                    </div>
                </div>

                @if ($errors->googleSocial->any())
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->googleSocial->first() }}</div>
                @endif

                <button type="submit" class="mt-5 w-full rounded-xl bg-gray-950 px-5 py-3 text-sm font-bold text-white hover:bg-gray-800 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-teal-200">Google sicher speichern</button>
            </form>

            <form method="POST" action="{{ route('admin.social-auth.apple.save') }}" enctype="multipart/form-data" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gray-950 text-xl font-black text-white shadow-sm">●</span>
                        <h3 class="mt-4 text-lg font-bold text-gray-950">Apple-Anmeldung</h3>
                        <p class="mt-1 text-sm text-gray-600">Die .p8-Datei wird nur im Upload-Request gelesen und niemals gespeichert.</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $apple['enabled'] && ! $apple['expired'] ? 'bg-emerald-100 text-emerald-800' : ($apple['expired'] ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600') }}">
                        {{ $apple['expired'] ? 'Abgelaufen' : ($apple['enabled'] ? 'Aktiv' : 'Aus') }}
                    </span>
                </div>

                <label class="mt-5 flex items-start gap-3 rounded-xl bg-gray-50 p-4">
                    <input type="hidden" name="apple[enabled]" value="0">
                    <input type="checkbox" name="apple[enabled]" value="1" @checked(old('apple.enabled', $apple['enabled'])) class="mt-1 rounded border-gray-300 text-teal-700 focus:ring-teal-600">
                    <span><strong class="block text-sm text-gray-900">Apple anzeigen</strong><span class="mt-1 block text-xs text-gray-500">Bleibt ohne vollständige Apple-Konfiguration automatisch ausgeblendet.</span></span>
                </label>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="apple-services-id" class="block text-sm font-semibold text-gray-800">Services ID</label>
                        <input id="apple-services-id" name="apple[client_id]" value="{{ old('apple.client_id', $apple['client_id']) }}" autocomplete="off" class="mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                    </div>
                    <div>
                        <label for="apple-team-id" class="block text-sm font-semibold text-gray-800">Team ID</label>
                        <input id="apple-team-id" name="apple[apple_team_id]" value="{{ old('apple.apple_team_id', $apple['apple_team_id']) }}" autocomplete="off" class="mt-1 block w-full rounded-xl border-gray-300 font-mono text-sm uppercase focus:border-teal-600 focus:ring-teal-600">
                    </div>
                    <div>
                        <label for="apple-key-id" class="block text-sm font-semibold text-gray-800">Key ID</label>
                        <input id="apple-key-id" name="apple[apple_key_id]" value="{{ old('apple.apple_key_id', $apple['apple_key_id']) }}" autocomplete="off" class="mt-1 block w-full rounded-xl border-gray-300 font-mono text-sm uppercase focus:border-teal-600 focus:ring-teal-600">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="apple-redirect-uri" class="block text-sm font-semibold text-gray-800">Rücksprungadresse</label>
                        <input id="apple-redirect-uri" type="url" name="apple[redirect_uri]" value="{{ old('apple.redirect_uri', $apple['redirect_uri']) }}" autocomplete="off" class="mt-1 block w-full rounded-xl border-gray-300 font-mono text-xs focus:border-teal-600 focus:ring-teal-600">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="apple-private-key" class="block text-sm font-semibold text-gray-800">Privater Apple-Schlüssel (.p8)</label>
                        <input id="apple-private-key" type="file" name="apple[private_key]" accept=".p8" class="mt-1 block w-full rounded-xl border border-dashed border-gray-300 bg-gray-50 p-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-gray-950 file:px-4 file:py-2 file:font-semibold file:text-white">
                        <p class="mt-2 text-xs text-gray-500">
                            @if ($apple['expires_at'])
                                Aktuelles Client-Secret gültig bis {{ $apple['expires_at']->format('d.m.Y H:i') }} Uhr. Neue .p8-Datei nur zur Erneuerung wählen.
                            @else
                                Beim Speichern wird daraus einmalig ein zeitlich begrenztes Client-Secret erzeugt.
                            @endif
                        </p>
                    </div>
                </div>

                @if ($errors->appleSocial->any())
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->appleSocial->first() }}</div>
                @endif

                <button type="submit" class="mt-5 w-full rounded-xl bg-gray-950 px-5 py-3 text-sm font-bold text-white hover:bg-gray-800 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-teal-200">Apple sicher speichern</button>
            </form>
        </div>
    @endif
</section>
