<?php

namespace App\Services\Promotion;

use App\Models\PromotionAuditHead;
use App\Models\PromotionCampaign;
use App\Models\PromotionParticipation;
use App\Models\PromotionPrize;
use App\Models\PromotionWin;
use App\Models\PromotionWinEvent;
use App\Models\User;
use App\Support\Promotion\IssuedPromotionWin;
use App\Support\Promotion\ParticipationId;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class PromotionWinService
{
    public const CANCELLATION_REASONS = [
        'issued_in_error',
        'participant_dispute_upheld',
        'campaign_cancelled',
        'technical_duplicate',
        'expired_reservation_released',
    ];

    private const EMPTY_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    public function __construct(
        private readonly PromotionAuditChain $audit,
        private readonly PromotionSettingsService $settings,
    ) {
    }

    public function inspectToken(string $plainToken): PromotionWin
    {
        $this->ensureEnabled();

        $this->assertTokenFormat($plainToken);

        $win = PromotionWin::query()
            ->with(['campaign', 'prize'])
            ->where('token_hash', PromotionWin::tokenHash($plainToken))
            ->first();

        if (! $win || $win->status !== PromotionWin::STATUS_ISSUED) {
            throw new \DomainException('Der Einmal-Code ist ungueltig, abgelaufen oder bereits verwendet.');
        }

        if ($win->expires_at?->isPast()) {
            $this->expire($win);

            throw new \DomainException('Der Einmal-Code ist ungueltig, abgelaufen oder bereits verwendet.');
        }

        return $win;
    }

    /** @param array{ip_address?: string|null, user_agent?: string|null} $context */
    public function issue(PromotionCampaign $campaign, PromotionPrize $prize, User $issuedBy, array $context = []): IssuedPromotionWin
    {
        $this->ensureEnabled();

        if (! $issuedBy->isActive() || ! $issuedBy->hasRbacPermission('promotion.wins.record')) {
            throw new AuthorizationException('Keine Berechtigung zur Gewinnerfassung.');
        }

        $plainToken = $this->newPlainToken();

        return DB::transaction(function () use ($campaign, $prize, $issuedBy, $context, $plainToken): IssuedPromotionWin {
            $issuedBy = User::query()->lockForUpdate()->findOrFail($issuedBy->getKey());
            if (! $issuedBy->isActive() || ! $issuedBy->hasRbacPermission('promotion.wins.record')) {
                throw new AuthorizationException('Keine Berechtigung zur Gewinnerfassung.');
            }
            $lockedCampaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $lockedPrize = PromotionPrize::query()->lockForUpdate()->findOrFail($prize->id);

            if (! $lockedCampaign->isCurrentlyActive() || (int) $lockedPrize->campaign_id !== (int) $lockedCampaign->id) {
                throw ValidationException::withMessages(['prize' => 'Kampagne oder Preis ist nicht aktiv.']);
            }

            if (! $this->audit->verify($lockedCampaign)) {
                throw ValidationException::withMessages(['prize' => 'Die Promotion-Konfiguration oder Auditkette ist ungueltig; es wird kein Gewinn ausgegeben.']);
            }

            if (! $lockedPrize->hasQuota()) {
                throw ValidationException::withMessages(['prize' => 'Das Kontingent dieses Gewinns ist erschoepft.']);
            }

            $prizeCounters = ['reserved_count' => $lockedPrize->reserved_count + 1];
            if (Schema::hasColumn('prizes', 'awarded_count')) {
                $prizeCounters['awarded_count'] = $lockedPrize->awarded_count + 1;
            }
            $lockedPrize->forceFill($prizeCounters)->save();

            $win = PromotionWin::create([
                'campaign_id' => $lockedCampaign->id,
                'prize_id' => $lockedPrize->id,
                'participation_id' => null,
                'token_hash' => PromotionWin::tokenHash($plainToken),
                'status' => PromotionWin::STATUS_ISSUED,
                'issued_by' => $issuedBy->id,
                'prize_name_snapshot' => $lockedPrize->name,
                'fulfillment_mode_snapshot' => $lockedPrize->fulfillment_mode,
                'expires_at' => now()->addMinutes($this->settings->qrTtlMinutes()),
            ]);

            $this->appendEvent($win, 'win.issued', [
                'campaign_id' => $win->campaign_id,
                'prize_id' => $win->prize_id,
                'fulfillment_mode' => $win->fulfillment_mode_snapshot,
                'status' => $win->status,
            ], $issuedBy, $context);

            return new IssuedPromotionWin($win->fresh(), $plainToken);
        }, 3);
    }

    /** @param array{ip_address?: string|null, user_agent?: string|null} $context */
    public function bindToken(string $plainToken, User $user, array $context = []): PromotionParticipation
    {
        $this->ensureEnabled();

        if (! $user->isActive()) {
            throw ValidationException::withMessages(['token' => 'Dieser Einmal-Code ist ungueltig.']);
        }
        $this->assertTokenFormat($plainToken);

        $result = DB::transaction(function () use ($plainToken, $user, $context): PromotionParticipation|PromotionWin {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            if (! $user->isActive()) {
                throw ValidationException::withMessages(['token' => 'Dieser Einmal-Code ist ungueltig.']);
            }
            $candidate = PromotionWin::query()
                ->select(['id', 'campaign_id'])
                ->where('token_hash', PromotionWin::tokenHash($plainToken))
                ->first();

            if (! $candidate) {
                throw ValidationException::withMessages(['token' => 'Dieser Einmal-Code wurde bereits verwendet oder ist ungueltig.']);
            }

            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($candidate->campaign_id);
            $win = PromotionWin::query()
                ->whereKey($candidate->getKey())
                ->where('token_hash', PromotionWin::tokenHash($plainToken))
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertAuditIntegrity($campaign, 'token');

            if ($win->status !== PromotionWin::STATUS_ISSUED) {
                throw ValidationException::withMessages(['token' => 'Dieser Einmal-Code wurde bereits verwendet oder ist ungueltig.']);
            }

            if ($win->expires_at?->isPast()) {
                $win->forceFill(['status' => PromotionWin::STATUS_EXPIRED, 'expired_at' => now()])->save();
                $this->appendEvent($win, 'win.expired', ['status' => $win->status], $user, $context);

                return $win;
            }

            if (! $campaign->isOpen()) {
                throw ValidationException::withMessages(['token' => 'Die Kampagne ist nicht aktiv.']);
            }

            $claimKey = hash('sha256', $win->campaign_id.':'.$user->id);

            if (PromotionWin::query()->where('claim_key', $claimKey)->exists()) {
                throw ValidationException::withMessages(['token' => 'Fuer diese Kampagne ist Ihrem Konto bereits ein Gewinn zugeordnet.']);
            }

            $participation = PromotionParticipation::query()
                ->where('campaign_id', $win->campaign_id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $participation ??= PromotionParticipation::create([
                'campaign_id' => $win->campaign_id,
                'user_id' => $user->id,
                'public_id' => ParticipationId::generate($campaign->code),
            ]);

            $win->forceFill([
                'participation_id' => $participation->id,
                'claim_key' => $claimKey,
                'status' => PromotionWin::STATUS_BOUND,
                'consumed_at' => now(),
                'bound_at' => now(),
            ])->save();

            $this->appendEvent($win, 'win.bound', [
                'participation_id' => $participation->id,
                'status' => $win->status,
                'user_ref' => hash_hmac('sha256', 'participant:'.$user->id, $this->settings->auditKey()),
            ], $user, $context);

            return $participation->fresh(['campaign', 'currentWin.prize']);
        }, 3);

        if ($result instanceof PromotionWin) {
            throw ValidationException::withMessages(['token' => 'Der Einmal-Code ist abgelaufen. Die Reservierung muss durch einen Volladmin geprueft werden.']);
        }

        return $result;
    }

    /** @param array{ip_address?: string|null, user_agent?: string|null} $context */
    public function confirmParticipation(PromotionParticipation $participation, User $user, array $context = []): PromotionParticipation
    {
        return $this->participantTransition($participation, $user, PromotionWin::STATUS_CONFIRMED, 'win.confirmed', 'confirmed_at', $context);
    }

    /** @param array{ip_address?: string|null, user_agent?: string|null} $context */
    public function disputeParticipation(PromotionParticipation $participation, User $user, array $context = []): PromotionParticipation
    {
        return $this->participantTransition($participation, $user, PromotionWin::STATUS_DISPUTED, 'win.disputed', 'disputed_at', $context);
    }

    /** @param array{ip_address?: string|null, user_agent?: string|null} $context */
    public function fulfill(PromotionWin $win, User $actor, array $context = []): PromotionWin
    {
        $this->ensureEnabled();

        return DB::transaction(function () use ($win, $actor, $context): PromotionWin {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->getKey());
            $candidate = PromotionWin::query()->select(['id', 'campaign_id'])->findOrFail($win->id);
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($candidate->campaign_id);
            $lockedWin = PromotionWin::query()->with(['prize', 'participation.user'])->lockForUpdate()->findOrFail($candidate->getKey());

            $this->assertAuditIntegrity($campaign, 'win');

            $participant = $lockedWin->participation?->user;

            if ($lockedWin->status === PromotionWin::STATUS_FULFILLED) {
                return $lockedWin;
            }

            if ($lockedWin->status !== PromotionWin::STATUS_CONFIRMED || ! $participant?->hasVerifiedEmail()) {
                throw ValidationException::withMessages(['win' => 'Ausgabe erst nach Bestaetigung und E-Mail-Verifikation.']);
            }

            $mode = $lockedWin->fulfillment_mode_snapshot;
            if (! in_array($mode, [PromotionPrize::FULFILLMENT_EXTERNAL, PromotionPrize::FULFILLMENT_ONSITE], true)) {
                throw ValidationException::withMessages(['win' => 'Die bei der QR-Ausgabe gespeicherte Ausgabemethode ist ungueltig.']);
            }

            $permission = $mode === PromotionPrize::FULFILLMENT_EXTERNAL
                ? 'promotion.fulfillment.external'
                : 'promotion.fulfillment.onsite';

            if (! $actor->isActive()
                || ! $actor->hasRbacPermission($permission)
                || ($mode === PromotionPrize::FULFILLMENT_EXTERNAL && ! $actor->isAdmin())) {
                throw new AuthorizationException('Keine Berechtigung zur Ausgabe dieses Gewinns.');
            }

            $lockedWin->forceFill([
                'status' => PromotionWin::STATUS_FULFILLED,
                'fulfilled_by' => $actor->id,
                'fulfilled_at' => now(),
            ])->save();
            $this->appendEvent($lockedWin, 'win.fulfilled', [
                'fulfillment_mode' => $mode,
                'status' => $lockedWin->status,
            ], $actor, $context);

            return $lockedWin->fresh();
        }, 3);
    }

    /** @param array{ip_address?: string|null, user_agent?: string|null} $context */
    public function cancel(PromotionWin $win, User $actor, string $reason, array $context = []): PromotionWin
    {
        $this->ensureEnabled();

        if (! $actor->isAdmin() || ! $actor->isActive()) {
            throw new AuthorizationException('Nur Volladmins duerfen Gewinnvorgaenge stornieren.');
        }

        $reason = trim($reason);

        if (! in_array($reason, self::CANCELLATION_REASONS, true)) {
            throw ValidationException::withMessages(['reason' => 'Bitte einen gueltigen strukturierten Stornogrund auswaehlen.']);
        }

        return DB::transaction(function () use ($win, $actor, $reason, $context): PromotionWin {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->getKey());
            if (! $actor->isAdmin() || ! $actor->isActive()) {
                throw new AuthorizationException('Nur Volladmins duerfen Gewinnvorgaenge stornieren.');
            }
            $candidate = PromotionWin::query()->select(['id', 'campaign_id'])->findOrFail($win->id);
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($candidate->campaign_id);
            $lockedWin = PromotionWin::query()->lockForUpdate()->findOrFail($candidate->getKey());
            $prize = PromotionPrize::query()->lockForUpdate()->findOrFail($lockedWin->prize_id);

            $this->assertAuditIntegrity($campaign, 'win');

            if ($lockedWin->status === PromotionWin::STATUS_CANCELLED) {
                return $lockedWin;
            }

            if ($lockedWin->status === PromotionWin::STATUS_FULFILLED) {
                throw ValidationException::withMessages(['win' => 'Dieser Vorgang kann nicht storniert werden.']);
            }

            $lockedWin->forceFill([
                'status' => PromotionWin::STATUS_CANCELLED,
                'claim_key' => null,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save();

            $prizeCounters = ['reserved_count' => max(0, $prize->reserved_count - 1)];
            if (Schema::hasColumn('prizes', 'awarded_count')) {
                $prizeCounters['awarded_count'] = max(0, $prize->awarded_count - 1);
            }
            $prize->forceFill($prizeCounters)->save();

            $this->appendEvent($lockedWin, 'win.cancelled', [
                'reason_digest' => hash('sha256', $reason),
                'status' => $lockedWin->status,
            ], $actor, $context);

            return $lockedWin->fresh();
        }, 3);
    }

    /** @param array{ip_address?: string|null, user_agent?: string|null} $context */
    public function expire(PromotionWin $win, ?User $actor = null, array $context = []): PromotionWin
    {
        $this->ensureEnabled();

        return DB::transaction(function () use ($win, $actor, $context): PromotionWin {
            $candidate = PromotionWin::query()->select(['id', 'campaign_id'])->findOrFail($win->id);
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($candidate->campaign_id);
            $lockedWin = PromotionWin::query()->lockForUpdate()->findOrFail($candidate->getKey());

            $this->assertAuditIntegrity($campaign, 'win');

            if ($lockedWin->status === PromotionWin::STATUS_EXPIRED) {
                return $lockedWin;
            }

            if ($lockedWin->status !== PromotionWin::STATUS_ISSUED || ! $lockedWin->expires_at?->isPast()) {
                throw ValidationException::withMessages(['win' => 'Nur abgelaufene, noch nicht zugeordnete Codes koennen verfallen.']);
            }

            $lockedWin->forceFill(['status' => PromotionWin::STATUS_EXPIRED, 'expired_at' => now()])->save();
            $this->appendEvent($lockedWin, 'win.expired', ['status' => $lockedWin->status], $actor, $context);

            return $lockedWin->fresh();
        }, 3);
    }

    /** @return array{valid: bool, checked: int, failed_sequence: int|null, head_matches: bool} */
    public function verifyAuditChain(PromotionCampaign $campaign): array
    {
        $this->ensureEnabled();
        $checked = PromotionWinEvent::query()->where('campaign_id', $campaign->id)->count();
        $valid = $this->audit->verify($campaign);

        return ['valid' => $valid, 'checked' => $checked, 'failed_sequence' => null, 'head_matches' => $valid];
    }

    /** @param array{ip_address?: string|null, user_agent?: string|null} $context */
    private function participantTransition(PromotionParticipation $participation, User $user, string $status, string $eventType, string $dateColumn, array $context): PromotionParticipation
    {
        $this->ensureEnabled();

        if ((int) $participation->user_id !== (int) $user->id) {
            throw new AuthorizationException('Diese Teilnahme gehoert nicht zu diesem Benutzer.');
        }

        return DB::transaction(function () use ($participation, $user, $status, $eventType, $dateColumn, $context): PromotionParticipation {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            if (! $user->isActive()) {
                throw new AuthorizationException('Ein deaktiviertes Konto darf einen Gewinn nicht bestaetigen oder beanstanden.');
            }
            $candidate = PromotionParticipation::query()
                ->select(['id', 'campaign_id'])
                ->findOrFail($participation->getKey());
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($candidate->campaign_id);
            $participation = PromotionParticipation::query()->lockForUpdate()->findOrFail($candidate->getKey());

            if ((int) $participation->user_id !== (int) $user->getKey()) {
                throw new AuthorizationException('Diese Teilnahme gehoert nicht zu diesem Benutzer.');
            }

            $win = PromotionWin::query()
                ->where('participation_id', $participation->id)
                ->latest('id')
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertAuditIntegrity($campaign, 'participation');

            if ($win->status === $status) {
                return $participation->fresh(['campaign', 'currentWin.prize']);
            }

            if ($win->status !== PromotionWin::STATUS_BOUND) {
                throw ValidationException::withMessages(['participation' => 'Dieser Gewinn wurde bereits bearbeitet.']);
            }

            $win->forceFill(['status' => $status, $dateColumn => now()])->save();
            $this->appendEvent($win, $eventType, ['participation_id' => $participation->id, 'status' => $status], $user, $context);

            return $participation->fresh(['campaign', 'currentWin.prize']);
        }, 3);
    }

    /** @param array<string, mixed> $payload
     * @param  array{ip_address?: string|null, user_agent?: string|null}  $context
     */
    private function appendEvent(PromotionWin $win, string $eventType, array $payload, ?User $actor, array $context): PromotionWinEvent
    {
        $win->loadMissing(['campaign', 'participation']);

        return $this->audit->append(
            $win->campaign,
            $eventType,
            $win,
            $win->participation,
            $actor,
            $payload,
            $context,
        );
    }

    /** @param array<string, mixed> $payload */
    private function eventHash(int $campaignId, int $winId, int $sequence, string $eventType, array $payload, string $previousHash, CarbonImmutable $occurredAt): string
    {
        $material = $this->canonicalJson([
            'campaign_id' => $campaignId,
            'event_type' => $eventType,
            'occurred_at' => $occurredAt->utc()->format('Y-m-d\TH:i:s\Z'),
            'payload' => $this->canonicalize($payload),
            'previous_hash' => $previousHash,
            'sequence' => $sequence,
            'win_id' => $winId,
        ]);

        return hash_hmac('sha256', $material, $this->settings->auditKey());
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->canonicalize($item), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }

    private function canonicalJson(array $value): string
    {
        return json_encode(
            $this->canonicalize($value),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    private function ensureEnabled(): void
    {
        if (! $this->settings->isLegacyWinFlowEnabled()) {
            throw new RuntimeException('Promotion ist nicht vollstaendig und sicher konfiguriert.');
        }
    }

    private function assertAuditIntegrity(PromotionCampaign $campaign, string $field): void
    {
        if (! $this->audit->verify($campaign)) {
            throw ValidationException::withMessages([
                $field => 'Die Promotion-Konfiguration, der Gewinnzustand oder die Auditkette ist ungueltig; es wird keine Zustandsaenderung ausgefuehrt.',
            ]);
        }
    }

    private function assertTokenFormat(string $plainToken): void
    {
        if (preg_match('/\A[A-Za-z0-9_-]{43}\z/', $plainToken) !== 1) {
            throw new \DomainException('Ungueltiges Tokenformat.');
        }
    }

    private function newPlainToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
