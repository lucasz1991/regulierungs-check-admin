<div class="rc-profile__section-bar">
    <div class="rc-profile__section-heading">
        <p class="rc-profile__eyebrow">Teilnahmen &amp; Ergebnisse</p>
        <h2>Promotion-Profil</h2>
        <p>Persönliche Tickets, Drehungen, Gewinne und ihr Bearbeitungsstand.</p>
    </div>
    @if(auth()->user()?->isAdmin())
        <button type="button" wire:click="issuePromotionTestTicket" wire:loading.attr="disabled" class="rc-profile__button">Test-Ticket ausstellen</button>
    @endif
</div>

<div class="rc-profile__history">
    @forelse($promotionTickets as $ticket)
        @php
            $ticketStatus = $ticket->status->value;
            $ticketLabel = ['ready' => 'Bereit', 'active' => 'Am Glücksrad', 'completed' => 'Abgeschlossen', 'cancelled' => 'Storniert'][$ticketStatus] ?? $ticketStatus;
        @endphp
        <article class="rc-profile__ticket" wire:key="profile-ticket-{{ $ticket->id }}">
            <header class="rc-profile__ticket-heading">
                <div>
                    <p class="rc-profile__small">Ausgestellt am {{ $ticket->issued_at?->format('d.m.Y H:i') ?? '—' }}</p>
                    <h3>{{ $ticket->campaign?->name ?? 'Kampagne nicht mehr verfügbar' }}</h3>
                    <p class="rc-profile__identifier">Teilnahme {{ $ticket->participation?->public_id ?? $ticket->public_id }}</p>
                </div>
                <div class="rc-profile__meta">
                    @if($ticket->isTest())<x-admin.profile.badge tone="warning">Test-Ticket</x-admin.profile.badge>@endif
                    <x-admin.profile.badge :tone="$ticketStatus === 'completed' ? 'success' : ($ticketStatus === 'active' ? 'warning' : 'neutral')">{{ $ticketLabel }}</x-admin.profile.badge>
                </div>
            </header>
            <div class="rc-profile__ticket-body">
                <section>
                    <h4>Aufrufe am Glücksrad</h4>
                    <ol class="rc-profile__timeline">
                        @forelse($ticket->turns as $turn)
                            <li>
                                <strong>{{ $turn->started_at?->format('d.m.Y H:i') }}</strong>
                                <p>{{ ['active' => 'Aktiv', 'completed' => 'Abgeschlossen', 'released' => 'Freigegeben'][$turn->status->value] ?? $turn->status->value }} · {{ $turn->startedBy?->name ?? 'System' }}</p>
                                @if($turn->release_reason)<p>Abbruch: {{ $turn->release_reason }}</p>@endif
                            </li>
                        @empty
                            <li class="rc-profile__timeline-empty">Dieses Ticket wurde noch nicht aufgerufen.</li>
                        @endforelse
                    </ol>
                </section>
                <section>
                    <h4>Ergebnisse &amp; Versand</h4>
                    <div class="rc-profile__results">
                        @forelse($ticket->results as $result)
                            @php
                                $outcome = $result->outcome_type_snapshot->value;
                                $mailStatus = $result->mail_status->value;
                            @endphp
                            <div @class(['rc-profile__result', 'rc-profile__result--superseded' => $result->superseded_at])>
                                <div class="rc-profile__result-heading">
                                    <strong>{{ $result->label_snapshot }}</strong>
                                    <x-admin.profile.badge :tone="$outcome === 'prize' && ! $result->superseded_at ? 'success' : 'neutral'">{{ ['prize' => 'Gewinn', 'no_win' => 'Kein Gewinn', 'retry' => 'Zusatzdreh', 'quota_reroll' => 'Erneute Drehung'][$outcome] ?? $outcome }}</x-admin.profile.badge>
                                </div>
                                <p class="rc-profile__small">{{ $result->recorded_at?->format('d.m.Y H:i') }} · {{ $result->recordedBy?->name ?? 'System' }}</p>
                                <dl class="rc-profile__result-facts">
                                    <div><dt>E-Mail</dt><dd>{{ ['pending' => 'Offen', 'sent' => 'Versendet', 'failed' => 'Fehlgeschlagen', 'not_required' => 'Nicht erforderlich'][$mailStatus] ?? $mailStatus }}</dd></div>
                                    <div><dt>Gewinnausgabe</dt><dd>{{ $result->fulfilled_at?->format('d.m.Y H:i') ?? ($outcome === 'prize' ? 'Offen' : 'Nicht erforderlich') }}</dd></div>
                                </dl>
                                @if($result->corrects_result_id)<p class="rc-profile__small">Korrigiertes Ergebnis</p>@endif
                                @if($result->superseded_at)<p class="rc-profile__small">Durch eine Korrektur ersetzt</p>@endif
                                @if($mailStatus === 'failed' && auth()->user()?->isAdmin())
                                    <button type="button" wire:click="resendPromotionMail({{ $result->id }})" wire:loading.attr="disabled" class="rc-profile__text-link">E-Mail erneut senden</button>
                                @endif
                            </div>
                        @empty
                            <p class="rc-profile__note">Noch kein Drehergebnis vorhanden.</p>
                        @endforelse
                    </div>
                </section>
            </div>
            @if($ticket->isTest() && auth()->user()?->isAdmin() && $ticketStatus !== 'active')
                <footer class="rc-profile__ticket-footer">
                    <button type="button" wire:click="resetPromotionTestTicket({{ $ticket->id }})" wire:confirm="Test-Drehung zurücksetzen und ein neues Test-Ticket ausstellen?" wire:loading.attr="disabled" class="rc-profile__text-link">Test-Drehung zurücksetzen &amp; neues Ticket ausstellen</button>
                </footer>
            @endif
        </article>
    @empty
        <div class="rc-profile__empty"><h3>Noch keine Promotion-Teilnahme</h3><p>Dieses Konto hat bisher kein persönliches Glücksrad-Ticket.</p></div>
    @endforelse

    @if($legacyPromotionWins->isNotEmpty())
        <details class="rc-profile__disclosure">
            <summary>Frühere Gewinne <span>{{ $legacyPromotionWins->count() }}</span></summary>
            @foreach($legacyPromotionWins as $legacy)
                <div class="rc-profile__history-row"><div><strong>{{ $legacy->prize_name_snapshot }}</strong><p>{{ $legacy->campaign?->name }}</p></div><p>{{ $legacy->status }} · {{ $legacy->created_at?->format('d.m.Y H:i') }}</p></div>
            @endforeach
        </details>
    @endif
    @if($staffPromotionTurns->isNotEmpty())
        <details class="rc-profile__disclosure">
            <summary>Als Mitarbeiter betreute Teilnahmen</summary>
            <p class="rc-profile__note">Die letzten {{ $staffPromotionTurns->count() }} Aufrufe dieses Mitarbeiters.</p>
            @foreach($staffPromotionTurns as $turn)
                <div class="rc-profile__history-row">
                    <div><strong>{{ $turn->campaign?->name ?? 'Promotion' }}</strong><p>{{ $turn->ticket?->user?->name ?? 'Teilnehmer nicht mehr verfügbar' }} · {{ $turn->ticket?->participation?->public_id }}</p></div>
                    <p>{{ $turn->started_at?->format('d.m.Y H:i') }} · {{ ['active' => 'Aktiv', 'completed' => 'Abgeschlossen', 'released' => 'Freigegeben'][$turn->status->value] ?? $turn->status->value }}</p>
                </div>
            @endforeach
        </details>
    @endif
</div>
