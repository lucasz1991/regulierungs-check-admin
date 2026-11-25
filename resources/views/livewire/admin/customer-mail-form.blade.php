@can('mails.manage')
<x-dialog-modal wire:model="showMailModal">
    <x-slot name="title">Nachricht verfassen</x-slot>
    <x-slot name="content">
        @if($showMailModal)
            <fieldset class="mb-4">
                <legend class="mb-2 font-medium">Versandart</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach(['message' => 'Interne Systemnachricht', 'both' => 'Systemnachricht und E-Mail', 'mail' => 'Nur E-Mail'] as $mode => $label)
                        <label class="flex items-center gap-2 rounded border px-3 py-2 {{ $this->externalMailOnly() && $mode !== 'mail' ? 'text-gray-400' : 'cursor-pointer' }}">
                            <input type="radio" name="versandart-{{ $this->getId() }}" value="{{ $mode }}" wire:model.live="mailType" @checked($mailType === $mode) @disabled($this->externalMailOnly() && $mode !== 'mail')>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @if($this->externalMailOnly())<p class="mt-1 text-sm text-gray-600">Kontakte ohne Benutzerkonto haben kein internes Postfach.</p>@endif
            </fieldset>
            @php($recipientCount = $this->mailRecipients()->count())
            <p class="mb-3 text-sm text-gray-600">{{ $recipientCount }} erreichbare Empfänger · {{ count($mailIds) }} ausgewählt</p>
            @error('mailIds') <p class="text-red-700">{{ $message }}</p> @enderror
            <div class="space-y-3">
                @error('mailType') <p class="text-red-700">{{ $message }}</p> @enderror
                <label class="block">Betreff<input class="block w-full rounded border-gray-300" wire:model.blur="mailSubject" maxlength="255"></label>
                <x-input-error for="mailSubject" />
                @if($mailType !== 'message')
                    <label class="block">Überschrift oder Anrede<input class="block w-full rounded border-gray-300" wire:model.blur="mailHeader" maxlength="255"></label>
                    <x-input-error for="mailHeader" />
                @endif
                <label class="block">Nachricht<textarea class="block w-full rounded border-gray-300" wire:model.blur="mailBody" rows="5"></textarea></label>
                <x-input-error for="mailBody" />
                @if($mailType !== 'message')
                    <label class="block">Link (optional)<input class="block w-full rounded border-gray-300" wire:model.blur="mailLink" type="url"></label>
                    <x-input-error for="mailLink" />
                @endif
                @if($mailPreview)
                    @if($mailType !== 'message')
                        <iframe title="E-Mail-Vorschau" sandbox class="w-full h-80 rounded border" srcdoc="{{ $this->customerMailPreviewHtml() }}"></iframe>
                    @endif
                    @if($mailType !== 'mail')
                        <div class="rounded border p-3"><strong>{{ $mailSubject }}</strong><p class="whitespace-pre-line">{{ $mailBody }}</p></div>
                    @endif
                @endif
            </div>
        @endif
    </x-slot>
    <x-slot name="footer">
        <x-secondary-button wire:click="resetMailModal">Abbrechen</x-secondary-button>
        <x-secondary-button class="ml-2" wire:click="previewMail">Vorschau</x-secondary-button>
        <x-button class="ml-2" wire:click="sendMail" wire:loading.attr="disabled">Versand einplanen</x-button>
    </x-slot>
</x-dialog-modal>
@endcan
