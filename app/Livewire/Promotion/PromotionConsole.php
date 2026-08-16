<?php

namespace App\Livewire\Promotion;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Models\PromotionPrize;
use App\Models\PromotionSpinResult;
use App\Models\PromotionTurn;
use App\Models\User;
use App\Services\Promotion\PromotionResultMailer;
use App\Services\Promotion\PromotionTicketService;
use App\Services\Promotion\PromotionTurnService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PromotionConsole extends Component
{
    use RequiresRbacPermission;

    #[Locked]
    public ?int $correctionResultId = null;

    #[Locked]
    public ?int $recoveryCampaignId = null;

    public ?int $correctionPrizeId = null;

    public string $correctionReason = 'staff_correction';

    protected function requiredRbacPermission(): string
    {
        return 'promotion.wins.record';
    }

    /** @return array{ok: bool, turn_id?: int, participant?: array<string, string|null>, message?: string} */
    public function scanTicket(
        string $payload,
        PromotionTurnService $turns,
        PromotionTicketService $tickets,
    ): array {
        $this->authorize('promotion.wins.record');

        if (! $tickets->publicCampaign()) {
            return $this->failure('Neue Scans sind pausiert, weil die Promotion deaktiviert oder die Kampagne beendet ist. Ein bereits aktiver Aufruf kann weiterhin abgeschlossen oder freigegeben werden.');
        }

        $payload = trim($payload);
        if ($payload === '' || mb_strlen($payload) > 2048) {
            return $this->failure('Bitte einen gültigen Ticket-QR oder eine Teilnahme-ID verwenden.');
        }

        try {
            $turn = $turns->scanTicket($payload, $this->actor());
        } catch (ValidationException $exception) {
            return $this->failure($this->validationMessage($exception));
        } catch (\DomainException $exception) {
            return $this->failure($exception->getMessage());
        }

        $turn->loadMissing(['ticket.participation.user', 'ticket.user', 'campaign', 'startedBy']);

        return ['ok' => true] + $this->turnPayload($turn);
    }

    /** @return array{ok: bool, final?: bool, title?: string, message?: string, instruction?: string|null, scan_next?: bool} */
    public function recordResult(
        int $turnId,
        int $fieldId,
        PromotionTurnService $turns,
        PromotionResultMailer $mailer,
        PromotionTicketService $tickets,
    ): array {
        $this->authorize('promotion.wins.record');

        try {
            $turn = PromotionTurn::query()->findOrFail($turnId);
            $field = PromotionPrize::query()->findOrFail($fieldId);
            abort_unless((int) $field->campaign_id === (int) $turn->campaign_id, 422);

            $result = $turns->recordResult($turn, $field, $field->outcome_type->value, $this->actor());
        } catch (ValidationException $exception) {
            return $this->failure($this->validationMessage($exception));
        } catch (\DomainException $exception) {
            return $this->failure($exception->getMessage());
        }

        if (! $result->is_final) {
            $quotaReroll = $result->outcome_type_snapshot->value === 'quota_reroll';

            return [
                'ok' => true,
                'final' => false,
                'title' => $quotaReroll ? 'Feld ist ausgeschöpft' : 'Zusatzdreh',
                'message' => $quotaReroll
                    ? 'Dieses Feld ist bereits ausgeschöpft. Der Dreh wurde protokolliert; bitte erneut drehen.'
                    : 'Der Zusatzdreh wurde protokolliert. Der Teilnehmer darf sofort noch einmal drehen.',
                'instruction' => $quotaReroll ? 'Bitte erneut drehen' : 'Zusatzdreh: Du darfst noch einmal drehen',
            ];
        }

        $mailSent = $mailer->send($result);

        return [
            'ok' => true,
            'final' => true,
            'title' => 'Ergebnis gespeichert',
            'message' => $result->label_snapshot.($mailSent ? ' wurde gespeichert und per E-Mail versendet.' : ' wurde gespeichert. Die E-Mail konnte nicht versendet werden.'),
            'scan_next' => $tickets->publicCampaign() !== null,
        ];
    }

    /** @return array{ok: bool, message?: string} */
    public function releaseTurn(int $turnId, PromotionTurnService $turns): array
    {
        $this->authorize('promotion.wins.record');

        try {
            $turns->releaseTurn(PromotionTurn::query()->findOrFail($turnId), $this->actor());
        } catch (ValidationException $exception) {
            return $this->failure($this->validationMessage($exception));
        } catch (\DomainException $exception) {
            return $this->failure($exception->getMessage());
        }

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function acknowledgeSticker(PromotionTurnService $turns, PromotionTicketService $tickets): array
    {
        $this->authorize('promotion.wins.record');

        $campaign = $tickets->publicCampaign();
        $campaign?->loadMissing('promotionState');
        if (! $campaign) {
            return $this->failure('Es ist keine öffentliche Kampagne aktiv.');
        }

        try {
            $turns->acknowledgeSticker($campaign, $this->actor());
        } catch (ValidationException $exception) {
            return $this->failure($this->validationMessage($exception));
        } catch (\DomainException $exception) {
            return $this->failure($exception->getMessage());
        }

        session()->flash('status', 'Der Sticker-Hinweis wurde bestätigt. Der nächste Teilnehmer kann gescannt werden.');

        return ['ok' => true];
    }

    public function fulfill(int $resultId, PromotionTurnService $turns): void
    {
        $result = PromotionSpinResult::query()->findOrFail($resultId);
        $mode = $this->fulfillmentMode($result);
        $permission = $mode === PromotionPrize::FULFILLMENT_EXTERNAL
            ? 'promotion.fulfillment.external'
            : 'promotion.fulfillment.onsite';

        $this->authorize($permission);
        abort_if($mode === PromotionPrize::FULFILLMENT_EXTERNAL && ! $this->actor()->isAdmin(), 403);

        $turns->fulfill($result, $this->actor());
        session()->flash('status', 'Die Ausgabe wurde einmalig dokumentiert.');
    }

    public function prepareCorrection(int $resultId): void
    {
        $this->authorize('promotion.wins.record');
        $result = PromotionSpinResult::query()->findOrFail($resultId);
        abort_unless((int) $result->recorded_by === (int) $this->actor()->id, 403);

        $this->correctionResultId = $result->id;
        $this->correctionPrizeId = $result->prize_id;
        $this->correctionReason = 'staff_correction';
    }

    public function cancelCorrection(): void
    {
        $this->authorize('promotion.wins.record');
        $this->reset('correctionResultId', 'correctionPrizeId');
        $this->correctionReason = 'staff_correction';
    }

    public function correctResult(PromotionTurnService $turns, PromotionResultMailer $mailer): void
    {
        $this->authorize('promotion.wins.record');
        $validated = $this->validate([
            'correctionResultId' => ['required', 'integer', 'exists:promotion_spin_results,id'],
            'correctionPrizeId' => ['required', 'integer', 'exists:prizes,id'],
            'correctionReason' => ['required', 'string', 'max:100'],
        ]);
        $result = PromotionSpinResult::query()->findOrFail($validated['correctionResultId']);
        $field = PromotionPrize::query()->findOrFail($validated['correctionPrizeId']);
        abort_if((int) $field->campaign_id !== (int) $result->campaign_id, 422);
        $outcome = $field->outcome_type->value;
        $corrected = $turns->correctResult($result, $field, $outcome, $this->actor(), $validated['correctionReason']);
        $mailSent = $mailer->send($corrected, true);
        $this->cancelCorrection();
        session()->flash('status', $mailSent
            ? 'Das korrigierte Ergebnis wurde gespeichert und dem Teilnehmer mitgeteilt.'
            : 'Das korrigierte Ergebnis wurde gespeichert. Die E-Mail ist fehlgeschlagen und kann durch einen Volladmin erneut versendet werden.');
    }

    public function displayParticipantName(?User $participant): string
    {
        if (! $participant) {
            return 'Nicht belegt';
        }

        if ($this->actor()->isAdmin()) {
            return $participant->name ?: 'Nicht belegt';
        }

        return collect(preg_split('/\s+/u', trim((string) $participant->name)) ?: [])
            ->filter()
            ->map(static fn (string $part): string => mb_substr($part, 0, 1).str_repeat('*', max(2, mb_strlen($part) - 1)))
            ->join(' ') ?: 'Nicht belegt';
    }

    public function displayParticipantEmail(?User $participant): string
    {
        $email = $participant?->email;
        if (! $email || ! str_contains($email, '@')) {
            return 'Nicht belegt';
        }

        if ($this->actor()->isAdmin()) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).str_repeat('*', max(3, mb_strlen($local) - 1)).'@'.$domain;
    }

    /** @return array{turn_id: int, participant: array{ticket_id: string, name: string, email: string, instruction: null}} */
    public function turnPayload(PromotionTurn $turn): array
    {
        $turn->loadMissing(['ticket.participation.user', 'ticket.user']);
        $ticket = $turn->ticket;
        $participant = $ticket?->user ?? $ticket?->participation?->user;

        return [
            'turn_id' => (int) $turn->id,
            'participant' => [
                'ticket_id' => (string) ($ticket?->participation?->public_id ?? ''),
                'name' => $this->displayParticipantName($participant),
                'email' => $this->displayParticipantEmail($participant),
                'instruction' => null,
            ],
        ];
    }

    public function render(PromotionTicketService $tickets)
    {
        $this->authorize('promotion.wins.record');

        $actor = $this->actor();
        $publicCampaign = $tickets->publicCampaign();
        $recoverableTurn = PromotionTurn::query()
            ->where('status', 'active')
            ->when(! $actor->isAdmin(), fn ($query) => $query->where('started_by', $actor->id))
            ->latest('started_at')
            ->first();

        if ($publicCampaign) {
            $this->recoveryCampaignId = (int) $publicCampaign->id;
        } elseif ($recoverableTurn) {
            $this->recoveryCampaignId = (int) $recoverableTurn->campaign_id;
        }

        $campaign = $publicCampaign
            ?? ($this->recoveryCampaignId ? \App\Models\PromotionCampaign::query()->find($this->recoveryCampaignId) : null);
        $newScansAllowed = $publicCampaign !== null;
        $turnQuery = PromotionTurn::query()
            ->when($campaign, fn ($query) => $query->where('campaign_id', $campaign->id));

        $recentTurns = Gate::allows('promotion.wins.view_all')
            ? (clone $turnQuery)
                ->with([
                    'ticket.participation.user:id,name,email',
                    'ticket.user:id,name,email',
                    'startedBy:id,name',
                    'results' => fn ($query) => $query->orderBy('sequence'),
                    'effectiveResult',
                ])
                ->latest('started_at')
                ->limit(20)
                ->get()
            : collect();

        $activeTurn = $campaign
            ? (clone $turnQuery)
                ->where('status', 'active')
                ->with(['ticket.participation.user:id,name,email', 'ticket.user:id,name,email', 'startedBy:id,name'])
                ->latest('started_at')
                ->first()
            : null;

        $resultFields = $campaign
            ? $campaign->prizes()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            : collect();
        $quotaPolicy = $campaign?->quota_exhaustion_policy instanceof \BackedEnum
            ? $campaign->quota_exhaustion_policy->value
            : (string) $campaign?->quota_exhaustion_policy;
        $scanBlockedByQuota = $quotaPolicy === 'block' && $resultFields->contains(
            static fn (PromotionPrize $field): bool => $field->outcome_type->value === 'prize'
                && (int) $field->awarded_count >= (int) $field->quota,
        );

        return view('livewire.promotion.promotion-console', [
            'campaign' => $campaign,
            'newScansAllowed' => $newScansAllowed,
            'stickerRequired' => (bool) $campaign?->promotionState?->sticker_required,
            'activeTurn' => $activeTurn,
            'recentTurns' => $recentTurns,
            'resultFields' => $resultFields,
            'scanBlockedByQuota' => $scanBlockedByQuota,
            'todayTotal' => $campaign ? (clone $turnQuery)->whereDate('started_at', today())->count() : 0,
            'todayCompleted' => $campaign ? (clone $turnQuery)->whereDate('completed_at', today())->where('status', 'completed')->count() : 0,
        ])->layout('layouts.promotion');
    }

    private function actor(): User
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && $actor->isActive(), 403);

        return $actor;
    }

    /** @return array{ok: false, message: string} */
    private function failure(string $message): array
    {
        return ['ok' => false, 'message' => trim($message) ?: 'Der Vorgang konnte nicht ausgeführt werden.'];
    }

    private function validationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            if (is_array($messages) && isset($messages[0])) {
                return (string) $messages[0];
            }
        }

        return $exception->getMessage();
    }

    private function fulfillmentMode(PromotionSpinResult $result): string
    {
        $mode = $result->fulfillment_mode_snapshot;

        return $mode instanceof \BackedEnum ? (string) $mode->value : (string) $mode;
    }
}
