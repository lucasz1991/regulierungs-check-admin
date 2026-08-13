<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Zugangsverwaltung</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Mitarbeiter</h1>
            <p class="mt-1 text-sm text-gray-500">Neue Zugänge entstehen per 72-Stunden-Einladung; bestehende Konten kann nur ein Volladmin hochstufen.</p>
        </div>
        <a href="{{ route('admin.team-permissions') }}" class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Teams &amp; Rechte</a>
    </div>

    @if(session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="font-bold text-gray-900">Zum Promotion-Team einladen</h2>
        <p class="mt-1 text-sm text-gray-500">Rolle und Rechte sind fest. Das Promotion-Team wird beim Senden automatisch angelegt oder auf den verbindlichen Rechtestand gebracht.</p>
        <form wire:submit="invite" class="mt-4 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <div>
                <label for="invite-email" class="mb-1 block text-sm font-medium text-gray-700">E-Mail</label>
                <input id="invite-email" wire:model="email" type="email" required class="w-full rounded-lg border border-gray-300 px-3 py-2">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="invite-position" class="mb-1 block text-sm font-medium text-gray-700">Funktion (optional)</label>
                <input id="invite-position" wire:model="position" class="w-full rounded-lg border border-gray-300 px-3 py-2" placeholder="z. B. Promotion-Mitarbeiter">
                @error('position') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-teal-700 px-4 py-2 font-semibold text-white hover:bg-teal-800 disabled:opacity-60">Einladung senden</button>
        </form>
    </section>

    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
        <h2 class="font-bold text-amber-950">Bestehendes Konto hochstufen</h2>
        <p class="mt-1 text-sm text-amber-900">Dieser Volladmin-Schritt ändert Rolle und aktuelles Team des Kontos. Eine noch nicht bestätigte E-Mail-Adresse wird dadurch nicht automatisch verifiziert.</p>
        <form wire:submit="promoteExisting" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
                <label for="existing-email" class="mb-1 block text-sm font-medium text-amber-950">E-Mail des bestehenden Kontos</label>
                <input id="existing-email" wire:model="existingEmail" type="email" required class="w-full rounded-lg border border-amber-300 bg-white px-3 py-2">
                @error('existingEmail') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:confirm="Das bestehende Konto wirklich zum Promotion-Mitarbeiter hochstufen?" wire:loading.attr="disabled" class="rounded-lg bg-amber-800 px-4 py-2 font-semibold text-white hover:bg-amber-900 disabled:opacity-60">Konto hochstufen</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-bold text-gray-900">Konten</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Rolle</th><th class="px-5 py-3">Aktuelles Team</th><th class="px-5 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($employees as $employee)
                        <tr><td class="px-5 py-3"><strong class="block text-gray-900">{{ $employee->name }}</strong><span class="text-gray-500">{{ $employee->email }}</span></td><td class="px-5 py-3">{{ $employee->role === 'admin' ? 'Volladmin' : 'Mitarbeiter' }}</td><td class="px-5 py-3">{{ $employee->currentTeam?->name ?? 'Kein Team' }}</td><td class="px-5 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $employee->status ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $employee->status ? 'Aktiv' : 'Deaktiviert' }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">Keine Mitarbeiterkonten vorhanden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-5 py-3">{{ $employees->links('vendor.pagination.tailwind') }}</div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-bold text-gray-900">Einladungsverlauf</h2></div>
        <div class="divide-y divide-gray-100">
            @forelse($invitations as $invitation)
                <div class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><strong class="text-sm text-gray-900">{{ $invitation->email }}</strong><p class="text-xs text-gray-500">{{ $invitation->team->name }} · eingeladen von {{ $invitation->inviter->name }} · Ablauf {{ $invitation->expires_at->format('d.m.Y H:i') }}</p></div>
                    <div class="flex items-center gap-3">
                        @if($invitation->accepted_at)
                            <span class="text-xs font-semibold text-emerald-700">Angenommen</span>
                        @elseif($invitation->expires_at->isFuture())
                            <span class="text-xs font-semibold text-amber-700">Offen</span>
                            <button wire:click="revoke({{ $invitation->id }})" wire:confirm="Einladung wirklich widerrufen?" class="text-xs font-semibold text-red-700">Widerrufen</button>
                        @else
                            <span class="text-xs font-semibold text-gray-500">Abgelaufen</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-500">Noch keine Einladungen.</p>
            @endforelse
        </div>
    </section>
</div>
