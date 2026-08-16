<?php

namespace App\Services\Promotion;

use App\Enums\PromotionFulfillmentMode;
use App\Enums\PromotionMailStatus;
use App\Enums\PromotionOutcomeType;
use App\Enums\PromotionQuotaPolicy;
use App\Enums\PromotionTicketStatus;
use App\Enums\PromotionTurnStatus;
use App\Models\Customer;
use App\Models\PromotionCampaign;
use App\Models\PromotionCampaignState;
use App\Models\PromotionParticipation;
use App\Models\PromotionPrize;
use App\Models\PromotionSpinResult;
use App\Models\PromotionTicket;
use App\Models\PromotionTurn;
use App\Models\Team;
use App\Models\User;
use App\Support\Promotion\ParticipationId;
use DomainException;
use Illuminate\Support\Facades\DB;
use ValueError;

final class PromotionTurnService
{
    public const CORRECTION_WINDOW_MINUTES = 10;

    public function __construct(
        private readonly PromotionAuditChain $audit,
        private readonly PromotionSettingsService $settings,
        private readonly PromotionTicketQrSigner $signer,
    ) {}

    public function scanTicket(string $payloadOrParticipationId, User $staff): PromotionTurn
    {
        $this->assertEnabled();
        $this->assertCanRecord($staff);
        $candidate = $this->ticketFromScanInput($payloadOrParticipationId);
        $manualEntry = ! str_starts_with(trim($payloadOrParticipationId), PromotionTicketQrSigner::VERSION.':');

        return DB::transaction(function () use ($candidate, $staff, $manualEntry): PromotionTurn {
            $staff = User::query()->lockForUpdate()->findOrFail($staff->getKey());
            $this->assertCanRecord($staff);
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($candidate->campaign_id);
            $ticket = PromotionTicket::query()->lockForUpdate()->findOrFail($candidate->getKey());
            $participation = PromotionParticipation::query()->lockForUpdate()->findOrFail($ticket->participation_id);
            $participant = User::query()->lockForUpdate()->find($ticket->user_id);
            $state = PromotionCampaignState::query()->whereKey($campaign->getKey())->lockForUpdate()->first();
            if (! $state) {
                throw new DomainException('Der geschuetzte Kampagnenzustand fehlt; das Ticket kann nicht gescannt werden.');
            }
            $this->assertPublicCampaign($campaign);
            $this->assertAuditIntegrity($campaign);

            if (! $participant
                || (int) $participation->user_id !== (int) $participant->getKey()
                || ! $participant->isActive()
                || ! $participant->hasVerifiedEmail()
                || $participant->role !== 'guest'
                || ! Customer::query()->where('user_id', $participant->getKey())->exists()) {
                throw new DomainException('Das Teilnehmerkonto ist deaktiviert oder nicht mehr bestaetigt.');
            }

            $participantTeamId = Team::query()->where('name', 'Benutzer')->value('id');
            if (! $participantTeamId
                || (int) $participant->current_team_id !== (int) $participantTeamId
                || ! $participant->teams()->whereKey($participantTeamId)->exists()) {
                throw new DomainException('Das Teilnehmerkonto ist keinem gueltigen Teilnehmer-Team mehr zugeordnet.');
            }

            if ($ticket->status !== PromotionTicketStatus::Ready) {
                throw new DomainException('Das Ticket wurde bereits verwendet, storniert oder ist gerade aktiv.');
            }

            if ($state->active_turn_id !== null) {
                throw new DomainException('Am Gluecksrad ist bereits ein anderer Teilnehmer aktiv.');
            }

            $exhaustedExists = PromotionPrize::query()
                ->where('campaign_id', $campaign->getKey())
                ->where('is_active', true)
                ->where('outcome_type', PromotionOutcomeType::Prize->value)
                ->whereColumn('awarded_count', '>=', 'quota')
                ->exists();
            if ($campaign->quota_exhaustion_policy === PromotionQuotaPolicy::Block && $exhaustedExists) {
                throw new DomainException('Neue Drehungen sind gesperrt, weil mindestens ein Gewinnkontingent erschoepft ist.');
            }
            if ($campaign->quota_exhaustion_policy === PromotionQuotaPolicy::StickerContinue && $state->sticker_required) {
                throw new DomainException('Vor dem naechsten Scan muss das Abkleben der erschoepften Radfelder bestaetigt werden.');
            }

            $now = now();
            $turn = PromotionTurn::query()->create([
                'ticket_id' => $ticket->getKey(),
                'campaign_id' => $campaign->getKey(),
                'started_by' => $staff->getKey(),
                'status' => PromotionTurnStatus::Active,
                'started_at' => $now,
            ]);
            $ticket->forceFill([
                'status' => PromotionTicketStatus::Active,
                'activated_at' => $now,
            ])->save();
            $state->forceFill(['active_turn_id' => $turn->getKey()])->save();

            $this->audit->appendV2($campaign, 'turn.started', $participation, $staff, [
                'status' => PromotionTurnStatus::Active->value,
                'manual_entry' => $manualEntry,
            ], $ticket, $turn);

            return $turn->fresh(['ticket.participation.user', 'campaign', 'startedBy', 'latestResult']);
        }, 5);
    }

