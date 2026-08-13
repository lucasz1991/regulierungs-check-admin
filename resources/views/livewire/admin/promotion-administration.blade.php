<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div><p class="text-xs font-bold uppercase tracking-wider text-teal-700">Nur Volladmin</p><h1 class="mt-1 text-2xl font-bold text-gray-900">Promotion verwalten</h1><p class="mt-1 text-sm text-gray-500">Kampagnen, Preise, Korrekturen und die kryptografische Auditkette.</p></div>
        <a href="{{ route('promotion.console') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold">Zur Promotion-Konsole</a>
    </div>
    @if(session('status'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
    <div class="grid gap-5 xl:grid-cols-[260px_1fr]">
        <aside class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
            <button wire:click="newCampaign" class="mb-3 w-full rounded-lg bg-teal-700 px-3 py-2 text-sm font-semibold text-white">Neue Kampagne</button>
            @foreach($campaigns as $entry)<button wire:click="editCampaign({{ $entry->id }})" class="mb-1 w-full rounded-lg px-3 py-2 text-left text-sm {{ $campaignId === $entry->id ? 'bg-teal-50 font-semibold text-teal-800' : 'hover:bg-gray-50' }}"><span class="block">{{ $entry->name }}</span><span class="text-xs font-normal text-gray-500">{{ $entry->code }} · {{ $entry->isOpen() ? 'Aktiv' : 'Inaktiv' }}</span></button>@endforeach
        </aside>
        <div class="space-y-5">
            <form wire:submit="saveCampaign" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-gray-900">Kampagne</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-medium">Code<input wire:model="campaignCode" class="mt-1 w-full rounded-lg border-gray-300" required>@error('campaignCode')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm font-medium">Name<input wire:model="campaignName" class="mt-1 w-full rounded-lg border-gray-300" required>@error('campaignName')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm font-medium">Start<input type="datetime-local" wire:model="campaignStartsAt" class="mt-1 w-full rounded-lg border-gray-300"></label>
                    <label class="text-sm font-medium">Ende<input type="datetime-local" wire:model="campaignEndsAt" class="mt-1 w-full rounded-lg border-gray-300">@error('campaignEndsAt')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" wire:model="campaignIsActive" class="rounded border-gray-300 text-teal-700">Kampagne aktiviert</label>
                </div>
                <button class="mt-4 rounded-lg bg-teal-700 px-4 py-2 font-semibold text-white">Kampagne speichern</button>
            </form>
            @if($selectedCampaign)
                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach(['issued' => 'Offene QR-Codes', 'expired' => 'Abgelaufen', 'cancelled' => 'Storniert', 'disputed' => 'Beanstandet'] as $metricStatus => $metricLabel)
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $metricLabel }}</p><p class="mt-2 text-3xl font-bold text-gray-900">{{ (int) ($statusCounts[$metricStatus] ?? 0) }}</p></div>
                    @endforeach
                </section>
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-bold text-gray-900">Auffälligkeiten nach Mitarbeiter</h2><p class="mt-1 text-xs text-gray-500">Erfasste Vorgänge mit Verfall, Storno und Beanstandung im Vergleich.</p></div>
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead><tr class="text-left text-xs uppercase text-gray-500"><th class="px-5 py-3">Mitarbeiter</th><th>Erfasst</th><th>Abgelaufen</th><th>Storniert</th><th>Beanstandet</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($staffRows as $row)<tr><td class="px-5 py-3 font-semibold">{{ $staffNames[$row->issued_by] ?? 'Gelöschtes Konto' }}</td><td>{{ $row->total }}</td><td>{{ $row->expired_total }}</td><td>{{ $row->cancelled_total }}</td><td>{{ $row->disputed_total }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-6 text-center text-gray-500">Noch keine Vorgänge.</td></tr>@endforelse</tbody></table></div>
                </section>
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between"><h2 class="font-bold text-gray-900">Preise</h2><span class="text-xs text-gray-500">Keine Gutscheincodes werden gespeichert</span></div>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label class="text-sm font-medium">Code<input wire:model="prizeCode" class="mt-1 w-full rounded-lg border-gray-300"></label>
                        <label class="text-sm font-medium">Bezeichnung<input wire:model="prizeName" class="mt-1 w-full rounded-lg border-gray-300"></label>
                        <label class="text-sm font-medium">Ausgabeart<select wire:model="prizeFulfillmentMode" class="mt-1 w-full rounded-lg border-gray-300"><option value="onsite_staff">Vor Ort durch Mitarbeiter</option><option value="external_admin">Extern durch Volladmin</option></select></label>
                        <label class="text-sm font-medium">Gesamtkontingent<input type="number" min="1" wire:model="prizeQuota" class="mt-1 w-full rounded-lg border-gray-300"></label>
                        <label class="text-sm font-medium">Sortierung<input type="number" min="0" wire:model="prizeSortOrder" class="mt-1 w-full rounded-lg border-gray-300"></label>
                        <label class="flex items-center gap-2 self-end pb-3 text-sm font-medium"><input type="checkbox" wire:model="prizeIsActive" class="rounded border-gray-300 text-teal-700">Aktiv</label>
                    </div>
                    @error('prizeQuota')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    <div class="mt-4 flex gap-3"><button wire:click.prevent="savePrize" class="rounded-lg bg-teal-700 px-4 py-2 font-semibold text-white">Preis speichern</button>@if($prizeId)<button type="button" wire:click="resetPrizeForm" class="rounded-lg border border-gray-300 px-4 py-2 font-semibold">Abbrechen</button>@endif</div>
                    <div class="mt-5 divide-y divide-gray-100 border-t border-gray-200">@foreach($selectedCampaign->prizes as $prize)<button wire:click="editPrize({{ $prize->id }})" class="flex w-full items-center justify-between py-3 text-left text-sm"><span><strong class="block">{{ $prize->name }}</strong><span class="text-xs text-gray-500">{{ $prize->code }} · {{ $prize->fulfillment_mode }}</span></span><span class="text-xs">{{ $prize->reserved_count }}/{{ $prize->quota }} reserviert</span></button>@endforeach</div>
                </section>
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-bold text-gray-900">Audit &amp; Korrekturen</h2><p class="text-xs text-gray-500">Stornierungen ersetzen keine Historie, sondern erzeugen ein neues Hashketten-Ereignis.</p></div><button wire:click="verifyAudit" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold">Auditkette pruefen</button></div>
                    @if($auditResult)<div class="mt-3 rounded-lg px-3 py-2 text-sm {{ $auditResult['valid'] ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800' }}">{{ $auditResult['valid'] ? 'Kette gueltig' : 'Manipulationsverdacht' }} · {{ $auditResult['checked'] }} Ereignisse geprueft</div>@endif
                    <label class="mt-4 block text-sm font-medium text-gray-700">Nach Teilnahme-ID, Name oder E-Mail suchen<input wire:model.live.debounce.300ms="winSearch" class="mt-1 w-full rounded-lg border-gray-300" placeholder="RC-STR26-… oder Konto"></label>
                    <div class="mt-4 overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead><tr class="text-left text-xs uppercase text-gray-500"><th class="py-2">ID</th><th>Teilnahme / Konto</th><th>Preis</th><th>Erfasst von</th><th>Status</th><th></th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($recentWins as $win)<tr><td class="py-2">#{{ $win->id }}</td><td><span class="block font-mono text-xs">{{ $win->participation?->public_id }}</span><span class="text-xs text-gray-500">{{ $win->participation?->user?->name }} · {{ $win->participation?->user?->email }}</span></td><td>{{ $win->prize_name_snapshot }}</td><td>{{ $win->issuedBy?->name ?? 'System' }}</td><td>{{ $win->status }}</td><td class="text-right">@if(!in_array($win->status, ['fulfilled','cancelled'], true))<button wire:click="prepareCorrection({{ $win->id }})" class="font-semibold text-red-700">Stornieren</button>@endif</td></tr>@empty<tr><td colspan="6" class="py-6 text-center text-gray-500">Keine passenden Vorgänge.</td></tr>@endforelse</tbody></table></div>
                    @if($correctionWinId)<form wire:submit="cancelWin" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4"><label class="text-sm font-semibold text-red-900">Stornogrund fuer Vorgang #{{ $correctionWinId }}<select wire:model="correctionReason" class="mt-1 w-full rounded-lg border-red-300 bg-white" required><option value="">Bitte auswaehlen</option><option value="issued_in_error">Gewinn irrtuemlich erfasst</option><option value="participant_dispute_upheld">Beanstandung des Teilnehmers bestaetigt</option><option value="campaign_cancelled">Kampagne abgebrochen</option><option value="technical_duplicate">Technisches Duplikat</option><option value="expired_reservation_released">Abgelaufene Reservierung freigeben</option></select></label><p class="mt-2 text-xs text-red-800">Keine Namen, E-Mail-Adressen oder Gutscheincodes eingeben; es werden ausschliesslich feste Grundcodes gespeichert.</p>@error('correctionReason')<p class="text-sm text-red-700">{{ $message }}</p>@enderror<button class="mt-2 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white">Unwiderruflich stornieren</button></form>@endif
                </section>
            @endif
        </div>
    </div>
</div>
