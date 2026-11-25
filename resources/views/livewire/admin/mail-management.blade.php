<div class="space-y-4">
    <h1 class="text-2xl font-bold">Versandaufträge</h1>
    @if(session('message'))<p role="status">{{ session('message') }}</p>@endif
    @php($labels = ['pending'=>'Eingeplant','processing'=>'In Übergabe','sent'=>'An Mailserver übergeben','failed'=>'Fehlgeschlagen','skipped'=>'Übersprungen','uncertain'=>'Übergabe unklar'])
    <div class="space-y-2">
    @forelse($mails as $mail)
        @php($recipients = collect($mail->recipients))
        @php($states = $recipients->map(fn($r) => $r['state'] ?? (!empty($r['status']) ? 'processed' : 'legacy'))->countBy())
        @php($channels = ['mail'=>'E-Mail','message'=>'Internes Postfach','both'=>'E-Mail und Postfach'])
        <details class="rounded border bg-white p-3" wire:key="mail-{{ $mail->id }}">
            <summary class="cursor-pointer"><strong>{{ $mail->content['subject'] ?? 'Nachricht' }}</strong> · {{ $channels[$mail->type] ?? 'Bestandsauftrag' }} · {{ $recipients->count() }} Empfänger
                <span class="ml-2 text-sm text-gray-600">{{ $mail->created_at?->format('d.m.Y H:i') }}</span>
                <span class="block text-sm">@foreach($states as $state=>$count){{ $count }} {{ $state === 'sent' && $mail->type === 'message' ? 'im Postfach' : ($labels[$state] ?? ($state === 'processed' ? 'verarbeitet (Bestand)' : 'ohne Versandnachweis')) }}{{ $loop->last ? '' : ' · ' }}@endforeach</span>
            </summary>
            <p class="my-2 whitespace-pre-line">{{ $mail->content['body'] ?? '' }}</p>
            <ul class="max-h-64 overflow-y-auto text-sm">@foreach($recipients as $recipient)<li>
                {{ $recipient['email'] ?? 'Benutzer' }} · {{ ($recipient['state'] ?? '') === 'sent' && $mail->type === 'message' ? 'Im Postfach' : ($labels[$recipient['state'] ?? ''] ?? (!empty($recipient['status']) ? 'Verarbeitet (Bestand)' : 'Ohne Versandnachweis')) }}
            </li>@endforeach</ul>
            @if($states->has('failed'))<x-secondary-button class="mt-3" wire:click="resendMail({{ $mail->id }})" wire:loading.attr="disabled">Fehlgeschlagene erneut einplanen</x-secondary-button>@endif
        </details>
    @empty <p>Keine Versandaufträge vorhanden.</p> @endforelse
    </div>
    {{ $mails->links() }}
</div>
