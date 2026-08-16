<?php

namespace App\Livewire\Admin;

use App\Enums\PromotionOutcomeType;
use App\Enums\PromotionQuotaPolicy;
use App\Livewire\Concerns\RequiresRbacPermission;
use App\Models\PromotionCampaign;
use App\Models\PromotionCampaignState;
use App\Models\PromotionPrize;
use App\Models\PromotionSpinResult;
use App\Models\PromotionTicket;
use App\Models\PromotionTurn;
use App\Models\PromotionWin;
use App\Models\PromotionWinEvent;
use App\Models\User;
use App\Services\Promotion\PromotionAuditChain;
use App\Services\Promotion\PromotionResultMailer;
use App\Services\Promotion\PromotionSettingsService;
use App\Services\Promotion\PromotionTicketService;
use App\Services\Promotion\PromotionTurnService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PromotionAdministration extends Component
{
    use RequiresRbacPermission;

    protected function requiredRbacPermission(): string
    {
        return 'promotion.campaigns.manage';
    }

    public ?int $campaignId = null;

    public string $campaignCode = '';

    public string $campaignName = '';

    public string $campaignLandingHeadline = '';

    public string $campaignLandingText = '';

    public string $campaignRulesText = '';

    public string $campaignQuotaPolicy = 'block';

    public bool $campaignIsActive = false;

    public bool $campaignIsPublic = false;

    public ?string $campaignStartsAt = null;

    public ?string $campaignEndsAt = null;

    public ?int $prizeId = null;

    public string $prizeCode = '';

    public string $prizeName = '';

    public string $prizeOutcomeType = 'prize';

    public string $prizeFulfillmentMode = PromotionPrize::FULFILLMENT_ONSITE;

    public int $prizeQuota = 1;

    public bool $prizeIsActive = true;

    public int $prizeSortOrder = 0;

    public string $historySearch = '';

    public string $historyOutcome = '';

    public ?int $historyStaffId = null;

    public ?string $historyFrom = null;

    public ?string $historyTo = null;

    #[Locked]
    public ?int $counterbookResultId = null;

    public ?int $counterbookPrizeId = null;

    public string $counterbookReason = '';

    public function mount(): void
    {
        $this->authorize('promotion.campaigns.manage');
        $campaign = PromotionCampaign::query()->where('is_public', true)->first()
            ?? PromotionCampaign::query()->latest()->first();

        if ($campaign) {
            $this->editCampaign($campaign->id);
        }
    }

    public function editCampaign(int $campaignId): void
    {
        $this->authorize('promotion.campaigns.manage');
        $campaign = PromotionCampaign::query()->findOrFail($campaignId);
        $this->campaignId = $campaign->id;
        $this->campaignCode = $campaign->code;
        $this->campaignName = $campaign->name;
        $this->campaignLandingHeadline = (string) $campaign->landing_headline;
        $this->campaignLandingText = (string) $campaign->landing_text;
        $this->campaignRulesText = (string) $campaign->rules_text;
        $this->campaignQuotaPolicy = $campaign->quota_exhaustion_policy->value;
        $this->campaignIsActive = (bool) $campaign->is_active;
        $this->campaignIsPublic = (bool) $campaign->is_public;
        $this->campaignStartsAt = optional($campaign->starts_at)->format('Y-m-d\TH:i');
        $this->campaignEndsAt = optional($campaign->ends_at)->format('Y-m-d\TH:i');
        $this->resetPrizeForm();
        $this->resetHistoryFilters();
    }

    public function newCampaign(): void
    {
        $this->authorize('promotion.campaigns.manage');
        $this->reset([
            'campaignId', 'campaignCode', 'campaignName', 'campaignLandingHeadline',
            'campaignLandingText', 'campaignRulesText', 'campaignStartsAt', 'campaignEndsAt',
        ]);
        $this->campaignQuotaPolicy = PromotionQuotaPolicy::Block->value;
        $this->campaignIsActive = false;
        $this->campaignIsPublic = false;
        $this->resetPrizeForm();
    }

    public function saveCampaign(PromotionAuditChain $audit, PromotionTicketService $tickets): void
    {
        $this->authorize('promotion.campaigns.manage');
        $validated = $this->validate([
            'campaignCode' => ['required', 'alpha_dash', 'max:20', Rule::unique('campaigns', 'code')->ignore($this->campaignId)],
            'campaignName' => ['required', 'string', 'max:255'],
            'campaignLandingHeadline' => ['nullable', 'string', 'max:255'],
            'campaignLandingText' => ['nullable', 'string', 'max:5000'],
            'campaignRulesText' => ['nullable', 'string', 'max:10000'],
            'campaignQuotaPolicy' => ['required', Rule::enum(PromotionQuotaPolicy::class)],
            'campaignIsActive' => ['boolean'],
            'campaignIsPublic' => ['boolean'],
            'campaignStartsAt' => ['nullable', 'date'],
            'campaignEndsAt' => ['nullable', 'date', 'after:campaignStartsAt'],
        ]);

        $campaign = DB::transaction(function () use ($validated, $audit, $tickets): PromotionCampaign {
            $campaign = $this->campaignId
                ? PromotionCampaign::query()->lockForUpdate()->findOrFail($this->campaignId)
                : new PromotionCampaign;
            $previousQuotaPolicy = $campaign->exists ? $campaign->quota_exhaustion_policy : null;

            if ($campaign->exists) {
                $campaign->prizes()->orderBy('id')->lockForUpdate()->get();
                $this->assertConfigurationIntegrityBeforeMutation($campaign, $audit);
            }

            $this->assertCampaignTransitionDoesNotStrandTurn(
                $campaign,
                (bool) $validated['campaignIsActive'],
                (bool) $validated['campaignIsPublic'],
            );

            if ($validated['campaignIsPublic'] && ! $this->campaignCanBePublished($campaign)) {
                throw ValidationException::withMessages([
                    'campaignIsPublic' => 'Vor der Veröffentlichung muss mindestens ein aktives finales Radfeld konfiguriert sein.',
                ]);
            }

            $campaign->forceFill([
                'code' => mb_strtoupper(trim($validated['campaignCode'])),
                'name' => trim($validated['campaignName']),
                'landing_headline' => trim($validated['campaignLandingHeadline']),
                'landing_text' => trim($validated['campaignLandingText']),
                'rules_text' => trim($validated['campaignRulesText']),
                'quota_exhaustion_policy' => $validated['campaignQuotaPolicy'],
                'is_active' => (bool) $validated['campaignIsActive'],
                'starts_at' => $validated['campaignStartsAt'],
                'ends_at' => $validated['campaignEndsAt'],
                'created_by' => $campaign->created_by ?: auth()->id(),
            ])->save();

            $this->synchronizeStickerRequirement(
                $campaign,
                $previousQuotaPolicy !== PromotionQuotaPolicy::StickerContinue
                    && $campaign->quota_exhaustion_policy === PromotionQuotaPolicy::StickerContinue,
            );

            $audit->appendConfiguration(
                $campaign,
                'campaign.configured',
                $audit->configurationPayload($campaign->fresh('prizes')),
                $this->actor(),
            );

            if ($validated['campaignIsPublic']) {
                $tickets->publishCampaign($campaign, $this->actor());
            } elseif ($campaign->is_public) {
                $tickets->publishCampaign(null, $this->actor());
            }

            return $campaign->fresh();
        }, 3);

        $this->campaignId = $campaign->id;
        $this->campaignIsPublic = $campaign->is_public;
        activity('promotion')->causedBy($this->actor())->performedOn($campaign)->log('Promotion-Kampagne gespeichert');
        session()->flash('status', 'Kampagne gespeichert.');
    }

    public function editPrize(int $prizeId): void
    {
        $this->authorize('promotion.prizes.manage');
        $prize = PromotionPrize::query()->findOrFail($prizeId);
        abort_unless((int) $prize->campaign_id === (int) $this->campaignId, 422);
        $this->prizeId = $prize->id;
        $this->prizeCode = $prize->code;
        $this->prizeName = $prize->name;
        $this->prizeOutcomeType = $prize->outcome_type->value;
        $mode = $prize->fulfillment_mode;
        $this->prizeFulfillmentMode = $mode instanceof \BackedEnum ? (string) $mode->value : (string) $mode;
        $this->prizeQuota = $prize->quota;
        $this->prizeIsActive = (bool) $prize->is_active;
        $this->prizeSortOrder = $prize->sort_order;
    }

    public function savePrize(PromotionAuditChain $audit): void
    {
        $this->authorize('promotion.prizes.manage');
        abort_unless($this->campaignId !== null, 422, 'Bitte zuerst eine Kampagne speichern.');

        $validated = $this->validate([
            'prizeCode' => ['required', 'alpha_dash', 'max:50', Rule::unique('prizes', 'code')->where('campaign_id', $this->campaignId)->ignore($this->prizeId)],
            'prizeName' => ['required', 'string', 'max:255'],
            'prizeOutcomeType' => ['required', Rule::in([
                PromotionOutcomeType::Prize->value,
                PromotionOutcomeType::NoWin->value,
                PromotionOutcomeType::Retry->value,
            ])],
            'prizeFulfillmentMode' => ['required', Rule::in([PromotionPrize::FULFILLMENT_ONSITE, PromotionPrize::FULFILLMENT_EXTERNAL])],
            'prizeQuota' => ['required', 'integer', 'min:0'],
            'prizeIsActive' => ['boolean'],
            'prizeSortOrder' => ['integer', 'min:0', 'max:10000'],
        ]);

        if ($validated['prizeOutcomeType'] === PromotionOutcomeType::Prize->value && (int) $validated['prizeQuota'] < 1) {
            throw ValidationException::withMessages(['prizeQuota' => 'Ein Gewinnfeld benötigt ein Kontingent von mindestens 1.']);
        }

        $prize = DB::transaction(function () use ($validated, $audit): PromotionPrize {
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($this->campaignId);
            $campaign->prizes()->orderBy('id')->lockForUpdate()->get();
            $this->assertCampaignHasNoActiveTurn($campaign);
            $this->assertConfigurationIntegrityBeforeMutation($campaign, $audit);
            $prize = $this->prizeId
                ? PromotionPrize::query()->lockForUpdate()->findOrFail($this->prizeId)
                : new PromotionPrize(['campaign_id' => $campaign->id, 'reserved_count' => 0, 'awarded_count' => 0]);
            abort_unless(! $prize->exists || (int) $prize->campaign_id === (int) $campaign->id, 422);

            $legacyCount = $prize->exists
                ? PromotionWin::query()->where('prize_id', $prize->id)->where('status', '<>', 'cancelled')->count()
                : 0;
            $v2AwardedCount = $prize->exists
                ? PromotionSpinResult::query()
                    ->where('prize_id', $prize->id)
                    ->where('outcome_type_snapshot', PromotionOutcomeType::Prize->value)
                    ->where('is_final', true)
                    ->whereNull('superseded_at')
                    ->count()
                : 0;
            $reservedCount = $prize->exists ? $legacyCount : 0;
            $awardedCount = $prize->exists ? $legacyCount + $v2AwardedCount : 0;
            $quota = $validated['prizeOutcomeType'] === PromotionOutcomeType::Prize->value
                ? (int) $validated['prizeQuota']
                : 0;
            if ($quota < $awardedCount) {
                throw ValidationException::withMessages([
                    'prizeQuota' => 'Das Kontingent darf nicht unter die bereits vergebenen Gewinne fallen.',
                ]);
            }

            $prize->forceFill([
                'campaign_id' => $campaign->id,
                'code' => mb_strtoupper(trim($validated['prizeCode'])),
                'name' => trim($validated['prizeName']),
                'outcome_type' => $validated['prizeOutcomeType'],
                'fulfillment_mode' => $validated['prizeOutcomeType'] === PromotionOutcomeType::Prize->value
                    ? $validated['prizeFulfillmentMode']
                    : PromotionPrize::FULFILLMENT_ONSITE,
                'quota' => $quota,
                'reserved_count' => $reservedCount,
                'awarded_count' => $awardedCount,
                'is_active' => (bool) $validated['prizeIsActive'],
                'sort_order' => (int) $validated['prizeSortOrder'],
            ])->save();

            $this->synchronizeStickerRequirement(
                $campaign,
                $prize->is_active
                    && $prize->outcome_type === PromotionOutcomeType::Prize
                    && $prize->awarded_count >= $prize->quota,
            );

            $audit->appendConfiguration($campaign, 'campaign.configured', $audit->configurationPayload($campaign->fresh('prizes')), $this->actor());

            return $prize->fresh();
        }, 3);

        activity('promotion')->causedBy($this->actor())->performedOn($prize)->log('Promotion-Radfeld gespeichert');
        $this->resetPrizeForm();
        session()->flash('status', 'Radfeld gespeichert.');
    }

    public function deletePrize(int $prizeId, PromotionAuditChain $audit): void
    {
        $this->authorize('promotion.prizes.manage');

        DB::transaction(function () use ($prizeId, $audit): void {
            $prize = PromotionPrize::query()->lockForUpdate()->findOrFail($prizeId);
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($prize->campaign_id);
            $this->assertCampaignHasNoActiveTurn($campaign);
            $this->assertConfigurationIntegrityBeforeMutation($campaign, $audit);
            if ($prize->spinResults()->exists() || $prize->wins()->exists()) {
                throw ValidationException::withMessages(['prize' => 'Ein bereits verwendetes Radfeld kann nur deaktiviert, nicht gelöscht werden.']);
            }

            $prize->delete();
            $this->synchronizeStickerRequirement($campaign);
            $audit->appendConfiguration($campaign, 'campaign.configured', $audit->configurationPayload($campaign->fresh('prizes')), $this->actor());
        }, 3);

        $this->resetPrizeForm();
        session()->flash('status', 'Radfeld gelöscht.');
    }

    public function prepareCounterbook(int $resultId): void
    {
        $this->authorize('promotion.corrections.manage');
        $result = PromotionSpinResult::query()->findOrFail($resultId);
        $this->counterbookResultId = $result->id;
        $this->counterbookPrizeId = $result->prize_id;
        $this->counterbookReason = '';
    }

    public function cancelCounterbook(): void
    {
        $this->authorize('promotion.corrections.manage');
        $this->reset('counterbookResultId', 'counterbookPrizeId', 'counterbookReason');
    }

    public function counterbook(PromotionTurnService $turns, PromotionResultMailer $mailer): void
    {
        $this->authorize('promotion.corrections.manage');
        $validated = $this->validate([
            'counterbookResultId' => ['required', 'integer', 'exists:promotion_spin_results,id'],
            'counterbookPrizeId' => ['required', 'integer', 'exists:prizes,id'],
            'counterbookReason' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        $result = PromotionSpinResult::query()->findOrFail($validated['counterbookResultId']);
        $field = PromotionPrize::query()->findOrFail($validated['counterbookPrizeId']);
        abort_if((int) $field->campaign_id !== (int) $result->campaign_id, 422);
        $outcome = $field->outcome_type->value;
        $corrected = $turns->counterbookResult($result, $field, $outcome, $this->actor(), trim($validated['counterbookReason']));
        $mailSent = $mailer->send($corrected, true);
        $this->reset('counterbookResultId', 'counterbookPrizeId', 'counterbookReason');
        session()->flash('status', $mailSent
            ? 'Gegenbuchung gespeichert; der vorherige Stand bleibt im Verlauf erhalten und die Korrekturmail wurde versendet.'
            : 'Gegenbuchung gespeichert; die Korrekturmail ist fehlgeschlagen und kann im Verlauf erneut versendet werden.');
    }

    public function fulfill(int $resultId, PromotionTurnService $turns): void
    {
        $result = PromotionSpinResult::query()->findOrFail($resultId);
        $mode = $result->fulfillment_mode_snapshot instanceof \BackedEnum
            ? (string) $result->fulfillment_mode_snapshot->value
            : (string) $result->fulfillment_mode_snapshot;
        $this->authorize($mode === PromotionPrize::FULFILLMENT_EXTERNAL
            ? 'promotion.fulfillment.external'
            : 'promotion.fulfillment.onsite');
        abort_if($mode === PromotionPrize::FULFILLMENT_EXTERNAL && ! $this->actor()->isAdmin(), 403);
        $turns->fulfill($result, $this->actor());
        session()->flash('status', 'Ausgabe einmalig dokumentiert.');
    }

    public function resendMail(int $resultId, PromotionResultMailer $mailer): void
    {
        $this->authorize('promotion.corrections.manage');
        $sent = $mailer->resend(PromotionSpinResult::query()->findOrFail($resultId), $this->actor());
        session()->flash('status', $sent ? 'Ergebnis-E-Mail wurde erneut versendet.' : 'Ergebnis gespeichert; die E-Mail konnte erneut nicht versendet werden.');
    }

    public function resetHistoryFilters(): void
    {
        $this->reset('historySearch', 'historyOutcome', 'historyStaffId', 'historyFrom', 'historyTo');
    }

    public function resetPrizeForm(): void
    {
        $this->authorize('promotion.prizes.manage');
        $this->reset('prizeId', 'prizeCode', 'prizeName');
        $this->prizeOutcomeType = PromotionOutcomeType::Prize->value;
        $this->prizeFulfillmentMode = PromotionPrize::FULFILLMENT_ONSITE;
        $this->prizeQuota = 1;
        $this->prizeIsActive = true;
        $this->prizeSortOrder = 0;
    }

    public function render(PromotionSettingsService $settings)
    {
        $this->authorize('promotion.campaigns.manage');
        $campaign = $this->campaignId
            ? PromotionCampaign::query()->with(['prizes' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'), 'promotionState'])->find($this->campaignId)
            : null;

        $results = PromotionSpinResult::query()
            ->when($this->campaignId, fn ($query) => $query->where('campaign_id', $this->campaignId))
            ->when(trim($this->historySearch) !== '', function ($query): void {
                $search = trim($this->historySearch);
                $query->where(function ($query) use ($search): void {
                    $query->where('label_snapshot', 'like', "%{$search}%")
                        ->orWhereHas('ticket.participation', fn ($participation) => $participation->where('public_id', 'like', "%{$search}%"))
                        ->orWhereHas('ticket.user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($this->historyOutcome !== '', fn ($query) => $query->where('outcome_type_snapshot', $this->historyOutcome))
            ->when($this->historyStaffId, fn ($query) => $query->where('recorded_by', $this->historyStaffId))
            ->when($this->historyFrom, fn ($query) => $query->whereDate('recorded_at', '>=', $this->historyFrom))
            ->when($this->historyTo, fn ($query) => $query->whereDate('recorded_at', '<=', $this->historyTo))
            ->with(['ticket.participation', 'ticket.user:id,name,email', 'campaign:id,name', 'prize:id,name', 'recordedBy:id,name', 'fulfilledBy:id,name'])
            ->latest('recorded_at')
            ->limit(150)
            ->get();

        $baseUrl = rtrim((string) ($settings->get()['redemption_base_url'] ?? ''), '/');
        $turnQuery = PromotionTurn::query()->when($this->campaignId, fn ($query) => $query->where('campaign_id', $this->campaignId));
        $ticketQuery = PromotionTicket::query()->when($this->campaignId, fn ($query) => $query->where('campaign_id', $this->campaignId));

        return view('livewire.admin.promotion-administration', [
            'campaigns' => PromotionCampaign::query()->latest()->get(),
            'selectedCampaign' => $campaign,
            'posterUrl' => $baseUrl !== '' ? $baseUrl.'/gluecksrad' : null,
            'ticketCount' => (clone $ticketQuery)->count(),
            'readyTicketCount' => (clone $ticketQuery)->where('status', 'ready')->count(),
            'todayTurnCount' => (clone $turnQuery)->whereDate('started_at', today())->count(),
            'results' => $results,
            'legacyWins' => PromotionWin::query()
                ->when($this->campaignId, fn ($query) => $query->where('campaign_id', $this->campaignId))
                ->with(['participation.user:id,name,email', 'prize:id,name', 'issuedBy:id,name'])
                ->latest()
                ->limit(30)
                ->get(),
            'staff' => User::query()
                ->whereIn('id', PromotionSpinResult::query()->select('recorded_by')->whereNotNull('recorded_by'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ])->layout('layouts.master');
    }

    private function campaignCanBePublished(PromotionCampaign $campaign): bool
    {
        if (! $this->campaignIsActive) {
            return false;
        }

        if (trim($this->campaignLandingHeadline) === ''
            || trim($this->campaignLandingText) === ''
            || trim($this->campaignRulesText) === '') {
            return false;
        }

        if (! $campaign->exists) {
            return false;
        }

        return $campaign->prizes()
            ->where('is_active', true)
            ->whereIn('outcome_type', [PromotionOutcomeType::Prize->value, PromotionOutcomeType::NoWin->value])
            ->exists();
    }

    private function assertConfigurationIntegrityBeforeMutation(PromotionCampaign $campaign, PromotionAuditChain $audit): void
    {
        if (PromotionWinEvent::query()->where('campaign_id', $campaign->id)->exists() && ! $audit->verify($campaign)) {
            throw new \RuntimeException('Die bestehende Promotion-Konfiguration konnte vor der Änderung nicht verifiziert werden.');
        }
    }

    private function assertCampaignTransitionDoesNotStrandTurn(PromotionCampaign $campaign, bool $willBeActive, bool $willBePublic): void
    {
        $activeStates = PromotionCampaignState::query()->whereNotNull('active_turn_id')->lockForUpdate()->get();
        if ($activeStates->isEmpty()) {
            return;
        }

        $ownStateIsActive = $campaign->exists && $activeStates->contains(
            static fn (PromotionCampaignState $state): bool => (int) $state->campaign_id === (int) $campaign->id,
        );
        if ($ownStateIsActive && (! $willBeActive || ((bool) $campaign->is_public && ! $willBePublic))) {
            throw ValidationException::withMessages([
                'campaignIsPublic' => 'Die Kampagne kann nicht deaktiviert oder unveröffentlicht werden, solange ein Teilnehmer am Glücksrad aktiv ist.',
            ]);
        }

        if ($willBePublic && $activeStates->contains(
            static fn (PromotionCampaignState $state): bool => ! $campaign->exists || (int) $state->campaign_id !== (int) $campaign->id,
        )) {
            throw ValidationException::withMessages([
                'campaignIsPublic' => 'Die öffentliche Kampagne kann nicht gewechselt werden, solange ein Teilnehmer am Glücksrad aktiv ist.',
            ]);
        }
    }

    private function assertCampaignHasNoActiveTurn(PromotionCampaign $campaign): void
    {
        $state = PromotionCampaignState::query()->whereKey($campaign->id)->lockForUpdate()->first();
        if ($state?->active_turn_id !== null) {
            throw ValidationException::withMessages([
                'prize' => 'Radfelder können nicht geändert werden, solange ein Teilnehmer am Glücksrad aktiv ist.',
            ]);
        }
    }

    private function synchronizeStickerRequirement(PromotionCampaign $campaign, bool $forceReacknowledgement = false): void
    {
        $prizes = $campaign->prizes()->orderBy('id')->lockForUpdate()->get();
        $state = PromotionCampaignState::query()->whereKey($campaign->id)->lockForUpdate()->first();
        if (! $state) {
            return;
        }

        $hasActiveExhaustedPrize = $campaign->quota_exhaustion_policy === PromotionQuotaPolicy::StickerContinue
            && $prizes->contains(static fn (PromotionPrize $prize): bool => $prize->is_active
                && $prize->outcome_type === PromotionOutcomeType::Prize
                && $prize->awarded_count >= $prize->quota);

        if ($hasActiveExhaustedPrize) {
            $alreadyAcknowledged = ! $state->sticker_required && $state->sticker_acknowledged_at !== null;
            if ($forceReacknowledgement || (! $state->sticker_required && ! $alreadyAcknowledged)) {
                $state->forceFill([
                    'sticker_required' => true,
                    'sticker_acknowledged_at' => null,
                    'sticker_acknowledged_by' => null,
                ])->save();
            }

            return;
        }

        if ($state->sticker_required || $state->sticker_acknowledged_at !== null || $state->sticker_acknowledged_by !== null) {
            $state->forceFill([
                'sticker_required' => false,
                'sticker_acknowledged_at' => null,
                'sticker_acknowledged_by' => null,
            ])->save();
        }
    }

    private function actor(): User
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && $actor->isAdmin() && $actor->isActive(), 403);

        return $actor;
    }
}
