<div class="">
    {{-- Hinweisbox --}}
    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-500" xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 23.625 23.625" fill="currentColor" aria-hidden="true">
                    <path
                        d="M11.812 0C5.289 0 0 5.289 0 11.812s5.289 11.813 11.812 11.813 11.813-5.29 11.813-11.813S18.335 0 11.812 0zm2.459 18.307c-.608.24-1.092.422-1.455.548a3.838 3.838 0 0 1-1.262.189c-.736 0-1.309-.18-1.717-.539s-.611-.814-.611-1.367c0-.215.015-.435.045-.659a8.23 8.23 0 0 1 .147-.759l.761-2.688c.067-.258.125-.503.171-.731.046-.23.068-.441.068-.633 0-.342-.071-.582-.212-.717-.143-.135-.412-.201-.813-.201-.196 0-.398.029-.605.09-.205.063-.383.12-.529.176l.201-.828c.498-.203.975-.377 1.43-.521a4.225 4.225 0 0 1 1.29-.218c.731 0 1.295.178 1.692.53.395.353.594.812.594 1.376 0 .117-.014.323-.041.617a4.129 4.129 0 0 1-.152.811l-.757 2.68a7.582 7.582 0 0 0-.167.736 3.892 3.892 0 0 0-.073.626c0 .356.079.599.239.728.158.129.435.194.827.194.185 0 .392-.033.626-.097.232-.064.4-.121.506-.17l-.203.827zm-.134-10.878a1.807 1.807 0 0 1-1.275.492c-.496 0-.924-.164-1.28-.492a1.57 1.57 0 0 1-.533-1.193c0-.465.18-.865.533-1.196a1.812 1.812 0 0 1 1.28-.497c.497 0 .923.165 1.275.497.353.331.53.731.53 1.196 0 .467-.177.865-.53 1.193z"
                        data-original="#030104" />
                </svg>
            </div>
            <div class="ml-3">
                <div class="text-sm">
                    <h2 class="text-lg font-semibold mb-2">Hinweis zur Aufgabenverwaltung</h2>
                    <p class="text-sm">
                        Hier findest du alle offenen, in Bearbeitung befindlichen und abgeschlossenen Aufgaben.  
                        Je nach Status können Aufgaben unterschiedlich behandelt werden:
                    </p>
                    <ul class="mt-2 text-sm list-disc list-inside">
                        <li><strong class="text-red-400">Offene Aufgaben</strong>  – Können übernommen und bearbeitet werden.</li>
                        <li><strong class="text-yellow-400">In Bearbeitung</strong>  – Bereits einem Admin zugewiesen.</li>
                        <li><strong class="text-green-400">Abgeschlossene Aufgaben</strong>  – Erledigt, keine weiteren Aktionen nötig.</li>
                    </ul>
                    <p class="mt-2 text-sm">
                        Falls du eine Aufgabe übernehmen möchtest, klicke auf <strong class="text-blue-600">"Übernehmen"</strong>.  
                        Sobald du sie erledigt hast, kannst du sie mit <strong class="text-blue-600">"Abschließen"</strong> als erledigt markieren. ✅
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Kopfbereich --}}
    <div class="mb-4 flex flex-wrap justify-between gap-4">
        <div class="mb-6 max-w-md">
            <h1 class="text-2xl font-bold text-gray-700">ToDo's</h1>
            <p class="text-gray-500">
                Es gibt insgesamt {{ $tasks->where('status', 0)->count() }} offene Aufgaben (auf dieser Seite).
            </p>
        </div>
    </div>

    {{-- Tabellenüberschrift --}}
    <div class="grid grid-cols-12 bg-gray-100 p-2 font-semibold text-gray-700 border-b border-gray-300">
        <div class="col-span-1">ID</div>
        <div class="col-span-3">Aufgabentyp</div>
        <div class="col-span-3">Zugehörigkeit</div>
        <div class="col-span-3">Zugewiesen an</div>
        <div class="col-span-2 text-right">Status</div>
    </div>

    {{-- Aufgaben-Liste --}}
    <div>
        @foreach ($tasks as $task)
            <div x-data="{ open: false }" class="border-b" @click.away="open = false">
                {{-- Tabellenzeile --}}
                <div
                    @click="open = !open"
                    class="cursor-pointer hover:bg-gray-50 grid grid-cols-12 items-center p-2 text-left"
                    x-bind:class="{ 'bg-blue-50': open }"
                >
                    <div class="col-span-1">{{ $task->id }}</div>
                    <div class="col-span-3">
                        @if($task->type === 'claim_verification')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                Claim-Verifikation
                            </span>
                        @else
                            {{ $task->type }}
                        @endif
                    </div>
                    <div class="col-span-3">
                        @switch($task->related_model_type)
                            @case('App\\Models\\User')
                                User
                                @break
                            @case('App\\Models\\ClaimRating')
                                Bewertung
                                @break
                            @default
                                Nicht zugeordnet
                        @endswitch
                    </div>
                    <div class="col-span-3">
                        {{ $task->assignedTo ? $task->assignedTo->name : 'Nicht zugewiesen' }}
                    </div>
                    <div class="col-span-2 text-right">
                        @switch($task->status)
                            @case(0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    Offen
                                </span>
                                @break
                            @case(1)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                    In Bearbeitung
                                </span>
                                @break
                            @case(2)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    Abgeschlossen
                                </span>
                                @break
                            @default
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                    Unbekannt
                                </span>
                        @endswitch
                    </div>
                </div>

                {{-- Detail-Karte --}}
                <div x-show="open" x-collapse x-cloak class="bg-blue-50 p-4 border-t">
                    <h3 class="text-lg font-bold mb-2">📝 Aufgaben-Details</h3>
                    <p class="text-sm whitespace-pre-line">
                        <strong>Beschreibung:</strong> {{ $task->description }}
                    </p>
                    <p class="text-sm mt-1">
                        <strong>Erstellt am:</strong> {{ $task->created_at->format('d.m.Y H:i') }}
                    </p>

                    {{-- Claim-Verifikations-Block --}}
                    @if($task->type === 'claim_verification')
                        @php
                            /** @var \App\Models\ClaimRating|null $claim */
                            $claim = $task->related;

                            $verification      = $claim?->verification ?? null;
                            $verificationState = $verification['state'] ?? 'none';
                            $caseNumber        = $verification['caseNumber'] ?? null;
                            $casefileUploaded  = $verification['casefileUploaded'] ?? false;

                            $verificationFiles = $claim
                                ? $claim->verificationFiles()->get()
                                : collect();
                        @endphp

                        <div class="mt-4 space-y-3 border-t border-blue-200 pt-3">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-base font-semibold flex items-center gap-2">
                                    🔍 Verifikation der Schadenbewertung
                                </h4>
                                @if($verificationState === 'pending')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Verifikation offen
                                    </span>
                                @elseif($verificationState === 'approved')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Verifiziert
                                    </span>
                                @elseif($verificationState === 'rejected')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Abgelehnt
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                        Keine Verifikation
                                    </span>
                                @endif
                            </div>

                            @if($claim)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div class="space-y-1">
                                        <p><strong>Bewertungs-ID:</strong> {{ $claim->id }}</p>
                                        <p><strong>User-ID:</strong> {{ $claim->user_id }}</p>
                                        <p><strong>Status:</strong> {{ $claim->status }}</p>
                                        <p><strong>Verifikations-Score:</strong> {{ $claim->verification_score }} / 60</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p>
                                            <strong>Versicherung:</strong>
                                            {{ optional($claim->insurance)->name ?? '—' }}
                                        </p>
                                        <p>
                                            <strong>Fallnummer:</strong>
                                            {{ $caseNumber ?: '—' }}
                                        </p>
                                        <p>
                                            <strong>Verifikationsdatei vorhanden:</strong>
                                            {{ $casefileUploaded ? 'Ja' : 'Nein' }}
                                        </p>
                                        <p>
                                            <strong>Verifizierte User-E-Mail:</strong>
                                            {{ $claim->user && $claim->user->email_verified_at ? 'Ja' : 'Nein' }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Verifikations-Dateien --}}
                                <div class="mt-3">
                                    <p class="font-semibold text-sm mb-1">📎 Verifikationsdateien</p>

                                    @if($verificationFiles->isEmpty())
                                        <p class="text-sm text-gray-600">
                                            Keine Verifikationsdateien hinterlegt.
                                        </p>
                                    @else
                                        <ul class="text-sm list-disc list-inside space-y-1">
                                            @foreach($verificationFiles as $file)
                                                <li>
                                                    {{-- Hier ggf. Download-Link mit deiner File-URL-Logik ergänzen --}}
                                                    <span class="font-medium">
                                                        Datei #{{ $file->id }}
                                                    </span>
                                                    – {{ $file->original_name ?? $file->file_name ?? 'ohne Namen' }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                {{-- Rohdaten optional anzeigen --}}
                                <details class="mt-3 text-xs">
                                    <summary class="cursor-pointer text-blue-600">
                                        Rohdaten (verification) anzeigen
                                    </summary>
                                    <pre class="mt-2 bg-white/60 p-2 rounded border overflow-x-auto">
{{ json_encode($verification, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                    </pre>
                                </details>
                            @else
                                <p class="mt-2 text-sm text-red-600">
                                    ⚠ Zugehörige Bewertung konnte nicht geladen werden.
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Footer mit Buttons --}}
                    <div class="mt-4 flex flex-wrap justify-end gap-2 border-t pt-3">
                        {{-- Claim-Verifikation Buttons (nur wenn in Bearbeitung + Claim vorhanden) --}}
                        @if($task->type === 'claim_verification' && $task->status == 1 && isset($claim))
                            <x-button
                                wire:click.stop="rejectClaimVerification({{ $task->id }})"
                                class="btn-xs text-sm bg-red-100 text-red-700 hover:bg-red-200"
                            >
                                ❌ Verifikation ablehnen
                            </x-button>

                            <x-button
                                wire:click.stop="approveClaimVerification({{ $task->id }})"
                                class="btn-xs text-sm bg-green-100 text-green-700 hover:bg-green-200"
                            >
                                ✅ Verifikation bestätigen
                            </x-button>
                        @endif

                        {{-- Übernehmen nur, wenn nicht zugewiesen --}}
                        @if(!$task->assignedTo)
                            <x-button
                                wire:click.stop="assignToMe({{ $task->id }})"
                                class="btn-xs text-sm"
                            >
                                ➕ Übernehmen
                            </x-button>
                        @endif

                        {{-- Generisches "Abschließen" nur für Nicht-Claim-Verifikations-Tasks --}}
                        @if($task->status == 1 && $task->type !== 'claim_verification')
                            <x-button
                                wire:click.stop="markAsCompleted({{ $task->id }})"
                                class="btn-xs text-sm text-green-500"
                            >
                                ✅ Abschließen
                            </x-button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $tasks->links() }}
    </div>
</div>
