<?php

namespace App\Services\Promotion;

use App\Models\PromotionAuditHead;
use App\Models\PromotionCampaign;
use App\Models\PromotionParticipation;
use App\Models\PromotionWin;
use App\Models\PromotionWinEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

final class PromotionAuditChain
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    public function __construct(private readonly PromotionSettingsService $settings)
    {
    }

    public function append(
        PromotionCampaign $campaign,
        string $eventType,
        ?PromotionWin $win,
        ?PromotionParticipation $participation,
        ?User $actor,
        array $payload = [],
        array $context = [],
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

        $payload = $this->canonicalize($payload);
        $material = $this->canonicalJson([
            'campaign_id' => (int) $campaign->getKey(),
            'event_type' => $eventType,
            'occurred_at' => $occurredAt,
            'payload' => $payload,
            'previous_hash' => $head->last_hash,
            'sequence' => $sequence,
            'win_id' => $win?->getKey(),
        ]);
        $eventHash = hash_hmac('sha256', $material, $key);

        $event = PromotionWinEvent::query()->create([
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
        ]);

        $head->forceFill(['last_sequence' => $sequence, 'last_hash' => $eventHash])->save();

        return $event;
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

    private function verifyHashChain(int $campaignId, PromotionAuditHead $head, string $key): bool
    {
        $previousHash = self::GENESIS_HASH;
        $expectedSequence = 1;

        foreach (PromotionWinEvent::query()->where('campaign_id', $campaignId)->orderBy('sequence')->cursor() as $event) {
            if ($event->sequence !== $expectedSequence || ! hash_equals($previousHash, $event->previous_hash)) {
                return false;
            }

            $occurredAt = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                (string) $event->getRawOriginal('occurred_at'),
                'UTC',
            )->format('Y-m-d\TH:i:s\Z');
            $material = $this->canonicalJson([
                'campaign_id' => $campaignId,
                'event_type' => $event->event_type,
                'occurred_at' => $occurredAt,
                'payload' => $event->payload,
                'previous_hash' => $event->previous_hash,
                'sequence' => $event->sequence,
                'win_id' => $event->win_id,
            ]);

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
        $events = PromotionWinEvent::query()->where('campaign_id', $campaignId)->orderBy('sequence')->get();
        $events = $events->whereNotNull('win_id')->values();
        $wins = PromotionWin::query()->where('campaign_id', $campaignId)->get()->keyBy('id');
        $eventWinIds = $events->whereNotNull('win_id')->pluck('win_id')->map(static fn (mixed $id): int => (int) $id)->unique()->sort()->values();
        $storedWinIds = $wins->keys()->map(static fn (mixed $id): int => (int) $id)->sort()->values();

        if ($eventWinIds->all() !== $storedWinIds->all()) {
            return false;
        }

        foreach ($wins as $win) {
            if (! $this->verifyWinState($win, $events->where('win_id', $win->getKey())->values(), $campaignId, $key)) {
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
                ->where('status', '<>', PromotionWin::STATUS_CANCELLED)
                ->count();

            if ((int) $prize->reserved_count !== $actual || (int) $prize->quota < $actual) {
                return false;
            }

            if (in_array(mb_strtoupper((string) $prize->code), ['AMAZON20', 'AMAZON5'], true)
                && (string) $prize->fulfillment_mode !== \App\Models\PromotionPrize::FULFILLMENT_EXTERNAL) {
                return false;
            }
        }

        return true;
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
        if ($expected !== $this->canonicalize($this->campaignState($campaign))) {
            return false;
        }

        $configurationEvents = PromotionWinEvent::query()
            ->where('campaign_id', $campaign->getKey())
            ->where('event_type', 'prize.configured')
            ->where('sequence', '>', $campaignEvent->sequence)
            ->orderBy('sequence')
            ->get();

        $expectedPrizes = collect((array) data_get($campaignEvent->payload, 'prizes', []))
            ->filter(static fn (mixed $state): bool => is_array($state) && (int) ($state['id'] ?? 0) > 0)
            ->mapWithKeys(fn (array $state): array => [(int) $state['id'] => $this->canonicalize($state)]);

        foreach ($configurationEvents as $event) {
            $state = (array) data_get($event->payload, 'prize', []);
            $prizeId = (int) ($state['id'] ?? 0);

            if ($prizeId <= 0) {
                return false;
            }

            $expectedPrizes->put($prizeId, $this->canonicalize($state));
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

            // Reservation changes are already bound to immutable win
            // events and reconciled separately, so compare config only.
            unset($expected['reserved_count'], $current['reserved_count']);
            if ($expected !== $current) {
                return false;
            }
        }

        if ($campaign->is_active && $expectedPrizes->isEmpty()) {
            return false;
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
        if ((string) $win->getRawOriginal('status') !== $expectedStatus
            || ($expectedPrizeId !== null && (int) $win->prize_id !== $expectedPrizeId)
            || ($win->participation_id === null ? null : (int) $win->participation_id) !== $expectedParticipationId) {
            return false;
        }

        foreach ([
            'bound' => 'bound_at',
            'confirmed' => 'confirmed_at',
            'disputed' => 'disputed_at',
            'fulfilled' => 'fulfilled_at',
            'expired' => 'expired_at',
            'cancelled' => 'cancelled_at',
        ] as $eventStatus => $column) {
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
