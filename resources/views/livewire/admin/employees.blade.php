<div class="space-y-6">
    <header class="overflow-hidden rounded-2xl bg-[#082f35] shadow-sm">
        <div class="grid gap-6 px-6 py-7 text-white lg:grid-cols-[1fr_auto] lg:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#8dd7d1]">Zugangsverwaltung</p>
                <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Mitarbeiter &amp; Teams</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                    Administratoren wählen das Team direkt aus. Neue Mitarbeiter setzen über den zugesandten Link nur noch ihr Passwort und sind danach sofort angemeldet. Eine zusätzliche E-Mail-Verifizierung ist nicht erforderlich.
                </p>
            </div>
            <a href="{{ route('admin.team-permissions') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-[#ffd166] focus:ring-offset-2 focus:ring-offset-[#082f35]">
                Teams &amp; Rechte verwalten
            </a>
        </div>
    </header>

    @if(session('status'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                </span>
                <div>
                    <h2 class="font-bold text-gray-900">Neuen Mitarbeiterzugang versenden</h2>
                    <p class="mt-1 text-sm leading-5 text-gray-500">Die E-Mail wird direkt aus dem Admin-Bereich versendet. Es werden keine Jobs, Queue-Worker oder Cron-Aufgaben benötigt.</p>
                </div>
            </div>

            <form wire:submit="invite" class="mt-5 space-y-4">
                <div>
                    <label for="invite-email" class="mb-1.5 block text-sm font-semibold text-gray-700">E-Mail-Adresse</label>
                    <input id="invite-email" wire:model="email" type="email" autocomplete="email" required class="min-h-11 w-full rounded-xl border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-teal-600 focus:ring-teal-600">
                    @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="invite-team" class="mb-1.5 block text-sm font-semibold text-gray-700">Team</label>
                        <select id="invite-team" wire:model="teamId" required class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-teal-600 focus:ring-teal-600">
                            <option value="">Team auswählen</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                        @error('teamId') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="invite-position" class="mb-1.5 block text-sm font-semibold text-gray-700">Funktion <span class="font-normal text-gray-400">(optional)</span></label>
                        <input id="invite-position" wire:model="position" autocomplete="organization-title" class="min-h-11 w-full rounded-xl border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-teal-600 focus:ring-teal-600" placeholder="z. B. Redaktion">
                        @error('position') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button type="submit" wire:loading.attr="disabled" @disabled($teams->isEmpty()) class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-teal-700 px-4 py-2.5 font-semibold text-white transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="invite">Einrichtungslink senden</span>
                    <span wire:loading wire:target="invite">E-Mail wird versendet …</span>
                </button>
            </form>
        </section>

        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm sm:p-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487 18.55 2.8a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM16.862 4.487 19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21h-10.5A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                </span>
                <div>
                    <h2 class="font-bold text-amber-950">Bestehendes Konto zuordnen</h2>
                    <p class="mt-1 text-sm leading-5 text-amber-900">Das Konto wird zum aktiven Mitarbeiterkonto und erhält genau das ausgewählte Team. Eine vorhandene E-Mail-Verifizierung wird nicht vorausgesetzt.</p>
                </div>
            </div>

            <form wire:submit="assignExisting" class="mt-5 space-y-4">
                <div>
                    <label for="existing-email" class="mb-1.5 block text-sm font-semibold text-amber-950">E-Mail des bestehenden Kontos</label>
                    <input id="existing-email" wire:model="existingEmail" type="email" autocomplete="email" required class="min-h-11 w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-amber-700 focus:ring-amber-700">
                    @error('existingEmail') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="existing-team" class="mb-1.5 block text-sm font-semibold text-amber-950">Team</label>
                    <select id="existing-team" wire:model="existingTeamId" required class="min-h-11 w-full rounded-xl border border-amber-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-amber-700 focus:ring-amber-700">
                        <option value="">Team auswählen</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </select>
                    @error('existingTeamId') <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:confirm="Das bestehende Konto wirklich als Mitarbeiter dem ausgewählten Team zuordnen?" wire:loading.attr="disabled" @disabled($teams->isEmpty()) class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-amber-800 px-4 py-2.5 font-semibold text-white transition hover:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-700 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">Team zuweisen</button>
            </form>
        </section>
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-bold text-gray-900">Mitarbeiterkonten</h2><p class="mt-1 text-sm text-gray-500">Das aktuelle Team bestimmt die freigegebenen Adminmodule.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Rolle</th><th class="px-5 py-3">Aktuelles Team</th><th class="px-5 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($employees as $employee)
                        <tr><td class="px-5 py-3"><strong class="block text-gray-900">{{ $employee->name }}</strong><span class="text-gray-500">{{ $employee->email }}</span></td><td class="px-5 py-3">{{ $employee->role === 'admin' ? 'Administrator' : 'Mitarbeiter' }}</td><td class="px-5 py-3">{{ $employee->currentTeam?->name ?? 'Kein Team' }}</td><td class="px-5 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $employee->status ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $employee->status ? 'Aktiv' : 'Deaktiviert' }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">Keine Mitarbeiterkonten vorhanden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-5 py-3">{{ $employees->links('vendor.pagination.tailwind') }}</div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-bold text-gray-900">Zugangsverlauf</h2><p class="mt-1 text-sm text-gray-500">Versendete Einrichtungslinks und deren Status.</p></div>
        <div class="divide-y divide-gray-100">
            @forelse($invitations as $invitation)
                <div class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><strong class="text-sm text-gray-900">{{ $invitation->email }}</strong><p class="text-xs text-gray-500">Team {{ $invitation->team->name }} · versendet von {{ $invitation->inviter->name }} · gültig bis {{ $invitation->expires_at->format('d.m.Y H:i') }}</p></div>
                    <div class="flex items-center gap-3">
                        @if($invitation->accepted_at)
                            <span class="text-xs font-semibold text-emerald-700">Eingerichtet</span>
                        @elseif($invitation->expires_at->isFuture())
                            <span class="text-xs font-semibold text-amber-700">Offen</span>
                            <button type="button" wire:click="revoke({{ $invitation->id }})" wire:confirm="Einrichtungslink wirklich widerrufen?" class="text-xs font-semibold text-red-700 hover:text-red-800">Widerrufen</button>
                        @else
                            <span class="text-xs font-semibold text-gray-500">Abgelaufen</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">Noch keine Einrichtungslinks versendet.</p>
            @endforelse
        </div>
    </section>
</div>
