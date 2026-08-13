<div class="space-y-6">
    <div><p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Zugriffsschutz</p><h1 class="mt-1 text-2xl font-bold text-gray-900">Teams &amp; Rechte</h1><p class="mt-1 text-sm text-gray-500">Kritische Rechte sind nicht delegierbar und bleiben Volladmins vorbehalten.</p></div>
    @if(session('status')) <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div> @endif

    <div class="grid gap-5 lg:grid-cols-[280px_1fr]">
        <aside class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
            @forelse($teams as $team)
                <button wire:click="selectTeam({{ $team->id }})" class="mb-1 w-full rounded-lg px-3 py-2 text-left text-sm {{ $selectedTeamId === $team->id ? 'bg-teal-50 font-semibold text-teal-800' : 'text-gray-700 hover:bg-gray-50' }}"><span class="block">{{ $team->name }}</span><span class="text-xs font-normal text-gray-500">{{ $team->personal_team ? 'Persoenlich' : 'Gemeinsam' }}</span></button>
            @empty
                <p class="p-3 text-sm text-gray-500">Keine Teams vorhanden.</p>
            @endforelse
        </aside>

        @if($selectedTeam)
            <form wire:submit="save" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div><h2 class="font-bold text-gray-900">{{ $selectedTeam->name }}</h2>@if($selectedTeam->isPromotionTeam())<p class="mt-1 text-sm text-amber-700">Das Promotion-Team ist fest auf genau drei operative Rechte gehaertet.</p>@endif</div>
                @foreach($groups as $group => $entries)
                    <fieldset><legend class="mb-2 text-sm font-bold text-gray-800">{{ $group }}</legend><div class="grid gap-2 md:grid-cols-2">
                        @foreach($entries as $permission => $label)
                            @php
                                $promotionPermission = in_array($permission, \App\Support\Rbac\RbacCatalog::promotionTeamPermissions(), true);
                                $locked = in_array($permission, $adminOnly, true)
                                    || ($selectedTeam->isPromotionTeam() && ! $promotionPermission)
                                    || (! $selectedTeam->isPromotionTeam() && $promotionPermission);
                            @endphp
                            <label class="flex items-start gap-3 rounded-lg border px-3 py-2 {{ $locked ? 'border-gray-100 bg-gray-50 text-gray-400' : 'border-gray-200' }}">
                                <input type="checkbox" wire:click="togglePermission('{{ $permission }}')" @checked((bool) ($permissions[$permission] ?? false)) @disabled($locked) class="mt-1 rounded border-gray-300 text-teal-700 focus:ring-teal-600">
                                <span><span class="block text-sm font-medium">{{ $label }}</span><code class="text-[11px]">{{ $permission }}</code></span>
                            </label>
                        @endforeach
                    </div></fieldset>
                @endforeach
                <button class="rounded-lg bg-teal-700 px-4 py-2 font-semibold text-white hover:bg-teal-800">Rechte speichern</button>
            </form>
        @endif
    </div>
</div>
