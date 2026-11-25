<div class="space-y-4 p-4">
    @section('title', 'Kontakte')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-bold">Kontakte</h1><p class="text-sm text-gray-600">{{ $contacts->total() }} Treffer</p></div>
        <div class="flex gap-2"><livewire:admin.contacts.search-contacts-form /><x-button wire:click="create">Neuer Kontakt</x-button></div>
    </div>
    @if(session('contactNotice')) <p role="status" class="text-green-700">{{ session('contactNotice') }}</p> @endif
    <div class="flex flex-wrap gap-2">
        <input class="rounded border-gray-300" aria-label="Kontakte suchen" placeholder="Name, Firma oder E-Mail suchen" wire:model.live.debounce.350ms="search">
        <select class="rounded border-gray-300" aria-label="Branche" wire:model.live="category"><option value="">Alle Branchen</option>@foreach($categories as $item)<option>{{ $item }}</option>@endforeach</select>
        <select class="rounded border-gray-300" aria-label="Versandfreigabe" wire:model.live="permission"><option value="">Alle Versandfreigaben</option>@foreach(\App\Models\Contact::PERMISSIONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
    </div>
    <div class="flex flex-wrap items-center gap-3 text-sm">
        <button wire:click="selectPage" class="underline">Diese Seite auswählen</button>
        <button wire:click="selectResults" class="underline">Alle {{ $contacts->total() }} Treffer auswählen</button>
        @if($selected)
            <button wire:click="$set('selected', [])" class="underline">Auswahl aufheben ({{ count($selected) }})</button>
            @can('mails.manage')<x-button wire:click="openMailModal">E-Mail an Auswahl</x-button>@endcan
        @endif
    </div>
    <div class="overflow-x-auto rounded border bg-white">
        <table class="w-full text-left text-sm"><thead class="bg-gray-50"><tr>
            <th class="p-3"><span class="sr-only">Auswahl</span></th>
            @foreach(['name'=>'Name','company'=>'Firma','email'=>'E-Mail'] as $field=>$label)
                <th class="p-3"><button wire:click="sortByField('{{ $field }}')">{{ $label }} {{ $sortBy === $field ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</button></th>
            @endforeach
            <th class="p-3">Branche / Ort</th><th class="p-3">Versandfreigabe</th><th class="p-3">Aktionen</th>
        </tr></thead><tbody>
        @forelse($contacts as $contact)
            <tr wire:key="contact-{{ $contact->id }}" class="border-t">
                <td class="p-3"><input aria-label="{{ $contact->name }} auswählen" type="checkbox" wire:model.live="selected" value="{{ $contact->id }}"></td>
                <td class="p-3 font-medium">{{ $contact->name }}</td><td class="p-3">{{ $contact->company }}</td><td class="p-3">{{ $contact->email ?: '–' }}</td>
                <td class="p-3">{{ collect([$contact->category,$contact->city])->filter()->join(' · ') }}</td>
                <td class="p-3">{{ \App\Models\Contact::PERMISSIONS[$contact->mail_permission] ?? 'Keine Freigabe' }}</td>
                <td class="p-3"><div class="flex flex-wrap gap-2">
                    <button class="underline" wire:click="edit({{ $contact->id }})">Bearbeiten</button>
                    @can('mails.manage') @if($contact->email && $contact->mail_permission === 'allowed')
                        <button class="underline" wire:click="openMailModal({{ $contact->id }})">E-Mail</button>
                    @endif @endcan
                    <button class="text-red-700" wire:confirm="Diesen Kontakt löschen?" wire:click="delete({{ $contact->id }})">Löschen</button>
                </div></td>
            </tr>
        @empty <tr><td colspan="7" class="p-5">Keine Kontakte gefunden.</td></tr> @endforelse
        </tbody></table>
    </div>
    {{ $contacts->links() }}
    <x-dialog-modal wire:model="showForm">
        <x-slot name="title">{{ $contactId ? 'Kontakt bearbeiten' : 'Neuer Kontakt' }}</x-slot>
        <x-slot name="content">
            <div class="grid gap-3 sm:grid-cols-2">
            @foreach(\App\Models\Contact::FIELDS as $key=>$label)
                <label class="block">{{ $label }}{{ $key === 'name' ? ' *' : '' }}
                    <input class="block w-full rounded border-gray-300" wire:model="form.{{ $key }}" type="{{ $key === 'email' ? 'email' : 'text' }}">
                    @error('form.'.$key)<span class="text-red-700">{{ $message }}</span>@enderror
                </label>
            @endforeach
            <label>Versandfreigabe<select wire:model="form.mail_permission" class="block w-full rounded border-gray-300">
                @foreach(\App\Models\Contact::PERMISSIONS as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select><x-input-error for="form.mail_permission" /></label>
            </div>
        </x-slot>
        <x-slot name="footer"><x-secondary-button wire:click="$set('showForm', false)">Abbrechen</x-secondary-button><x-button class="ml-2" wire:click="save" wire:loading.attr="disabled">Speichern</x-button></x-slot>
    </x-dialog-modal>
    @include('livewire.admin.customer-mail-form')
</div>
