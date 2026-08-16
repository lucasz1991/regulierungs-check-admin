<?php

namespace App\Services\Promotion;

use App\Models\PromotionAuditHead;
use App\Models\PromotionCampaign;
use App\Models\PromotionCampaignState;
use App\Models\PromotionParticipation;
use App\Models\PromotionSpinResult;
use App\Models\PromotionTicket;
use App\Models\PromotionTurn;
use App\Models\PromotionWin;
use App\Models\PromotionWinEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class PromotionAuditChain
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    public function __construct(private readonly PromotionSettingsService $settings) {}

    public function append(
        PromotionCampaign $campaign,
        string $eventType,
        ?PromotionWin $win,
        ?PromotionParticipation $participation,
        ?User $actor,
        array $payload = [],
        array $context = [],
        ?PromotionTicket $ticket = null,
        ?PromotionTurn $turn = null,
        ?PromotionSpinResult $spinResult = null,
    ): PromotionWinEvent {
        $key = $this->key();

        PromotionAuditHead::query()->firstOrCreate(
            ['campaign_id' => $campaign->getKey()],
            ['last_sequence' => 0, 'last_hash' => self::GENESIS_HASH],
        );

        $head = PromotionAuditHead::query()
            ->whereKey($campaign->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if (! $this->verifyHashChain((int) $campaign->getKey(), $head, $key)) {
            throw new RuntimeException('Die bestehende Promotion-Auditkette ist ungueltig oder wurde mit einem anderen Schluessel signiert.');
        }

        $sequence = $head->last_sequence + 1;
        $occurredAt = Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z');
        $actorRef = $actor ? hash_hmac('sha256', 'user:'.$actor->getKey(), $key) : null;
        $payload = array_merge($payload, array_filter([
            'actor_ref' => $actorRef,
            'participation_id' => $participation?->getKey(),
        ], static fn (mixed $value): bool => $value !== null));

        if ($win) {
            $payload['win_state'] = $this->winState($win, $key);
        }

        if ($participation) {
            $payload['participation_state'] = $this->participationState($participation, $key);
        }

        if ($ticket) {
            $payload['ticket_state'] = $this->ticketState($ticket, $key);
        }

        if ($turn) {
            $payload['turn_state'] = $this->turnState($turn, $key);
        }

        if ($spinResult) {
            $payload['spin_result_state'] = $this->spinResultState($spinResult, $key);
        }

        $campaignState = Schema::hasTable('promotion_campaign_states')
            ? PromotionCampaignState::query()->find($campaign->getKey())
            : null;
        if ($campaignState) {
            $payload['campaign_state'] = $this->campaignRuntimeState($campaignState, $key);
        }

        $payload = $this->canonicalize($payload);

        $materialData = [
            'campaign_id' => (int) $campaign->getKey(),
            'event_type' => $eventType,
            'occurred_at' => $occurredAt,
            'payload' => $payload,
            'previous_hash' => $head->last_hash,
            'sequence' => $sequence,
            'win_id' => $win?->getKey(),
        ];
        if ($ticket || $turn || $spinResult) {
            $materialData += [
                'ticket_id' => $ticket?->getKey(),
                'turn_id' => $turn?->getKey(),
                'spin_result_id' => $spinResult?->getKey(),
            ];
        }
        $material = $this->canonicalJson($materialData);
        $eventHash = hash_hmac('sha256', $material, $key);

        $eventData = [
            'campaign_id' => $campaign->getKey(),
            'sequence' => $sequence,
            'win_id' => $win?->getKey(),
            'participation_id' => $participation?->getKey(),
            'actor_ref' => $actorRef,
            'event_type' => $eventType,
            'payload' => $payload,
            'previous_hash' => $head->last_hash,
            'event_hash' => $eventHash,
            'occurred_at' => Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $occurredAt, 'UTC'),
        ];
        if (Schema::hasColumn('win_events', 'ticket_id')) {
            $eventData += [
                'ticket_id' => $ticket?->getKey(),
                'turn_id' => $turn?->getKey(),
                'spin_result_id' => $spinResult?->getKey(),
            ];
        }

        $event = PromotionWinEvent::query()->create($eventData);

        $head->forceFill([
            'last_sequence' => $sequence,
            'last_hash' => $eventHash,
        ])->save();

        return $event;
    }

    public function appendV2(
        PromotionCampaign $campaign,
        string $eventType,
        ?PromotionParticipation $participation,
        ?User $actor,
        array $payload = [],
        ?PromotionTicket $ticket = null,
        ?PromotionTurn $turn = null,
        ?PromotionSpinResult $spinResult = null,
    ): PromotionWinEvent {
        return $this->append(
            $campaign,
            $eventType,
            null,
            $participation,
            $actor,
            $payload,
            [],
            $ticket,
            $turn,
            $spinResult,
        );
    }

    public function verify(PromotionCampaign $campaign): bool
    {
        $key = $this->key();
        $head = PromotionAuditHead::query()->find($campaign->getKey());

        if (! $head || ! $this->verifyHashChain((int) $campaign->getKey(), $head, $key)) {
            return false;
        }

        return $this->verifyWinStates((int) $campaign->getKey(), $key)
            && $this->verifyPrizeCounters((int) $campaign->getKey())
            && $this->verifyV2States((int) $campaign->getKey(), $key)
            && $this->verifyLatestConfiguration($campaign, $head->last_sequence > 0);
    }

    public function appendConfiguration(
        PromotionCampaign $campaign,
        string $eventType,
        array $payload,
        User $actor,
        array $context = [],
    ): PromotionWinEvent {
        return $this->append($campaign, $eventType, null, null, $actor, $payload, $context);
    }

    /** @return array{campaign: array<string, mixed>, prizes: array<int, array<string, mixed>>} */
    public function configurationPayload(PromotionCampaign $campaign): array
    {
        $campaign = PromotionCampaign::query()->with('prizes')->findOrFail($campaign->getKey());

        return [
            'campaign' => $this->campaignState($campaign),
            'prizes' => $campaign->prizes->sortBy('id')->map(fn ($prize): array => $this->prizeState($prize))->values()->all(),
        ];
    }

    private function verifyHashChain(int $campaignId, PromotionAuditHead $head, string $key): bool
    {
        $previousHash = self::GENESIS_HASH;
        $expectedSequence = 1;

        foreach (PromotionWinEvent::query()->where('campaign_id', $campaignId)->orderBy('sequence')->cursor() as $event) {
            if ($event->sequence !== $expectedSequence || ! hash_equals($previousHash, $event->previous_hash)) {
                return false;
            }

            // Die DB speichert diesen Wert bewusst als UTC-Sekunde ohne Offset.
            // Ein normaler Eloquent-Datetime-Cast wuerde ihn in der App-Zeitzone
            // interpretieren und dadurch das kanonische Hashmaterial verschieben.
            $occurredAt = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                (string) $event->getRawOriginal('occurred_at'),
                'UTC',
            )->format('Y-m-d\TH:i:s\Z');

            $materialData = [
                'campaign_id' => $campaignId,
                'event_type' => $event->event_type,
                'occurred_at' => $occurredAt,
                'payload' => $event->payload,
                'previous_hash' => $event->previous_hash,
                'sequence' => $event->sequence,
                'win_id' => $event->win_id,
            ];
            if ($event->ticket_id !== null || $event->turn_id !== null || $event->spin_result_id !== null) {
                $materialData += [
                    'ticket_id' => $event->ticket_id,
                    'turn_id' => $event->turn_id,
                    'spin_result_id' => $event->spin_result_id,
                ];
            }
            $material = $this->canonicalJson($materialData);

            if (! hash_equals($event->event_hash, hash_hmac('sha256', $material, $key))) {
                return false;
            }

            $previousHash = $event->event_hash;
            $expectedSequence++;
        }

        return $head->last_sequence === $expectedSequence - 1
            && hash_equals($head->last_hash, $previousHash);
    }

    private function verifyWinStates(int $campaignId, string $key): bool
    {
        $events = PromotionWinEvent::query()
            ->where('campaign_id', $campaignId)
            ->orderBy('sequence')
            ->get();
        $events = $events->whereNotNull('win_id')->values();
        $wins = PromotionWin::query()->where('campaign_id', $campaignId)->get()->keyBy('id');
        $eventWinIds = $events->whereNotNull('win_id')->pluck('win_id')->map(static fn (mixed $id): int => (int) $id)->unique()->sort()->values();
        $storedWinIds = $wins->keys()->map(static fn (mixed $id): int => (int) $id)->sort()->values();

        if ($eventWinIds->all() !== $storedWinIds->all()) {
            return false;
        }

        foreach ($wins as $win) {
            $winEvents = $events->where('win_id', $win->getKey())->values();

            if (! $this->verifyWinState($win, $winEvents, $campaignId, $key)) {
                return false;
            }
        }

        return true;
    }

    private function verifyPrizeCounters(int $campaignId): bool
    {
        foreach (\App\Models\PromotionPrize::query()->where('campaign_id', $campaignId)->cursor() as $prize) {
            $actual = PromotionWin::query()
                ->where('prize_id', $prize->getKey())
                ->where('status', '<>', 'cancelled')
                ->count();

            if ((int) $prize->reserved_count !== $actual || (int) $prize->quota < $actual) {
                return false;
            }

            if (Schema::hasColumn('prizes', 'awarded_count')) {
                $v2Awarded = Schema::hasTable('promotion_spin_results')
                    ? PromotionSpinResult::query()
                        ->where('prize_id', $prize->getKey())
                        ->where('outcome_type_snapshot', 'prize')
                        ->where('is_final', true)
                        ->whereNull('superseded_at')
                        ->count()
                    : 0;
                $expectedAwarded = $actual + $v2Awarded;
                if ((int) $prize->awarded_count !== $expectedAwarded || (int) $prize->quota < $expectedAwarded) {
                    return false;
                }
            }

        }

        return true;
    }

    private function verifyV2States(int $campaignId, string $key): bool
    {
        if (! Schema::hasTable('promotion_tickets') || ! Schema::hasColumn('win_events', 'ticket_id')) {
            return true;
        }

        $events = PromotionWinEvent::query()->where('campaign_id', $campaignId)->orderBy('sequence')->get();
        $latestTicketEvents = [];
        $latestTurnEvents = [];
        $latestSpinResultEvents = [];
        $latestParticipationEvents = [];
        $latestCampaignStateEvent = null;
        $hasRuntimeEventEvidence = false;

        foreach ($events as $event) {
            if ($event->participation_id !== null) {
                $latestParticipationEvents[(int) $event->participation_id] = $event;
            }

            if ($event->ticket_id !== null) {
                $latestTicketEvents[(int) $event->ticket_id] = $event;
                $hasRuntimeEventEvidence = true;
            }

            if ($event->turn_id !== null) {
                $latestTurnEvents[(int) $event->turn_id] = $event;
                $hasRuntimeEventEvidence = true;
            }

            if ($event->spin_result_id !== null) {
                $latestSpinResultEvents[(int) $event->spin_result_id] = $event;
                $hasRuntimeEventEvidence = true;
            }

            if (is_array(data_get($event->payload, 'campaign_state'))) {
                $latestCampaignStateEvent = $event;
                $hasRuntimeEventEvidence = true;
            }
        }

        $tickets = PromotionTicket::query()->where('campaign_id', $campaignId)->get();
        $participations = PromotionParticipation::query()
            ->whereIn('id', $tickets->pluck('participation_id')->filter()->unique())
            ->get()
            ->keyBy('id');
        $hasTickets = $tickets->isNotEmpty();
        foreach ($tickets as $ticket) {
            $event = $latestTicketEvents[(int) $ticket->getKey()] ?? null;
            $participation = $participations->get((int) $ticket->participation_id);
            $participationEvent = $latestParticipationEvents[(int) $ticket->participation_id] ?? null;
            if (! $event || ! is_array(data_get($event->payload, 'ticket_state'))
                || $this->canonicalize(data_get($event->payload, 'ticket_state')) !== $this->canonicalize($this->ticketState($ticket, $key))
                || ! $participation
                || ! $participationEvent
                || ! is_array(data_get($participationEvent->payload, 'participation_state'))
                || (int) $event->participation_id !== (int) $ticket->participation_id
                || (int) $participationEvent->participation_id !== (int) $participation->getKey()
                || (int) $participation->campaign_id !== $campaignId
                || (int) $ticket->campaign_id !== (int) $participation->campaign_id
                || (int) $ticket->user_id !== (int) $participation->user_id
                || $this->canonicalize(data_get($participationEvent->payload, 'participation_state')) !== $this->canonicalize($this->participationState($participation, $key))) {
                return false;
            }
        }

        $hasTurns = false;
        foreach (PromotionTurn::query()->where('campaign_id', $campaignId)->cursor() as $turn) {
            $hasTurns = true;
            $event = $latestTurnEvents[(int) $turn->getKey()] ?? null;
            if (! $event || ! is_array(data_get($event->payload, 'turn_state'))
                || $this->canonicalize(data_get($event->payload, 'turn_state')) !== $this->canonicalize($this->turnState($turn, $key))) {
                return false;
            }
        }

        $hasSpinResults = false;
        foreach (PromotionSpinResult::query()->where('campaign_id', $campaignId)->cursor() as $result) {
            $hasSpinResults = true;
            $event = $latestSpinResultEvents[(int) $result->getKey()] ?? null;
            if (! $event || ! is_array(data_get($event->payload, 'spin_result_state'))
                || $this->canonicalize(data_get($event->payload, 'spin_result_state')) !== $this->canonicalize($this->spinResultState($result, $key))) {
                return false;
            }
        }

        $state = PromotionCampaignState::query()->find($campaignId);
        $hasRuntimeEvidence = $hasTickets || $hasTurns || $hasSpinResults || $hasRuntimeEventEvidence;
        if (! $state) {
            return ! $hasRuntimeEvidence;
        }

        if (! $latestCampaignStateEvent
            || $this->canonicalize(data_get($latestCampaignStateEvent->payload, 'campaign_state')) !== $this->canonicalize($this->campaignRuntimeState($state, $key))) {
            return false;
        }

        $activeTurns = PromotionTurn::query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'active')
            ->get();
        $activeTickets = PromotionTicket::query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'active')
            ->get();
        if ($activeTurns->count() > 1 || $activeTickets->count() > 1 || $activeTurns->count() !== $activeTickets->count()) {
            return false;
        }

        if ($activeTurns->isEmpty()) {
            return $state->active_turn_id === null;
        }

        $activeTurn = $activeTurns->first();
        $activeTicket = $activeTickets->first();

        return (int) $state->active_turn_id === (int) $activeTurn->getKey()
            && (int) $activeTurn->ticket_id === (int) $activeTicket->getKey()
            && (int) $activeTurn->campaign_id === $campaignId
            && (int) $activeTicket->campaign_id === $campaignId
            && $activeTicket->activated_at !== null
            && $activeTurn->completed_at === null
            && $activeTurn->released_at === null
            && $activeTicket->completed_at === null
            && $activeTicket->cancelled_at === null;
    }

    private function verifyLatestConfiguration(PromotionCampaign $campaign, bool $hasAuditEvents): bool
    {
        $campaign = PromotionCampaign::query()->find($campaign->getKey());

        if (! $campaign) {
            return false;
        }

        $campaignEvent = PromotionWinEvent::query()
            ->where('campaign_id', $campaign->getKey())
            ->where('event_type', 'campaign.configured')
            ->latest('sequence')
            ->first();

        if (! $campaignEvent) {
            return ! $campaign->is_active && ! $hasAuditEvents;
        }

        $expected = $this->canonicalize((array) data_get($campaignEvent->payload, 'campaign', []));
        $currentCampaignState = $this->canonicalize($this->campaignState($campaign));
        if ($expected !== array_intersect_key($currentCampaignState, $expected)) {
            return false;
        }

        $configurationEvents = PromotionWinEvent::query()
            ->where('campaign_id', $campaign->getKey())
            ->whereIn('event_type', ['prize.configured', 'prize.removed'])
            ->where('sequence', '>', $campaignEvent->sequence)
            ->orderBy('sequence')
            ->get();

        $expectedPrizes = collect((array) data_get($campaignEvent->payload, 'prizes', []))
            ->filter(static fn (mixed $state): bool => is_array($state) && (int) ($state['id'] ?? 0) > 0)
            ->mapWithKeys(fn (array $state): array => [(int) $state['id'] => $this->canonicalize($state)]);

        foreach ($configurationEvents as $event) {
            $payload = (array) $event->payload;
            $singleState = data_get($payload, 'prize');
            if (is_array($singleState)) {
                $prizeId = (int) ($singleState['id'] ?? 0);

                if ($prizeId <= 0) {
                    return false;
                }

                $expectedPrizes->put($prizeId, $this->canonicalize($singleState));

                continue;
            }

            if (! array_key_exists('prizes', $payload) || ! is_array($payload['prizes'])) {
                return false;
            }

            $replacement = collect($payload['prizes'])
                ->filter(static fn (mixed $state): bool => is_array($state))
                ->mapWithKeys(fn (array $state): array => [(int) ($state['id'] ?? 0) => $this->canonicalize($state)]);

            if ($replacement->count() !== count($payload['prizes'])
                || $replacement->keys()->contains(static fn (mixed $id): bool => (int) $id <= 0)) {
                return false;
            }

            $expectedPrizes = $replacement;
        }

        $currentPrizes = \App\Models\PromotionPrize::query()
            ->where('campaign_id', $campaign->getKey())
            ->get()
            ->keyBy(fn (\App\Models\PromotionPrize $prize): int => (int) $prize->getKey());

        if ($expectedPrizes->keys()->sort()->values()->all() !== $currentPrizes->keys()->sort()->values()->all()) {
            return false;
        }

        foreach ($currentPrizes as $prizeId => $prize) {
            $expected = $expectedPrizes->get((int) $prizeId);
            $current = $this->canonicalize($this->prizeState($prize));
            unset($expected['reserved_count'], $current['reserved_count'], $expected['awarded_count'], $current['awarded_count']);
            $current = array_intersect_key($current, $expected);

            if ($expected !== $current) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function campaignState(PromotionCampaign $campaign): array
    {
        return [
            'id' => (int) $campaign->id,
            'code' => (string) $campaign->code,
            'name_digest' => hash('sha256', (string) $campaign->name),
            'starts_at' => $campaign->getRawOriginal('starts_at'),
            'ends_at' => $campaign->getRawOriginal('ends_at'),
            'is_active' => (bool) $campaign->is_active,
            'is_public' => (bool) ($campaign->is_public ?? false),
            'public_slot' => $campaign->public_slot === null ? null : (int) $campaign->public_slot,
            'quota_exhaustion_policy' => (string) ($campaign->getRawOriginal('quota_exhaustion_policy') ?? 'block'),
            'landing_headline_digest' => hash('sha256', (string) ($campaign->landing_headline ?? '')),
            'landing_text_digest' => hash('sha256', (string) ($campaign->landing_text ?? '')),
            'rules_text_digest' => hash('sha256', (string) ($campaign->rules_text ?? '')),
        ];
    }

    /** @return array<string, mixed> */
    private function prizeState(\App\Models\PromotionPrize $prize): array
    {
        return [
            'id' => (int) $prize->id,
            'code' => (string) $prize->code,
            'name_digest' => hash('sha256', (string) $prize->name),
            'fulfillment_mode' => (string) $prize->getRawOriginal('fulfillment_mode'),
            'quota' => (int) $prize->quota,
            'reserved_count' => (int) $prize->reserved_count,
            'awarded_count' => (int) ($prize->awarded_count ?? 0),
            'outcome_type' => (string) ($prize->getRawOriginal('outcome_type') ?? 'prize'),
            'is_active' => (bool) $prize->is_active,
            'sort_order' => (int) $prize->sort_order,
            'configuration_digest' => hash('sha256', json_encode($prize->configuration, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)),
        ];
    }

    /** @param Collection<int, PromotionWinEvent> $events */
    private function verifyWinState(PromotionWin $win, Collection $events, int $campaignId, string $key): bool
    {
        if ($events->isEmpty() || (int) $win->campaign_id !== $campaignId) {
            return false;
        }

        $previousStatus = null;
        $latestState = null;
        $legacyParticipationId = null;
        $legacyPrizeId = null;
        $seen = [];
        $immutableState = null;
        $claimKeyDigest = null;
        $participationState = null;
        $previousTimestamps = null;

        foreach ($events as $event) {
            $expectedStatus = $this->statusForEvent((string) $event->event_type);

            if ($expectedStatus === null
                || (int) $event->campaign_id !== $campaignId
                || ! $this->transitionIsValid($previousStatus, $expectedStatus)) {
                return false;
            }

            $payload = (array) $event->payload;

            if (($payload['status'] ?? null) !== $expectedStatus) {
                return false;
            }

            if ($event->event_type === 'win.issued') {
                $legacyPrizeId = isset($payload['prize_id']) ? (int) $payload['prize_id'] : null;
            }

            if ($event->participation_id !== null) {
                $eventParticipationId = (int) $event->participation_id;
                $legacyParticipationId ??= $eventParticipationId;

                if ($legacyParticipationId !== $eventParticipationId) {
                    return false;
                }
            }

            if (array_key_exists('win_state', $payload)) {
                if (! is_array($payload['win_state'])) {
                    return false;
                }

                $latestState = $this->canonicalize($payload['win_state']);

                if (! $this->timestampContinuityIsValid(
                    $previousStatus,
                    $expectedStatus,
                    $previousTimestamps,
                    $latestState,
                )) {
                    return false;
                }
                $previousTimestamps = $this->timestampState($latestState);

                if (! $this->claimKeyContinuity(
                    $previousStatus,
                    $expectedStatus,
                    $latestState['claim_key_digest'] ?? null,
                    $claimKeyDigest,
                )) {
                    return false;
                }

                $eventImmutableState = $this->immutableWinState($latestState);
                if ($immutableState !== null && $eventImmutableState !== $immutableState) {
                    return false;
                }
                $immutableState ??= $eventImmutableState;

                if (($latestState['status'] ?? null) !== $expectedStatus
                    || (int) ($latestState['campaign_id'] ?? 0) !== $campaignId
                    || (int) ($latestState['prize_id'] ?? 0) !== (int) $win->prize_id
                    || ($latestState['participation_id'] ?? null) !== ($event->participation_id === null ? null : (int) $event->participation_id)) {
                    return false;
                }
            }

            if ($event->participation_id !== null) {
                if (! isset($payload['participation_state']) || ! is_array($payload['participation_state'])) {
                    return false;
                }

                $eventParticipationState = $this->canonicalize($payload['participation_state']);
                $participationState ??= $eventParticipationState;

                if ($participationState !== $eventParticipationState
                    || (int) ($eventParticipationState['id'] ?? 0) !== (int) $event->participation_id
                    || (int) ($eventParticipationState['campaign_id'] ?? 0) !== $campaignId) {
                    return false;
                }
            } elseif (array_key_exists('participation_state', $payload)) {
                return false;
            }

            $seen[$expectedStatus] = true;
            $previousStatus = $expectedStatus;
        }

        if ($latestState !== null) {
            return $latestState === $this->canonicalize($this->winState($win, $key))
                && $this->verifyParticipationState($win, $participationState, $key);
        }

        return $this->verifyLegacyWinState($win, (string) $previousStatus, $legacyPrizeId, $legacyParticipationId, $seen)
            && $this->verifyParticipationState($win, $participationState, $key);
    }

    private function claimKeyContinuity(
        ?string $previousStatus,
        string $status,
        mixed $eventDigest,
        ?string &$boundDigest,
    ): bool {
        $digest = is_string($eventDigest) && preg_match('/\A[a-f0-9]{64}\z/', $eventDigest) === 1
            ? $eventDigest
            : null;

        return match ($status) {
            'issued', 'expired' => $eventDigest === null,
            'bound' => $previousStatus === 'issued'
                && $digest !== null
                && (($boundDigest = $digest) !== null),
            'confirmed', 'disputed', 'fulfilled' => $boundDigest !== null
                && $digest !== null
                && hash_equals($boundDigest, $digest),
            'cancelled' => $eventDigest === null,
            default => false,
        };
    }

    /** @param array<string, mixed>|null $expected */
    private function verifyParticipationState(
        PromotionWin $win,
        ?array $expected,
        string $key,
    ): bool {
        if ($win->participation_id === null) {
            return $expected === null;
        }

        $participation = PromotionParticipation::query()->find($win->participation_id);

        return $participation !== null
            && $expected !== null
            && (int) $participation->campaign_id === (int) $win->campaign_id
            && $this->canonicalize($expected) === $this->canonicalize($this->participationState($participation, $key));
    }

    /** @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function immutableWinState(array $state): array
    {
        return $this->canonicalize(array_intersect_key($state, array_flip([
            'campaign_id',
            'prize_id',
            'issued_by_ref',
            'prize_name_snapshot_digest',
            'fulfillment_mode_snapshot',
            'token_hash_digest',
            'expires_at',
        ])));
    }

    /**
     * @param  array<string, mixed>|null  $previous
     * @param  array<string, mixed>  $current
     */
    private function timestampContinuityIsValid(
        ?string $previousStatus,
        string $status,
        ?array $previous,
        array $current,
    ): bool {
        $current = $this->timestampState($current);

        if (count($current) !== 7) {
            return false;
        }

        foreach ($current as $value) {
            if ($value !== null && (! is_string($value) || trim($value) === '')) {
                return false;
            }
        }

        if ($previous === null) {
            return $previousStatus === null
                && $status === 'issued'
                && count(array_filter($current, static fn (mixed $value): bool => $value !== null)) === 0;
        }

        $introduced = match ($status) {
            'bound' => ['consumed_at', 'bound_at'],
            'confirmed' => ['confirmed_at'],
            'disputed' => ['disputed_at'],
            'fulfilled' => ['fulfilled_at'],
            'expired' => ['expired_at'],
            'cancelled' => ['cancelled_at'],
            default => [],
        };

        foreach ($current as $column => $value) {
            $previousValue = $previous[$column] ?? null;

            if (in_array($column, $introduced, true)) {
                if ($previousValue !== null || $value === null) {
                    return false;
                }

                continue;
            }

            if ($value !== $previousValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function timestampState(array $state): array
    {
        $columns = [
            'consumed_at',
            'bound_at',
            'confirmed_at',
            'disputed_at',
            'fulfilled_at',
            'expired_at',
            'cancelled_at',
        ];

        return array_intersect_key($state, array_flip($columns));
    }

    /** @param array<string, bool> $seen */
    private function verifyLegacyWinState(
        PromotionWin $win,
        string $expectedStatus,
        ?int $expectedPrizeId,
        ?int $expectedParticipationId,
        array $seen,
    ): bool {
        $status = $win->getRawOriginal('status');

        if ((string) $status !== $expectedStatus
            || ($expectedPrizeId !== null && (int) $win->prize_id !== $expectedPrizeId)
            || ($win->participation_id === null ? null : (int) $win->participation_id) !== $expectedParticipationId) {
            return false;
        }

        $timestampEvents = [
            'bound' => 'bound_at',
            'confirmed' => 'confirmed_at',
            'disputed' => 'disputed_at',
            'fulfilled' => 'fulfilled_at',
            'expired' => 'expired_at',
            'cancelled' => 'cancelled_at',
        ];

        foreach ($timestampEvents as $eventStatus => $column) {
            if (isset($seen[$eventStatus]) !== ($win->getRawOriginal($column) !== null)) {
                return false;
            }
        }

        if (isset($seen['fulfilled']) !== ($win->fulfilled_by !== null)) {
            return false;
        }

        if (isset($seen['cancelled'])) {
            $cancelEvent = $win->events()->where('event_type', 'win.cancelled')->latest('sequence')->first();
            $reason = $win->getRawOriginal('cancellation_reason');

            if (! is_string($reason)
                || ! $cancelEvent
                || ! hash_equals((string) ($cancelEvent->payload['reason_digest'] ?? ''), hash('sha256', $reason))) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function ticketState(PromotionTicket $ticket, string $key): array
    {
        $raw = static fn (string $column): mixed => $ticket->getRawOriginal($column);

        return [
            'id' => (int) $ticket->getKey(),
            'participation_id' => (int) $ticket->participation_id,
            'campaign_id' => (int) $ticket->campaign_id,
            'user_ref' => $ticket->user_id === null ? null : hash_hmac('sha256', 'participant-user:'.$ticket->user_id, $key),
            'status' => (string) $raw('status'),
            'issued_at' => $raw('issued_at') === null ? null : (string) $raw('issued_at'),
            'activated_at' => $raw('activated_at') === null ? null : (string) $raw('activated_at'),
            'completed_at' => $raw('completed_at') === null ? null : (string) $raw('completed_at'),
            'cancelled_at' => $raw('cancelled_at') === null ? null : (string) $raw('cancelled_at'),
        ];
    }

    /** @return array<string, mixed> */
    private function turnState(PromotionTurn $turn, string $key): array
    {
        $raw = static fn (string $column): mixed => $turn->getRawOriginal($column);
        $userRef = static fn (mixed $id): ?string => $id === null ? null : hash_hmac('sha256', 'user:'.$id, $key);

        return [
            'id' => (int) $turn->getKey(),
            'ticket_id' => (int) $turn->ticket_id,
            'campaign_id' => (int) $turn->campaign_id,
            'started_by_ref' => $userRef($turn->started_by),
            'completed_by_ref' => $userRef($turn->completed_by),
            'released_by_ref' => $userRef($turn->released_by),
            'status' => (string) $raw('status'),
            'started_at' => $raw('started_at') === null ? null : (string) $raw('started_at'),
            'completed_at' => $raw('completed_at') === null ? null : (string) $raw('completed_at'),
            'released_at' => $raw('released_at') === null ? null : (string) $raw('released_at'),
            'release_reason_digest' => $raw('release_reason') === null ? null : hash('sha256', (string) $raw('release_reason')),
        ];
    }

    /** @return array<string, mixed> */
    private function spinResultState(PromotionSpinResult $result, string $key): array
    {
        $raw = static fn (string $column): mixed => $result->getRawOriginal($column);
        $userRef = static fn (mixed $id): ?string => $id === null ? null : hash_hmac('sha256', 'user:'.$id, $key);
        $digest = static fn (mixed $value): ?string => $value === null || $value === '' ? null : hash('sha256', (string) $value);

        return [
            'id' => (int) $result->getKey(),
            'turn_id' => (int) $result->turn_id,
            'ticket_id' => (int) $result->ticket_id,
            'campaign_id' => (int) $result->campaign_id,
            'prize_id' => $result->prize_id === null ? null : (int) $result->prize_id,
            'sequence' => (int) $result->sequence,
            'outcome_type_snapshot' => (string) $raw('outcome_type_snapshot'),
            'label_snapshot_digest' => $digest($raw('label_snapshot')),
            'fulfillment_mode_snapshot' => $raw('fulfillment_mode_snapshot') === null ? null : (string) $raw('fulfillment_mode_snapshot'),
            'is_final' => (bool) $result->is_final,
            'recorded_by_ref' => $userRef($result->recorded_by),
            'recorded_at' => $raw('recorded_at') === null ? null : (string) $raw('recorded_at'),
            'corrects_result_id' => $result->corrects_result_id === null ? null : (int) $result->corrects_result_id,
            'superseded_at' => $raw('superseded_at') === null ? null : (string) $raw('superseded_at'),
            'correction_reason_digest' => $digest($raw('correction_reason')),
            'mail_status' => (string) $raw('mail_status'),
            'mail_sent_at' => $raw('mail_sent_at') === null ? null : (string) $raw('mail_sent_at'),
            'mail_failed_at' => $raw('mail_failed_at') === null ? null : (string) $raw('mail_failed_at'),
            'mail_last_attempted_at' => $raw('mail_last_attempted_at') === null ? null : (string) $raw('mail_last_attempted_at'),
            'mail_error_digest' => $raw('mail_error_digest') === null ? null : (string) $raw('mail_error_digest'),
            'fulfilled_by_ref' => $userRef($result->fulfilled_by),
            'fulfilled_at' => $raw('fulfilled_at') === null ? null : (string) $raw('fulfilled_at'),
        ];
    }

    /** @return array<string, mixed> */
    private function campaignRuntimeState(PromotionCampaignState $state, string $key): array
    {
        return [
            'campaign_id' => (int) $state->campaign_id,
            'active_turn_id' => $state->active_turn_id === null ? null : (int) $state->active_turn_id,
            'sticker_required' => (bool) $state->sticker_required,
            'sticker_acknowledged_at' => $state->getRawOriginal('sticker_acknowledged_at') === null ? null : (string) $state->getRawOriginal('sticker_acknowledged_at'),
            'sticker_acknowledged_by_ref' => $state->sticker_acknowledged_by === null
                ? null
                : hash_hmac('sha256', 'user:'.$state->sticker_acknowledged_by, $key),
        ];
    }

    /** @return array<string, int|string|null> */
    private function winState(PromotionWin $win, string $key): array
    {
        $raw = static fn (string $column): mixed => $win->getRawOriginal($column);
        $digest = static fn (mixed $value): ?string => $value === null || $value === '' ? null : hash('sha256', (string) $value);
        $userRef = static fn (mixed $id): ?string => $id === null ? null : hash_hmac('sha256', 'user:'.$id, $key);

        return [
            'campaign_id' => (int) $win->campaign_id,
            'prize_id' => (int) $win->prize_id,
            'participation_id' => $win->participation_id === null ? null : (int) $win->participation_id,
            'status' => (string) $raw('status'),
            'issued_by_ref' => $userRef($win->issued_by),
            'fulfilled_by_ref' => $userRef($win->fulfilled_by),
            'prize_name_snapshot_digest' => $digest($raw('prize_name_snapshot')),
            'fulfillment_mode_snapshot' => $raw('fulfillment_mode_snapshot') === null ? null : (string) $raw('fulfillment_mode_snapshot'),
            'token_hash_digest' => $digest($raw('token_hash')),
            'claim_key_digest' => $digest($raw('claim_key')),
            'expires_at' => $raw('expires_at') === null ? null : (string) $raw('expires_at'),
            'consumed_at' => $raw('consumed_at') === null ? null : (string) $raw('consumed_at'),
            'bound_at' => $raw('bound_at') === null ? null : (string) $raw('bound_at'),
            'confirmed_at' => $raw('confirmed_at') === null ? null : (string) $raw('confirmed_at'),
            'disputed_at' => $raw('disputed_at') === null ? null : (string) $raw('disputed_at'),
            'fulfilled_at' => $raw('fulfilled_at') === null ? null : (string) $raw('fulfilled_at'),
            'expired_at' => $raw('expired_at') === null ? null : (string) $raw('expired_at'),
            'cancelled_at' => $raw('cancelled_at') === null ? null : (string) $raw('cancelled_at'),
            'cancellation_reason_digest' => $digest($raw('cancellation_reason')),
        ];
    }

    /** @return array<string, int|string|null> */
    private function participationState(PromotionParticipation $participation, string $key): array
    {
        return [
            'id' => (int) $participation->getKey(),
            'campaign_id' => (int) $participation->campaign_id,
            'public_id_digest' => hash('sha256', (string) $participation->public_id),
            'user_ref' => $participation->user_id === null
                ? null
                : hash_hmac('sha256', 'participant-user:'.$participation->user_id, $key),
        ];
    }

    private function statusForEvent(string $eventType): ?string
    {
        return match ($eventType) {
            'win.issued' => 'issued',
            'win.bound' => 'bound',
            'win.confirmed' => 'confirmed',
            'win.fulfilled' => 'fulfilled',
            'win.disputed' => 'disputed',
            'win.expired' => 'expired',
            'win.cancelled' => 'cancelled',
            default => null,
        };
    }

    private function transitionIsValid(?string $from, string $to): bool
    {
        return match ($to) {
            'issued' => $from === null,
            'bound' => $from === 'issued',
            'confirmed', 'disputed' => $from === 'bound',
            'fulfilled' => $from === 'confirmed',
            'expired' => $from === 'issued',
            'cancelled' => in_array($from, ['issued', 'bound', 'confirmed', 'disputed', 'expired'], true),
            default => false,
        };
    }

    private function key(): string
    {
        return $this->settings->auditKey();
    }

    private function canonicalJson(array $value): string
    {
        return json_encode(
            $this->canonicalize($value),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
