@can('mails.manage')
@once
    <link rel="stylesheet" href="{{ asset('adminresources/css/customer-mail.css') }}">
@endonce
@php($composerId = 'customer-mail-'.$this->getId())
<x-modal :id="$composerId" :labelled-by="$composerId.'-title'" max-width="2xl" wire:model="showMailModal">
    @if($showMailModal)
        @php($recipientCount = $this->mailRecipients()->count())
        <div class="mail-composer">
            <header class="mail-composer__header">
                <div>
                    <h2 id="{{ $composerId }}-title">{{ $mailPreview ? 'Versandvorschau' : 'Nachricht verfassen' }}</h2>
                    <p>{{ $recipientCount }} {{ $recipientCount === 1 ? 'erreichbarer Empfänger' : 'erreichbare Empfänger' }} · {{ count($mailIds) }} ausgewählt</p>
                </div>
                <button type="button" class="mail-composer__close" wire:click="resetMailModal" aria-label="Schließen">&times;</button>
            </header>
            <div class="mail-composer__body" wire:key="{{ $composerId }}-{{ $mailPreview ? 'preview' : 'edit' }}">
                @error('mailIds') <p role="alert" class="mail-composer__error">{{ $message }}</p> @enderror
                @if(!$mailPreview)
                    <fieldset class="mail-composer__channels">
                        <legend>Versandart</legend>
                        <div class="mail-composer__options">
                            @foreach(['message' => 'Interne Systemnachricht', 'both' => 'Systemnachricht und E-Mail', 'mail' => 'Nur E-Mail'] as $mode => $label)
                                <label>
                                    <input type="radio" name="{{ $composerId }}-mode" value="{{ $mode }}" wire:model.live="mailType"
                                        @checked($mailType === $mode) @disabled($this->externalMailOnly() && $mode !== 'mail')>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('mailType') <p role="alert" class="mail-composer__error">{{ $message }}</p> @enderror
                        @if($this->externalMailOnly())<p class="mail-composer__hint">Kontakte ohne Benutzerkonto erreichst du per E-Mail.</p>@endif
                    </fieldset>
                    <div class="mail-composer__fields">
                        <label>Betreff
                            <input type="text" wire:model="mailSubject" maxlength="255" @if($errors->has('mailSubject')) aria-invalid="true" @endif>
                            @error('mailSubject') <span role="alert" class="mail-composer__error">{{ $message }}</span> @enderror
                        </label>
                        @if($mailType !== 'message')
                            <label>Anrede oder Überschrift
                                <input type="text" wire:model="mailHeader" maxlength="255" @if($errors->has('mailHeader')) aria-invalid="true" @endif>
                                @error('mailHeader') <span role="alert" class="mail-composer__error">{{ $message }}</span> @enderror
                            </label>
                        @endif
                    </div>
                    <label class="mail-composer__field">Nachricht
                        <textarea wire:model="mailBody" rows="5" maxlength="50000" @if($errors->has('mailBody')) aria-invalid="true" @endif></textarea>
                        @error('mailBody') <span role="alert" class="mail-composer__error">{{ $message }}</span> @enderror
                    </label>
                    @if($mailType !== 'message')
                        <div class="mail-composer__fields">
                            <label><span>Link <span class="mail-composer__optional">(optional)</span></span>
                                <input type="url" placeholder="https://" wire:model="mailLink" maxlength="2048" @if($errors->has('mailLink')) aria-invalid="true" @endif>
                                @error('mailLink') <span role="alert" class="mail-composer__error">{{ $message }}</span> @enderror
                            </label>
                            <label><span>Buttontext <span class="mail-composer__optional">(optional)</span></span>
                                <input type="text" placeholder="Weiter" wire:model="mailButtonLabel" maxlength="80" @if($errors->has('mailButtonLabel')) aria-invalid="true" @endif>
                                @error('mailButtonLabel') <span role="alert" class="mail-composer__error">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    @endif
                @else
                    @if($mailType !== 'mail')
                        <section class="mail-composer__preview"><h3>Internes Postfach</h3>
                            <div class="mail-composer__message"><strong>{{ $mailSubject }}</strong><p>{{ $mailBody }}</p></div>
                        </section>
                    @endif
                    @if($mailType !== 'message')
                        <section class="mail-composer__preview"><h3>E-Mail · {{ $mailSubject }}</h3>
                            <iframe title="E-Mail-Vorschau" sandbox referrerpolicy="no-referrer" srcdoc="{{ $this->customerMailPreviewHtml() }}"></iframe>
                        </section>
                    @endif
                @endif
            </div>
            <footer class="mail-composer__actions">
                <button type="button" class="mail-composer__cancel" wire:click="resetMailModal">Abbrechen</button>
                @if($mailPreview)
                    <button type="button" wire:click="$set('mailPreview', false)">Bearbeiten</button>
                @else
                    <button type="button" wire:click="previewMail" wire:loading.attr="disabled">Vorschau</button>
                @endif
                <button type="button" class="mail-composer__send" wire:click="sendMail" wire:loading.attr="disabled" @disabled(!$recipientCount)>Versand einplanen</button>
            </footer>
        </div>
    @endif
</x-modal>
@endcan
