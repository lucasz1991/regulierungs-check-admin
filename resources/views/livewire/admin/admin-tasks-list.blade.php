<div class="relative">
    <style>
        :root {
            --tasks-ink: #0f172a;
            --tasks-muted: #475569;
            --tasks-line: #dbe3ee;
            --tasks-surface: #ffffff;
            --tasks-accent-a: #0ea5a4;
            --tasks-accent-b: #f59e0b;
            --tasks-bg-a: #ecfeff;
            --tasks-bg-b: #fff7ed;
        }
    </style>

    @php
        $openCount = $tasks->where('status', 0)->count();
        $inProgressCount = $tasks->where('status', 1)->count();
        $doneCount = $tasks->where('status', 2)->count();
    @endphp

    <section class="mb-6 rounded-2xl border p-6 shadow-sm"
             style="background: linear-gradient(120deg, var(--tasks-bg-a), var(--tasks-bg-b)); border-color: var(--tasks-line);">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold tracking-[0.16em] uppercase" style="color: var(--tasks-muted);">Admin Dashboard</p>
                <h1 class="mt-1 text-3xl font-black leading-tight" style="color: var(--tasks-ink);">Aufgabenmanagement</h1>
                <p class="mt-2 text-sm" style="color: var(--tasks-muted);">
                    Klare Priorisierung, schnelle Uebernahme und sauberer Abschluss deiner Aufgaben.
                </p>
            </div>

            <div class="grid w-full max-w-xl grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border bg-white/80 p-3 backdrop-blur" style="border-color: var(--tasks-line);">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Offen</p>
                    <p class="mt-1 text-2xl font-extrabold text-rose-600">{{ $openCount }}</p>
                </div>
                <div class="rounded-xl border bg-white/80 p-3 backdrop-blur" style="border-color: var(--tasks-line);">
                    <p class="text-xs uppercase tracking-wide text-slate-500">In Bearbeitung</p>
                    <p class="mt-1 text-2xl font-extrabold text-amber-600">{{ $inProgressCount }}</p>
                </div>
                <div class="rounded-xl border bg-white/80 p-3 backdrop-blur" style="border-color: var(--tasks-line);">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Abgeschlossen</p>
                    <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ $doneCount }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-6 rounded-2xl border bg-white p-4 shadow-sm" style="border-color: var(--tasks-line);">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 rounded-lg bg-cyan-100 p-2 text-cyan-700">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 5.25a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5zM13 17h-2v-6h2v6z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-base font-bold" style="color: var(--tasks-ink);">Hinweis zur Aufgabenverwaltung</h2>
                <p class="mt-1 text-sm" style="color: var(--tasks-muted);">
                    Offene Aufgaben koennen uebernommen werden. Aufgaben in Bearbeitung sind bereits zugewiesen. Abgeschlossene Aufgaben sind erledigt.
                </p>
                <p class="mt-1 text-sm" style="color: var(--tasks-muted);">
                    Mit <span class="font-semibold text-cyan-700">Uebernehmen</span> startest du die Bearbeitung, mit
                    <span class="font-semibold text-emerald-700">Abschliessen</span> beendest du sie.
                </p>
            </div>
        </div>
    </section>

    <div class="mb-3 hidden rounded-xl border bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 md:grid md:grid-cols-12"
         style="border-color: var(--tasks-line);">
        <div class="col-span-1">ID</div>
        <div class="col-span-3">Aufgabentyp</div>
        <div class="col-span-3">Zugehoerigkeit</div>
        <div class="col-span-3">Zugewiesen an</div>
        <div class="col-span-2 text-right">Status</div>
    </div>

    <div class="space-y-3">
        @forelse ($tasks as $task)
            @php
                $typeLabel = $task->type === 'claim_verification' ? 'Claim-Verifikation' : $task->type;

                $relatedLabel = match ($task->related_model_type) {
                    'App\\Models\\User' => 'User',
                    'App\\Models\\ClaimRating' => 'Bewertung',
                    default => 'Nicht zugeordnet',
                };

                $statusLabel = match ($task->status) {
                    0 => 'Offen',
                    1 => 'In Bearbeitung',
                    2 => 'Abgeschlossen',
                    default => 'Unbekannt',
                };

                $statusClass = match ($task->status) {
                    0 => 'bg-rose-100 text-rose-700 ring-rose-200',
                    1 => 'bg-amber-100 text-amber-700 ring-amber-200',
                    2 => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                    default => 'bg-slate-100 text-slate-700 ring-slate-200',
                };
            @endphp

            <article x-data="{ open: false }" @click.away="open = false" class="overflow-hidden rounded-2xl border bg-white shadow-sm"
                     style="border-color: var(--tasks-line);">
                <button
                    type="button"
                    @click="open = !open"
                    class="grid w-full grid-cols-1 gap-3 px-4 py-4 text-left transition hover:bg-slate-50 md:grid-cols-12 md:items-center"
                >
                    <div class="md:col-span-1">
                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">
                            #{{ $task->id }}
                        </span>
                    </div>

                    <div class="md:col-span-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500 md:hidden">Aufgabentyp</p>
                        <span class="inline-flex items-center rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-semibold text-cyan-800">
                            {{ $typeLabel }}
                        </span>
                    </div>

                    <div class="md:col-span-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500 md:hidden">Zugehoerigkeit</p>
                        <p class="text-sm font-medium text-slate-700">{{ $relatedLabel }}</p>
                    </div>

                    <div class="md:col-span-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500 md:hidden">Zugewiesen an</p>
                        <p class="text-sm font-medium text-slate-700">{{ $task->assignedTo ? $task->assignedTo->name : 'Nicht zugewiesen' }}</p>
                    </div>

                    <div class="md:col-span-2 md:text-right">
                        <div class="flex items-center gap-2 md:justify-end">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="{ 'rotate-180': open }"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </button>

                <div x-show="open" x-collapse x-cloak class="border-t px-4 pb-4 pt-4" style="border-color: var(--tasks-line); background: #fcfdff;">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="rounded-xl border bg-white p-4" style="border-color: var(--tasks-line);">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Aufgaben-Details</h3>
                            <p class="mt-2 text-sm whitespace-pre-line text-slate-700">
                                <span class="font-semibold">Beschreibung:</span><br>{{ $task->description }}
                            </p>
                            <p class="mt-3 text-xs text-slate-500">
                                Erstellt am: {{ $task->created_at->format('d.m.Y H:i') }}
                            </p>
                        </div>
                    </div>

                    @if($task->type === 'claim_verification')
                        @php
                            /** @var \App\Models\ClaimRating|null $claim */
                            $claim = $task->related instanceof \App\Models\ClaimRating
                                ? $task->related
                                : null;

                            $verification = $claim ? $claim->verification : [];
                            $verificationState = $verification['state'] ?? 'none';
                            $caseNumber = $verification['caseNumber'] ?? null;
                            $casefileUploaded = $verification['casefileUploaded'] ?? false;
                            $verificationFiles = $claim ? $claim->verificationFiles()->get() : collect();

                            $verificationLabel = match ($verificationState) {
                                'pending' => 'Verifikation offen',
                                'approved' => 'Verifiziert',
                                'rejected' => 'Abgelehnt',
                                default => 'Keine Verifikation',
                            };

                            $verificationClass = match ($verificationState) {
                                'pending' => 'bg-amber-100 text-amber-700 ring-amber-200',
                                'approved' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                                'rejected' => 'bg-rose-100 text-rose-700 ring-rose-200',
                                default => 'bg-slate-100 text-slate-700 ring-slate-200',
                            };
                        @endphp

                        <div class="mt-4 rounded-xl border bg-white p-4" style="border-color: var(--tasks-line);">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Claim-Verifikation</h4>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $verificationClass }}">
                                    {{ $verificationLabel }}
                                </span>
                            </div>

                            @if($claim)
                                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                                    <div class="space-y-1 text-slate-700">
                                        <p><span class="font-semibold">Bewertungs-ID:</span> {{ $claim->id }}</p>
                                        <p><span class="font-semibold">User-ID:</span> {{ $claim->user_id }}</p>
                                        <p><span class="font-semibold">Status:</span> {{ $claim->status }}</p>
                                        <p><span class="font-semibold">Verifikations-Score:</span> {{ $claim->verification_score }} / 60</p>
                                    </div>
                                    <div class="space-y-1 text-slate-700">
                                        <p><span class="font-semibold">Versicherung:</span> {{ optional($claim->insurance)->name ?? '-' }}</p>
                                        <p><span class="font-semibold">Fallnummer:</span> {{ $caseNumber ?: '-' }}</p>
                                        <p><span class="font-semibold">Verifikationsdatei vorhanden:</span> {{ $casefileUploaded ? 'Ja' : 'Nein' }}</p>
                                        <p><span class="font-semibold">Verifizierte User-E-Mail:</span> {{ $claim->user && $claim->user->email_verified_at ? 'Ja' : 'Nein' }}</p>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Verifikationsdateien</p>

                                    @if($verificationFiles->isEmpty())
                                        <p class="mt-2 text-sm text-slate-600">Keine Verifikationsdateien hinterlegt.</p>
                                    @else
                                        <ul class="mt-2 space-y-2">
                                            @foreach($verificationFiles as $file)
                                                <li class="flex flex-wrap items-center gap-2 rounded-lg border bg-slate-50 px-3 py-2 text-sm text-slate-700"
                                                    style="border-color: var(--tasks-line);">
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center rounded-full border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                                        x-on:click.stop="$dispatch('filepool-preview', { id: {{ $file->id }} })"
                                                        title="Datei-Vorschau oeffnen"
                                                    >
                                                        <i class="fas fa-eye mr-1"></i> Vorschau
                                                    </button>
                                                    <span class="font-semibold">Datei #{{ $file->id }}</span>
                                                    <span>- {{ $file->name ?? 'ohne Namen' }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @else
                                <p class="text-sm text-rose-600">Zugehoerige Bewertung konnte nicht geladen werden.</p>
                            @endif
                        </div>
                    @endif

                    <div class="mt-4 rounded-xl border bg-white p-4" style="border-color: var(--tasks-line);">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Aktionen</h3>
                            <span class="text-xs text-slate-400">Task #{{ $task->id }}</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if($task->type === 'claim_verification' && $task->status == 1 && $task->related instanceof \App\Models\ClaimRating)
                                <x-button
                                    wire:click.stop="approveClaimVerification({{ $task->id }})"
                                    class="btn-xs text-sm bg-emerald-100 text-emerald-700 hover:bg-emerald-200"
                                >
                                    Verifikation bestaetigen
                                </x-button>

                                <x-button
                                    wire:click.stop="rejectClaimVerification({{ $task->id }})"
                                    class="btn-xs text-sm bg-rose-100 text-rose-700 hover:bg-rose-200"
                                >
                                    Verifikation ablehnen
                                </x-button>
                            @endif

                            @if(!$task->assignedTo)
                                <x-button
                                    wire:click.stop="assignToMe({{ $task->id }})"
                                    class="btn-xs text-sm"
                                >
                                    Uebernehmen
                                </x-button>
                            @endif

                            @if($task->status == 1 && $task->type !== 'claim_verification')
                                <x-button
                                    wire:click.stop="markAsCompleted({{ $task->id }})"
                                    class="btn-xs text-sm text-emerald-600"
                                >
                                    Abschliessen
                                </x-button>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed bg-white p-10 text-center shadow-sm" style="border-color: var(--tasks-line);">
                <p class="text-lg font-semibold text-slate-700">Keine Aufgaben auf dieser Seite.</p>
                <p class="mt-1 text-sm text-slate-500">Sobald neue Aufgaben vorhanden sind, erscheinen sie hier.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-5">
        {{ $tasks->links() }}
    </div>

    <livewire:tools.file-pools.file-preview-modal />
</div>
