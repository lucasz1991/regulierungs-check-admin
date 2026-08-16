<?php

namespace App\Services\Promotion;

use App\Enums\PromotionOutcomeType;
use App\Enums\PromotionQuotaPolicy;
use App\Enums\PromotionTicketStatus;
use App\Models\Customer;
use App\Models\PromotionCampaign;
use App\Models\PromotionCampaignState;
use App\Models\PromotionParticipation;
use App\Models\PromotionSetting;
use App\Models\PromotionTicket;
use App\Models\PromotionWin;
use App\Models\PromotionWinEvent;
use App\Models\Team;
use App\Models\User;
use App\Support\Promotion\ParticipationId;
use DomainException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PromotionTicketService
{
    public function __construct(
        private readonly PromotionAuditChain $audit,
        private readonly PromotionSettingsService $settings,
    ) {}

    public function publicCampaign(): ?PromotionCampaign
    {
        if (! $this->settings->isEnabled()) {
            return null;
        }

        $campaignId = $this->settings->publicCampaignId();
        if ($campaignId === null) {
            return null;
        }

        $campaign = PromotionCampaign::query()->find($campaignId);
        $publicCount = PromotionCampaign::query()->where('is_public', true)->where('public_slot', 1)->count();

        return $campaign
            && $publicCount === 1
            && $campaign->is_public
            && (int) $campaign->public_slot === 1
            && $campaign->isOpen()
                ? $campaign
                : null;
    }

    public function ticketFor(User $user, ?PromotionCampaign $campaign = null): ?PromotionTicket
    {
        $campaign ??= $this->publicCampaign();
        if (! $campaign) {
            return null;
        }

        return PromotionTicket::query()
            ->with(['participation', 'campaign', 'latestTurn.latestResult', 'effectiveResult.prize'])
            ->where('campaign_id', $campaign->getKey())
            ->where('user_id', $user->getKey())
            ->first();
    }

    public function ensureTicket(User $user, ?PromotionCampaign $campaign = null): PromotionTicket
    {
        $campaign ??= $this->publicCampaign();
        if (! $campaign) {
            throw new DomainException('Derzeit ist kein oeffentliches Gluecksrad aktiv.');
        }

        return DB::transaction(function () use ($user, $campaign): PromotionTicket {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $this->assertParticipant($user);
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($campaign->getKey());
            $this->assertSelectedCampaign($campaign);
            $prizes = $campaign->prizes()->orderBy('id')->lockForUpdate()->get();
            $this->assertAuditIntegrity($campaign);

            $runtimeState = PromotionCampaignState::query()->whereKey($campaign->getKey())->lockForUpdate()->first();
            if (! $runtimeState) {
                $stickerRequired = $campaign->quota_exhaustion_policy === PromotionQuotaPolicy::StickerContinue
                    && $prizes->contains(static fn ($prize): bool => $prize->is_active
                        && $prize->outcome_type === PromotionOutcomeType::Prize
                        && $prize->awarded_count >= $prize->quota);
                PromotionCampaignState::query()->create([
                    'campaign_id' => $campaign->getKey(),
                    'active_turn_id' => null,
                    'sticker_required' => $stickerRequired,
                    'sticker_acknowledged_at' => null,
                    'sticker_acknowledged_by' => null,
                ]);
                $this->audit->appendV2($campaign, 'campaign.runtime_initialized', null, $user, [
                    'active_turn_id' => null,
                    'sticker_required' => $stickerRequired,
                ]);
                $this->assertAuditIntegrity($campaign);
            }

            $participation = PromotionParticipation::query()
                ->where('campaign_id', $campaign->getKey())
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();
            if (! $participation) {
                $participation = PromotionParticipation::query()->create([
                    'campaign_id' => $campaign->getKey(),
                    'user_id' => $user->getKey(),
                    'public_id' => ParticipationId::generate((string) $campaign->code),
                ]);
            }

            $ticket = PromotionTicket::query()->where('participation_id', $participation->getKey())->lockForUpdate()->first();
            if ($ticket) {
                return $ticket->fresh(['participation', 'campaign', 'latestTurn.latestResult', 'effectiveResult.prize']);
            }

            $legacyCompleted = PromotionWin::query()
                ->where('participation_id', $participation->getKey())
                ->where('status', '<>', 'cancelled')
                ->exists();
            $now = now();
            $ticket = PromotionTicket::query()->create([
                'participation_id' => $participation->getKey(),
                'campaign_id' => $campaign->getKey(),
                'user_id' => $user->getKey(),
                'status' => $legacyCompleted ? PromotionTicketStatus::Completed : PromotionTicketStatus::Ready,
                'issued_at' => $now,
                'completed_at' => $legacyCompleted ? $now : null,
            ]);

            $this->audit->appendV2(
                $campaign,
                $legacyCompleted ? 'ticket.legacy_completed' : 'ticket.issued',
                $participation,
                $user,
                ['status' => $ticket->status->value],
                $ticket,
            );

            return $ticket->fresh(['participation', 'campaign', 'latestTurn.latestResult', 'effectiveResult.prize']);
        }, 5);
    }

    public function publishCampaign(?PromotionCampaign $campaign, User $actor): ?PromotionCampaign
    {
        $this->assertGlobalAdmin($actor);

        return DB::transaction(function () use ($campaign, $actor): ?PromotionCampaign {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->getKey());
            $this->assertGlobalAdmin($actor);
            $campaignId = $campaign?->getKey();
            PromotionSetting::query()->whereKey(1)->lockForUpdate()->firstOrFail();
            $this->settings->auditKey();
            $currentPublicId = $this->settings->publicCampaignId();
            $campaigns = PromotionCampaign::query()->lockForUpdate()->get();
            $campaignStates = PromotionCampaignState::query()
                ->whereIn('campaign_id', $campaigns->pluck('id'))
                ->orderBy('campaign_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('campaign_id');
            $candidate = $campaignId === null ? null : $campaigns->firstWhere('id', $campaignId);
            if ($campaignId !== null && (! $candidate || ! $candidate->is_active)) {
                throw new DomainException('Nur eine aktivierte Kampagne kann veroeffentlicht werden.');
            }

            foreach ($campaigns as $item) {
                if (PromotionWinEvent::query()->where('campaign_id', $item->getKey())->exists()
                    && ! $this->audit->verify($item)) {
                    throw new DomainException('Eine betroffene Kampagne oder ihre Auditkette wurde veraendert; die Veroeffentlichung wurde abgebrochen.');
                }
            }

            $changed = collect();
            foreach ($campaigns as $item) {
                $shouldBePublic = $campaignId !== null && (int) $item->getKey() === (int) $campaignId;
                if ((bool) $item->is_public !== $shouldBePublic
                    || ($item->public_slot === null ? null : (int) $item->public_slot) !== ($shouldBePublic ? 1 : null)) {
                    $changed->push($item);
                }
            }

            $affectedCampaignIds = $changed->pluck('id')
                ->merge([$currentPublicId, $campaignId])
                ->filter()
                ->map(static fn (mixed $id): int => (int) $id)
                ->unique();
            if ($campaignId !== $currentPublicId
                && $affectedCampaignIds->contains(fn (int $id): bool => $campaignStates->get($id)?->active_turn_id !== null)) {
                throw new DomainException('Die oeffentliche Kampagne kann waehrend einer aktiven Gluecksrad-Drehung nicht gewechselt oder aufgehoben werden.');
            }
            if ($changed->contains(fn (PromotionCampaign $item): bool => $campaignStates->get($item->getKey())?->active_turn_id !== null)) {
                throw new DomainException('Eine Kampagne mit aktiver Gluecksrad-Drehung darf nicht neu veroeffentlicht oder entfernt werden.');
            }

            // Free the unique public slot before assigning it to a campaign
            // with a lower ID than the previously published campaign.
            foreach ($changed as $item) {
                if ($campaignId === null || (int) $item->getKey() !== (int) $campaignId) {
                    $item->forceFill(['is_public' => false, 'public_slot' => null])->save();
                }
            }
            if ($candidate && $changed->contains(fn (PromotionCampaign $item): bool => (int) $item->getKey() === (int) $campaignId)) {
                $candidate->forceFill(['is_public' => true, 'public_slot' => 1])->save();
            }
            $this->settings->setPublicCampaignId($campaignId === null ? null : (int) $campaignId);

            foreach ($changed as $item) {
                $this->audit->appendConfiguration($item, 'campaign.configured', $this->audit->configurationPayload($item), $actor);
            }

            if ($campaignId === null) {
                return null;
            }

            $published = PromotionCampaign::query()->findOrFail($campaignId);

            return $published->fresh(['prizes', 'promotionState']);
        }, 5);
    }

    private function assertParticipant(User $user): void
    {
        if (! $user->isActive()) {
            throw new DomainException('Ein deaktiviertes Konto darf kein Gluecksrad-Ticket erhalten.');
        }
        if (! $user->hasVerifiedEmail()) {
            throw new DomainException('Bitte bestaetigen Sie zuerst Ihre E-Mail-Adresse.');
        }
        if ($user->role !== 'guest') {
            throw new DomainException('Nur ein regulaeres Teilnehmerkonto darf ein Gluecksrad-Ticket erhalten.');
        }

        $team = Team::query()->where('name', 'Benutzer')->lockForUpdate()->first();
        if (! $team || ! Customer::query()->where('user_id', $user->getKey())->exists()) {
            throw new DomainException('Das Teilnehmerkonto ist nicht vollstaendig eingerichtet.');
        }
        if ($user->current_team_id !== null && (int) $user->current_team_id !== (int) $team->getKey()) {
            throw new DomainException('Das Konto ist keinem gueltigen Teilnehmer-Team zugeordnet.');
        }
        if ($user->current_team_id === null) {
            $user->forceFill(['current_team_id' => $team->getKey()])->save();
        }
        $user->teams()->syncWithoutDetaching([$team->getKey() => ['role' => 'guest']]);
    }

    private function assertSelectedCampaign(PromotionCampaign $campaign): void
    {
        try {
            $selectedId = $this->settings->publicCampaignId();
        } catch (RuntimeException $exception) {
            throw new DomainException('Die Promotion-Einstellungen sind ungueltig.', 0, $exception);
        }

        if ($selectedId !== (int) $campaign->getKey()
            || ! $campaign->is_public
            || (int) $campaign->public_slot !== 1
            || ! $campaign->isOpen()
            || PromotionCampaign::query()->where('is_public', true)->where('public_slot', 1)->count() !== 1) {
            throw new DomainException('Diese Kampagne ist nicht oeffentlich aktiv.');
        }
    }

    private function assertAuditIntegrity(PromotionCampaign $campaign): void
    {
        if (! $this->audit->verify($campaign)) {
            throw new DomainException('Die Kampagnen- oder Auditdaten sind ungueltig.');
        }
    }

    private function assertGlobalAdmin(User $actor): void
    {
        if ($actor->role !== 'admin' || ! (bool) $actor->status) {
            throw new DomainException('Nur ein aktiver Volladmin darf eine Kampagne veroeffentlichen.');
        }
    }
}