    public function releaseTurn(PromotionTurn $turn, User $staff, string $reason = 'staff_cancelled'): PromotionTurn
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 120) {
            throw new DomainException('Fuer den Abbruch ist ein kurzer Grund erforderlich.');
        }

        return DB::transaction(function () use ($turn, $staff, $reason): PromotionTurn {
            $staff = User::query()->lockForUpdate()->findOrFail($staff->getKey());
            $this->assertCanRecord($staff);
            $turn = PromotionTurn::query()->lockForUpdate()->findOrFail($turn->getKey());
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($turn->campaign_id);
            $ticket = PromotionTicket::query()->lockForUpdate()->findOrFail($turn->ticket_id);
            $state = PromotionCampaignState::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();
            $this->assertAuditIntegrity($campaign);
            $this->assertTurnOwnerOrAdmin($turn, $staff);

            if ($turn->status !== PromotionTurnStatus::Active || (int) $state->active_turn_id !== (int) $turn->getKey()) {
                throw new DomainException('Dieser Gluecksrad-Aufruf ist nicht mehr aktiv.');
            }

            $now = now();
            $turn->forceFill([
                'status' => PromotionTurnStatus::Released,
                'released_by' => $staff->getKey(),
                'released_at' => $now,
                'release_reason' => $reason,
            ])->save();
            $ticket->forceFill([
                'status' => PromotionTicketStatus::Ready,
                'activated_at' => null,
            ])->save();
            $state->forceFill(['active_turn_id' => null])->save();

            $this->audit->appendV2($campaign, 'turn.released', $ticket->participation, $staff, [
                'reason_digest' => hash('sha256', $reason),
                'status' => PromotionTurnStatus::Released->value,
            ], $ticket, $turn);

            return $turn->fresh(['ticket.participation.user', 'campaign', 'startedBy']);
        }, 5);
    }

    public function acknowledgeSticker(PromotionCampaign $campaign, User $staff): PromotionCampaignState
    {
        return DB::transaction(function () use ($campaign, $staff): PromotionCampaignState {
            $staff = User::query()->lockForUpdate()->findOrFail($staff->getKey());
            $this->assertCanRecord($staff);
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($campaign->getKey());
            $state = PromotionCampaignState::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();
            $this->assertAuditIntegrity($campaign);
            if ($campaign->quota_exhaustion_policy !== PromotionQuotaPolicy::StickerContinue || ! $state->sticker_required) {
                throw new DomainException('Fuer diese Kampagne ist aktuell keine Sticker-Bestaetigung erforderlich.');
            }
            if ($state->active_turn_id !== null) {
                throw new DomainException('Waehrend einer aktiven Drehung kann die Radkonfiguration nicht bestaetigt werden.');
            }

            $state->forceFill([
                'sticker_required' => false,
                'sticker_acknowledged_at' => now(),
                'sticker_acknowledged_by' => $staff->getKey(),
            ])->save();
            $this->audit->appendV2($campaign, 'campaign.sticker_acknowledged', null, $staff, [
                'sticker_required' => false,
            ]);

            return $state->fresh(['campaign', 'stickerAcknowledgedBy']);
        }, 5);
    }

    public function recordResult(
        PromotionTurn $turn,
        ?PromotionPrize $prize,
        PromotionOutcomeType|string $outcomeType,
        User $staff,
    ): PromotionSpinResult {
        $requestedOutcome = $this->outcome($outcomeType);
        if ($requestedOutcome === PromotionOutcomeType::QuotaReroll) {
            throw new DomainException('Eine Kontingent-Neudrehung wird ausschliesslich automatisch erkannt.');
        }

        return DB::transaction(function () use ($turn, $prize, $requestedOutcome, $staff): PromotionSpinResult {
            $staff = User::query()->lockForUpdate()->findOrFail($staff->getKey());
            $this->assertCanRecord($staff);
            $turn = PromotionTurn::query()->lockForUpdate()->findOrFail($turn->getKey());
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($turn->campaign_id);
            $ticket = PromotionTicket::query()->lockForUpdate()->findOrFail($turn->ticket_id);
            $state = PromotionCampaignState::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();
            $prize = $prize ? PromotionPrize::query()->lockForUpdate()->findOrFail($prize->getKey()) : null;
            $this->assertAuditIntegrity($campaign);
            $this->assertTurnOwnerOrAdmin($turn, $staff);

            if ($turn->status !== PromotionTurnStatus::Active
                || $ticket->status !== PromotionTicketStatus::Active
                || (int) $state->active_turn_id !== (int) $turn->getKey()) {
                throw new DomainException('Dieser Teilnehmer ist nicht mehr am Gluecksrad aktiv.');
            }
            $this->assertPrizeSelection($campaign, $prize, $requestedOutcome);

            $actualOutcome = $requestedOutcome;
            if ($requestedOutcome === PromotionOutcomeType::Prize && $prize->awarded_count >= $prize->quota) {
                $actualOutcome = PromotionOutcomeType::QuotaReroll;
                if ($campaign->quota_exhaustion_policy === PromotionQuotaPolicy::StickerContinue) {
                    $state->forceFill([
                        'sticker_required' => true,
                        'sticker_acknowledged_at' => null,
                        'sticker_acknowledged_by' => null,
                    ])->save();
                }
            }
            $isFinal = $actualOutcome->isFinal();
            $sequence = ((int) PromotionSpinResult::query()->where('turn_id', $turn->getKey())->max('sequence')) + 1;
            $now = now();
            if ($actualOutcome === PromotionOutcomeType::Prize) {
                $prize->forceFill(['awarded_count' => $prize->awarded_count + 1])->save();
                if ($campaign->quota_exhaustion_policy === PromotionQuotaPolicy::StickerContinue
                    && $prize->awarded_count >= $prize->quota) {
                    $state->forceFill([
                        'sticker_required' => true,
                        'sticker_acknowledged_at' => null,
                        'sticker_acknowledged_by' => null,
                    ])->save();
                }
            }

            $result = PromotionSpinResult::query()->create([
                'turn_id' => $turn->getKey(),
                'ticket_id' => $ticket->getKey(),
                'campaign_id' => $campaign->getKey(),
                'prize_id' => $prize?->getKey(),
                'sequence' => $sequence,
                'outcome_type_snapshot' => $actualOutcome,
                'label_snapshot' => $actualOutcome === PromotionOutcomeType::QuotaReroll
                    ? 'Neudrehung wegen erschoepftem Feld: '.$prize->name
                    : (string) $prize?->name,
                'fulfillment_mode_snapshot' => $actualOutcome === PromotionOutcomeType::Prize
                    ? $prize->getRawOriginal('fulfillment_mode')
                    : null,
                'is_final' => $isFinal,
                'recorded_by' => $staff->getKey(),
                'recorded_at' => $now,
                'mail_status' => $isFinal ? PromotionMailStatus::Pending : PromotionMailStatus::NotRequired,
            ]);

            if ($isFinal) {
                $turn->forceFill([
                    'status' => PromotionTurnStatus::Completed,
                    'completed_by' => $staff->getKey(),
                    'completed_at' => $now,
                ])->save();
                $ticket->forceFill([
                    'status' => PromotionTicketStatus::Completed,
                    'completed_at' => $now,
                ])->save();
                $state->forceFill(['active_turn_id' => null])->save();
            }

            $this->audit->appendV2($campaign, 'spin.recorded', $ticket->participation, $staff, [
                'is_final' => $isFinal,
                'outcome_type' => $actualOutcome->value,
            ], $ticket, $turn, $result);

            return $result->fresh(['turn', 'ticket.participation.user', 'campaign', 'prize', 'recordedBy']);
        }, 5);
    }

    public function correctResult(
        PromotionSpinResult $result,
        ?PromotionPrize $prize,
        PromotionOutcomeType|string $outcomeType,
        User $staff,
        string $reason = 'staff_correction',
    ): PromotionSpinResult {
        return $this->correct($result, $prize, $outcomeType, $staff, $reason, false);
    }

    public function counterbookResult(
        PromotionSpinResult $result,
        ?PromotionPrize $prize,
        PromotionOutcomeType|string $outcomeType,
        User $admin,
        string $reason,
    ): PromotionSpinResult {
        return $this->correct($result, $prize, $outcomeType, $admin, $reason, true);
    }

    public function fulfill(PromotionSpinResult $result, User $actor): PromotionSpinResult
    {
        return DB::transaction(function () use ($result, $actor): PromotionSpinResult {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->getKey());
            $result = PromotionSpinResult::query()->lockForUpdate()->findOrFail($result->getKey());
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($result->campaign_id);
            $ticket = PromotionTicket::query()->lockForUpdate()->findOrFail($result->ticket_id);
            $turn = PromotionTurn::query()->lockForUpdate()->findOrFail($result->turn_id);
            $this->assertAuditIntegrity($campaign);

            if ($result->superseded_at !== null || ! $result->is_final || $result->outcome_type_snapshot !== PromotionOutcomeType::Prize) {
                throw new DomainException('Nur der aktuelle finale Gewinn kann als ausgegeben markiert werden.');
            }
            $ticket->loadMissing('user');
            if (! $ticket->user?->hasVerifiedEmail()) {
                throw new DomainException('Die E-Mail-Adresse des Teilnehmers ist noch nicht verifiziert.');
            }

            $mode = $result->fulfillment_mode_snapshot;
            $isAdmin = $this->isGlobalAdmin($actor);
            $canOnsite = $isAdmin || (method_exists($actor, 'hasRbacPermission') && $actor->hasRbacPermission('promotion.fulfillment.onsite'));
            if (! (bool) $actor->status) {
                throw new DomainException('Ein deaktiviertes Konto darf keine Ausgabe markieren.');
            }
            if (($mode === PromotionFulfillmentMode::ExternalAdmin && ! $isAdmin)
                || ($mode === PromotionFulfillmentMode::OnsiteStaff && ! $canOnsite)) {
                throw new DomainException('Keine Berechtigung fuer diese Ausgabemethode.');
            }
            if ($result->fulfilled_at !== null) {
                return $result;
            }

            $result->forceFill(['fulfilled_by' => $actor->getKey(), 'fulfilled_at' => now()])->save();
            $this->audit->appendV2($campaign, 'spin.fulfilled', $ticket->participation, $actor, [
                'fulfillment_mode' => $mode?->value,
            ], $ticket, $turn, $result);

            return $result->fresh(['ticket.participation.user', 'campaign', 'prize', 'fulfilledBy']);
        }, 5);
    }

    public function markMailSent(PromotionSpinResult $result): PromotionSpinResult
    {
        return $this->transitionMail($result, PromotionMailStatus::Sent);
    }

    public function markMailFailed(PromotionSpinResult $result, string $error): PromotionSpinResult
    {
        return $this->transitionMail($result, PromotionMailStatus::Failed, $error);
    }

    public function markMailPendingForResend(PromotionSpinResult $result, User $admin): PromotionSpinResult
    {
        if (! $this->isGlobalAdmin($admin)) {
            throw new DomainException('Nur ein Volladmin darf eine Ergebnismail erneut anfordern.');
        }

        return $this->transitionMail($result, PromotionMailStatus::Pending, null, $admin);
    }

    private function correct(
        PromotionSpinResult $result,
        ?PromotionPrize $prize,
        PromotionOutcomeType|string $outcomeType,
        User $actor,
        string $reason,
        bool $adminOverride,
    ): PromotionSpinResult {
        $targetOutcome = $this->outcome($outcomeType);
        $reason = trim($reason);
        if (! $targetOutcome->isFinal() || $reason === '' || mb_strlen($reason) > 255) {
            throw new DomainException('Korrekturen brauchen ein finales Ergebnis und einen gueltigen Grund.');
        }

        return DB::transaction(function () use ($result, $prize, $targetOutcome, $actor, $reason, $adminOverride): PromotionSpinResult {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->getKey());
            $result = PromotionSpinResult::query()->lockForUpdate()->findOrFail($result->getKey());
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($result->campaign_id);
            $turn = PromotionTurn::query()->lockForUpdate()->findOrFail($result->turn_id);
            $ticket = PromotionTicket::query()->lockForUpdate()->findOrFail($result->ticket_id);
            $this->assertAuditIntegrity($campaign);

            if ($adminOverride) {
                if (! $this->isGlobalAdmin($actor)) {
                    throw new DomainException('Nur ein Volladmin darf ausserhalb der Korrekturfrist gegenbuchen.');
                }
            } else {
                $this->assertCanRecord($actor);
                if ((int) $turn->started_by !== (int) $actor->getKey()) {
                    throw new DomainException('Nur der erfassende Mitarbeiter darf dieses Ergebnis korrigieren.');
                }
                if ((int) $result->recorded_by !== (int) $actor->getKey()) {
                    throw new DomainException('Nur der Mitarbeiter, der das aktuelle Ergebnis erfasst hat, darf es innerhalb der Frist korrigieren.');
                }
                if (! $turn->completed_at || $turn->completed_at->copy()->addMinutes(self::CORRECTION_WINDOW_MINUTES)->isPast()) {
                    throw new DomainException('Die zehnminuetige Korrekturfrist ist abgelaufen.');
                }
            }
            if ($result->superseded_at !== null || ! $result->is_final || $result->fulfilled_at !== null) {
                throw new DomainException('Dieses Ergebnis ist nicht mehr korrigierbar.');
            }

            $selectedPrizeId = $prize?->getKey();
            $ids = collect([$result->prize_id, $selectedPrizeId])->filter()->unique()->sort()->values();
            $lockedPrizes = PromotionPrize::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $selectedPrize = $selectedPrizeId === null ? null : $lockedPrizes->get($selectedPrizeId);
            $this->assertPrizeSelection($campaign, $selectedPrize, $targetOutcome);
            $oldPrize = $result->outcome_type_snapshot === PromotionOutcomeType::Prize ? $lockedPrizes->get($result->prize_id) : null;
            $newPrize = $targetOutcome === PromotionOutcomeType::Prize ? $selectedPrize : null;
            if ($targetOutcome === PromotionOutcomeType::Prize && ! $newPrize) {
                throw new DomainException('Der neue Gewinn ist ungueltig.');
            }

            $availableBeforeNew = $newPrize?->awarded_count ?? 0;
            if ($oldPrize && $newPrize && (int) $oldPrize->getKey() === (int) $newPrize->getKey()) {
                $availableBeforeNew--;
            }
            if ($newPrize && $availableBeforeNew >= $newPrize->quota) {
                throw new DomainException('Das Kontingent des neuen Gewinns ist erschoepft.');
            }

            if ($oldPrize) {
                $oldPrize->forceFill(['awarded_count' => max(0, $oldPrize->awarded_count - 1)])->save();
            }
            if ($newPrize) {
                $newPrize = PromotionPrize::query()->lockForUpdate()->findOrFail($newPrize->getKey());
                $newPrize->forceFill(['awarded_count' => $newPrize->awarded_count + 1])->save();
            }

            $state = PromotionCampaignState::query()->whereKey($campaign->getKey())->lockForUpdate()->first();
            if ($state && $campaign->quota_exhaustion_policy === PromotionQuotaPolicy::StickerContinue) {
                $newlyExhausted = $newPrize
                    && $newPrize->awarded_count >= $newPrize->quota
                    && (! $oldPrize || (int) $oldPrize->getKey() !== (int) $newPrize->getKey());
                $anyExhausted = PromotionPrize::query()
                    ->where('campaign_id', $campaign->getKey())
                    ->where('is_active', true)
                    ->where('outcome_type', PromotionOutcomeType::Prize->value)
                    ->whereColumn('awarded_count', '>=', 'quota')
                    ->exists();
                if ($newlyExhausted) {
                    $state->forceFill(['sticker_required' => true, 'sticker_acknowledged_at' => null, 'sticker_acknowledged_by' => null])->save();
                } elseif ($state->sticker_required && ! $anyExhausted) {
                    $state->forceFill(['sticker_required' => false])->save();
                }
            }

            $now = now();
            $result->forceFill(['superseded_at' => $now])->save();
            $this->audit->appendV2($campaign, 'spin.superseded', $ticket->participation, $actor, [
                'reason_digest' => hash('sha256', $reason),
            ], $ticket, $turn, $result);

            $replacement = PromotionSpinResult::query()->create([
                'turn_id' => $turn->getKey(), 'ticket_id' => $ticket->getKey(), 'campaign_id' => $campaign->getKey(),
                'prize_id' => $selectedPrize?->getKey(),
                'sequence' => ((int) PromotionSpinResult::query()->where('turn_id', $turn->getKey())->max('sequence')) + 1,
                'outcome_type_snapshot' => $targetOutcome,
                'label_snapshot' => (string) $selectedPrize?->name,
                'fulfillment_mode_snapshot' => $targetOutcome === PromotionOutcomeType::Prize ? $newPrize->getRawOriginal('fulfillment_mode') : null,
                'is_final' => true, 'recorded_by' => $actor->getKey(), 'recorded_at' => $now,
                'corrects_result_id' => $result->getKey(), 'correction_reason' => $reason,
                'mail_status' => PromotionMailStatus::Pending,
            ]);
            $this->audit->appendV2($campaign, $adminOverride ? 'spin.counterbooked' : 'spin.corrected', $ticket->participation, $actor, [
                'reason_digest' => hash('sha256', $reason),
                'outcome_type' => $targetOutcome->value,
            ], $ticket, $turn, $replacement);

            return $replacement->fresh(['turn', 'ticket.participation.user', 'campaign', 'prize', 'recordedBy', 'correctsResult']);
        }, 5);
    }

    private function transitionMail(
        PromotionSpinResult $result,
        PromotionMailStatus $status,
        ?string $error = null,
        ?User $actor = null,
    ): PromotionSpinResult {
        return DB::transaction(function () use ($result, $status, $error, $actor): PromotionSpinResult {
            if ($actor) {
                $actor = User::query()->lockForUpdate()->findOrFail($actor->getKey());
                if (! $this->isGlobalAdmin($actor)) {
                    throw new DomainException('Nur ein aktiver Volladmin darf eine fehlgeschlagene Ergebnismail erneut anfordern.');
                }
            }
            $result = PromotionSpinResult::query()->lockForUpdate()->findOrFail($result->getKey());
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($result->campaign_id);
            $ticket = PromotionTicket::query()->lockForUpdate()->findOrFail($result->ticket_id);
            $turn = PromotionTurn::query()->lockForUpdate()->findOrFail($result->turn_id);
            $this->assertAuditIntegrity($campaign);
            if (! $result->is_final || $result->superseded_at !== null) {
                throw new DomainException('Fuer dieses Ergebnis darf kein Mailstatus gesetzt werden.');
            }
            $current = $result->mail_status;
            if (in_array($status, [PromotionMailStatus::Sent, PromotionMailStatus::Failed], true)
                && $current !== PromotionMailStatus::Pending) {
                throw new DomainException('Nur eine ausstehende Ergebnismail kann als versendet oder fehlgeschlagen markiert werden.');
            }
            if ($status === PromotionMailStatus::Pending
                && ($current !== PromotionMailStatus::Failed || ! $actor)) {
                throw new DomainException('Nur eine fehlgeschlagene Ergebnismail kann durch einen Volladmin erneut angefordert werden.');
            }

            $now = now();
            $result->forceFill([
                'mail_status' => $status,
                'mail_sent_at' => $status === PromotionMailStatus::Sent ? $now : null,
                'mail_failed_at' => $status === PromotionMailStatus::Failed ? $now : null,
                'mail_last_attempted_at' => $now,
                'mail_error_digest' => $status === PromotionMailStatus::Failed ? hash('sha256', (string) $error) : null,
            ])->save();
            $this->audit->appendV2($campaign, 'spin.mail_'.$status->value, $ticket->participation, $actor, [
                'mail_status' => $status->value,
            ], $ticket, $turn, $result);

            return $result->fresh(['ticket.participation.user', 'campaign', 'prize']);
        }, 5);
    }

    private function ticketFromScanInput(string $input): PromotionTicket
    {
        $input = trim($input);
        if (str_starts_with($input, PromotionTicketQrSigner::VERSION.':')) {
            return $this->signer->parse($input);
        }

        $publicId = mb_strtoupper($input);
        if (! ParticipationId::isValid($publicId)) {
            throw new DomainException('Die Teilnahme-ID ist ungueltig.');
        }
        $ticket = PromotionTicket::query()->whereHas('participation', fn ($query) => $query->where('public_id', $publicId))->first();
        if (! $ticket) {
            throw new DomainException('Zu dieser Teilnahme-ID wurde kein Ticket gefunden.');
        }

        return $ticket;
    }

    private function assertPrizeSelection(PromotionCampaign $campaign, ?PromotionPrize $prize, PromotionOutcomeType $outcome): void
    {
        if (! $prize || ! $prize->is_active || (int) $prize->campaign_id !== (int) $campaign->getKey()) {
            throw new DomainException('Das ausgewaehlte Radfeld ist nicht aktiv oder gehoert zu einer anderen Kampagne.');
        }
        $configured = $prize->outcome_type instanceof PromotionOutcomeType
            ? $prize->outcome_type
            : $this->outcome((string) $prize->getRawOriginal('outcome_type'));
        if ($configured !== $outcome) {
            throw new DomainException('Der Ergebnistyp stimmt nicht mit dem konfigurierten Radfeld ueberein.');
        }
    }

    private function assertPublicCampaign(PromotionCampaign $campaign): void
    {
        if ($this->settings->publicCampaignId() !== (int) $campaign->getKey()
            || ! $campaign->is_public || (int) $campaign->public_slot !== 1 || ! $campaign->isOpen()) {
            throw new DomainException('Das Ticket gehoert nicht zur aktuell oeffentlichen Kampagne.');
        }
    }

    private function assertAuditIntegrity(PromotionCampaign $campaign): void
    {
        if (! $this->audit->verify($campaign)) {
            throw new DomainException('Die Kampagnen-, Ergebnis- oder Auditdaten sind ungueltig.');
        }
    }

    private function assertCanRecord(User $actor): void
    {
        if (! ($this->isGlobalAdmin($actor)
            || (method_exists($actor, 'hasRbacPermission') && $actor->hasRbacPermission('promotion.wins.record')))) {
            throw new DomainException('Keine Berechtigung zur Gewinnerfassung.');
        }
    }

    private function assertTurnOwnerOrAdmin(PromotionTurn $turn, User $actor): void
    {
        if (! $this->isGlobalAdmin($actor) && (int) $turn->started_by !== (int) $actor->getKey()) {
            throw new DomainException('Dieser Gluecksrad-Aufruf gehoert zu einem anderen Mitarbeiter.');
        }
    }

    private function isGlobalAdmin(User $actor): bool
    {
        return $actor->role === 'admin' && (bool) $actor->status;
    }

    private function assertEnabled(): void
    {
        if (! $this->settings->isEnabled()) {
            throw new DomainException('Die Promotion-Funktion ist deaktiviert oder unvollstaendig konfiguriert.');
        }
    }

    private function outcome(PromotionOutcomeType|string $outcome): PromotionOutcomeType
    {
        if ($outcome instanceof PromotionOutcomeType) {
            return $outcome;
        }
        try {
            return PromotionOutcomeType::from($outcome);
        } catch (ValueError $exception) {
            throw new DomainException('Unbekannter Gluecksrad-Ergebnistyp.', 0, $exception);
        }
    }
}
