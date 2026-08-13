<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Models\PromotionCampaign;
use App\Models\PromotionPrize;
use App\Models\PromotionWin;
use App\Models\PromotionWinEvent;
use App\Models\User;
use App\Services\Promotion\PromotionAuditChain;
use App\Services\Promotion\PromotionWinService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public bool $campaignIsActive = false;

    public ?string $campaignStartsAt = null;

    public ?string $campaignEndsAt = null;

    public ?int $prizeId = null;

    public string $prizeCode = '';

    public string $prizeName = '';

    public string $prizeFulfillmentMode = PromotionPrize::FULFILLMENT_ONSITE;

    public int $prizeQuota = 1;

    public bool $prizeIsActive = true;

    public int $prizeSortOrder = 0;

    public ?int $correctionWinId = null;

    public string $correctionReason = '';

    public ?array $auditResult = null;

    public string $winSearch = '';

    public function mount(): void
    {
        $this->authorize('promotion.campaigns.manage');
        $this->campaignId = PromotionCampaign::query()->latest()->value('id');
    }

    public function editCampaign(int $campaignId): void
    {
        $this->authorize('promotion.campaigns.manage');
        $campaign = PromotionCampaign::query()->findOrFail($campaignId);
        $this->campaignId = $campaign->id;
        $this->campaignCode = $campaign->code;
        $this->campaignName = $campaign->name;
        $this->campaignIsActive = $campaign->is_active;
        $this->campaignStartsAt = optional($campaign->starts_at)->format('Y-m-d\TH:i');
        $this->campaignEndsAt = optional($campaign->ends_at)->format('Y-m-d\TH:i');
        $this->resetPrizeForm();
    }

    public function newCampaign(): void
    {
        $this->authorize('promotion.campaigns.manage');
        $this->reset('campaignId', 'campaignCode', 'campaignName', 'campaignStartsAt', 'campaignEndsAt');
        $this->campaignIsActive = false;
        $this->resetPrizeForm();
    }

    public function saveCampaign(PromotionAuditChain $audit): void
    {
        $this->authorize('promotion.campaigns.manage');
        $validated = $this->validate([
            'campaignCode' => ['required', 'alpha_dash', 'max:20', Rule::unique('campaigns', 'code')->ignore($this->campaignId)],
            'campaignName' => ['required', 'string', 'max:255'],
            'campaignIsActive' => ['boolean'],
            'campaignStartsAt' => ['nullable', 'date'],
            'campaignEndsAt' => ['nullable', 'date', 'after:campaignStartsAt'],
        ]);

        $campaign = DB::transaction(function () use ($validated, $audit): PromotionCampaign {
            $existingCampaign = $this->campaignId
                ? PromotionCampaign::query()->lockForUpdate()->findOrFail($this->campaignId)
                : null;

            if ($existingCampaign) {
                $existingCampaign->prizes()->orderBy('id')->lockForUpdate()->get();
                $this->assertConfigurationIntegrityBeforeMutation($existingCampaign, $audit);
            }

            if ($validated['campaignIsActive'] && ! $this->campaignIsReady($existingCampaign)) {
                throw ValidationException::withMessages([
                    'campaignIsActive' => 'Vor der Aktivierung müssen Amazon 20 €, Amazon 5 € und Surprise mit Kontingent eingerichtet sein. Der Ausgabemodus von Surprise muss ausdrücklich gespeichert werden.',
                ]);
            }

            $campaign = $existingCampaign ?? new PromotionCampaign;
            $campaign->forceFill([
                'code' => mb_strtoupper($validated['campaignCode']),
                'name' => trim($validated['campaignName']),
                'is_active' => $validated['campaignIsActive'],
                'starts_at' => $validated['campaignStartsAt'],
                'ends_at' => $validated['campaignEndsAt'],
                'created_by' => $existingCampaign?->created_by ?? auth()->id(),
            ])->save();

            if (! $existingCampaign) {
                $this->createInitialPrizes($campaign);
            }

            if ($validated['campaignIsActive']) {
                $this->ensurePrizeConfigurationEvents($campaign, $audit);
            }

            $audit->appendConfiguration($campaign, 'campaign.configured', [
                'campaign' => $this->campaignAuditState($campaign->fresh()),
                'prizes' => $campaign->prizes()->orderBy('id')->get()->map(fn (PromotionPrize $prize): array => $this->prizeAuditState($prize))->all(),
            ], auth()->user(), $this->accessContext());

            return $campaign;
        }, 3);

        $this->campaignId = $campaign->id;
        activity('promotion')->causedBy(auth()->user())->performedOn($campaign)->log('Promotion-Kampagne gespeichert');
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
        $this->prizeFulfillmentMode = $prize->fulfillment_mode;
        $this->prizeQuota = $prize->quota;
        $this->prizeIsActive = $prize->is_active;
        $this->prizeSortOrder = $prize->sort_order;
    }

    public function savePrize(PromotionAuditChain $audit): void
    {
        $this->authorize('promotion.prizes.manage');
        abort_unless($this->campaignId !== null, 422, 'Bitte zuerst eine Kampagne speichern.');

        $validated = $this->validate([
            'prizeCode' => ['required', 'alpha_dash', 'max:50', Rule::unique('prizes', 'code')->where('campaign_id', $this->campaignId)->ignore($this->prizeId)],
            'prizeName' => ['required', 'string', 'max:255'],
            'prizeFulfillmentMode' => ['required', Rule::in([PromotionPrize::FULFILLMENT_ONSITE, PromotionPrize::FULFILLMENT_EXTERNAL])],
            'prizeQuota' => ['required', 'integer', 'min:1'],
            'prizeIsActive' => ['boolean'],
            'prizeSortOrder' => ['integer', 'min:0'],
        ]);

        $normalizedCode = mb_strtoupper($validated['prizeCode']);
        if (in_array($normalizedCode, ['AMAZON20', 'AMAZON5'], true)
            && $validated['prizeFulfillmentMode'] !== PromotionPrize::FULFILLMENT_EXTERNAL) {
            $this->addError('prizeFulfillmentMode', 'Amazon-Gutscheine dürfen ausschließlich durch Volladmins extern ausgegeben werden.');

            return;
        }

        $prize = DB::transaction(function () use ($validated, $normalizedCode, $audit): PromotionPrize {
            $campaign = PromotionCampaign::query()->lockForUpdate()->findOrFail($this->campaignId);
            $lockedPrizes = $campaign->prizes()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $existing = $this->prizeId ? $lockedPrizes->get($this->prizeId) : null;

            if ($this->prizeId && ! $existing) {
                abort(404);
            }

            $hadAuditBaseline = $this->assertConfigurationIntegrityBeforeMutation($campaign, $audit);

            $actualReservations = 0;

            if ($existing) {
                abort_unless((int) $existing->campaign_id === (int) $this->campaignId, 422);

                $actualReservations = $existing->wins()
                    ->where('status', '<>', PromotionWin::STATUS_CANCELLED)
                    ->count();

                if ((int) $validated['prizeQuota'] < $actualReservations) {
                    throw ValidationException::withMessages([
                        'prizeQuota' => 'Das Kontingent darf nicht unter die bereits reservierte Menge fallen.',
                    ]);
                }

                $prize = $existing;
            } else {
                $prize = new PromotionPrize;
                $prize->campaign_id = $this->campaignId;
                $prize->reserved_count = 0;
            }

            $prize->forceFill([
                'code' => $normalizedCode,
                'name' => trim($validated['prizeName']),
                'fulfillment_mode' => $validated['prizeFulfillmentMode'],
                'quota' => $validated['prizeQuota'],
                'reserved_count' => $actualReservations,
                'is_active' => $validated['prizeIsActive'],
                'sort_order' => $validated['prizeSortOrder'],
                'configuration' => $this->prizeConfiguration($existing, $normalizedCode),
            ])->save();

            if (! $hadAuditBaseline) {
                $audit->appendConfiguration($campaign, 'campaign.configured', [
                    'campaign' => $this->campaignAuditState($campaign->fresh()),
                    'prizes' => $campaign->prizes()->orderBy('id')->get()->map(fn (PromotionPrize $configuredPrize): array => $this->prizeAuditState($configuredPrize))->all(),
                ], auth()->user(), $this->accessContext());
            }

            $audit->appendConfiguration($campaign, 'prize.configured', [
                'prize' => $this->prizeAuditState($prize->fresh()),
            ], auth()->user(), $this->accessContext());

            return $prize;
        }, 3);

        activity('promotion')->causedBy(auth()->user())->performedOn($prize)->log('Promotion-Preis gespeichert');
        $this->resetPrizeForm();
        session()->flash('status', 'Preis gespeichert.');
    }

    public function prepareCorrection(int $winId): void
    {
        $this->authorize('promotion.corrections.manage');
        $this->correctionWinId = PromotionWin::query()->findOrFail($winId)->id;
        $this->correctionReason = '';
    }

    public function cancelWin(PromotionWinService $wins): void
    {
        $this->authorize('promotion.corrections.manage');
        $validated = $this->validate(['correctionReason' => ['required', Rule::in(PromotionWinService::CANCELLATION_REASONS)]]);
        $win = PromotionWin::query()->findOrFail($this->correctionWinId);
        $wins->cancel($win, auth()->user(), trim($validated['correctionReason']), $this->accessContext());
        $this->reset('correctionWinId', 'correctionReason');
        session()->flash('status', 'Der Vorgang wurde storniert und protokolliert.');
    }

    public function verifyAudit(PromotionWinService $wins): void
    {
        $this->authorize('promotion.audit.view');
        abort_unless($this->campaignId !== null, 422);
        $this->auditResult = $wins->verifyAuditChain(PromotionCampaign::query()->findOrFail($this->campaignId));
    }

    public function render()
    {
        $this->authorize('promotion.campaigns.manage');

        $winQuery = PromotionWin::query()
            ->when($this->campaignId, fn ($query) => $query->where('campaign_id', $this->campaignId));
        $search = trim($this->winSearch);
        $recentWins = (clone $winQuery)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('participation', fn ($participation) => $participation->where('public_id', 'like', "%{$search}%"))
                        ->orWhereHas('participation.user', function ($user) use ($search): void {
                            $user->where('email', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->with(['prize', 'participation.user', 'issuedBy'])
            ->latest()
            ->limit(100)
            ->get();
        $statusCounts = (clone $winQuery)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $staffRows = (clone $winQuery)
            ->selectRaw("issued_by, COUNT(*) AS total, SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) AS expired_total, SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_total, SUM(CASE WHEN status = 'disputed' THEN 1 ELSE 0 END) AS disputed_total")
            ->groupBy('issued_by')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
        $staffNames = User::query()->whereKey($staffRows->pluck('issued_by')->filter())->pluck('name', 'id');

        return view('livewire.admin.promotion-administration', [
            'campaigns' => PromotionCampaign::query()->latest()->get(),
            'selectedCampaign' => $this->campaignId ? PromotionCampaign::query()->with(['prizes' => fn ($q) => $q->orderBy('sort_order')])->find($this->campaignId) : null,
            'recentWins' => $recentWins,
            'statusCounts' => $statusCounts,
            'staffRows' => $staffRows,
            'staffNames' => $staffNames,
        ])->layout('layouts.master');
    }

    public function resetPrizeForm(): void
    {
        $this->authorize('promotion.prizes.manage');
        $this->reset('prizeId', 'prizeCode', 'prizeName');
        $this->prizeFulfillmentMode = PromotionPrize::FULFILLMENT_ONSITE;
        $this->prizeQuota = 1;
        $this->prizeIsActive = true;
        $this->prizeSortOrder = 0;
    }

    /** @return array{ip_address: string|null, user_agent: string|null} */
    private function accessContext(): array
    {
        return ['ip_address' => request()->ip(), 'user_agent' => request()->userAgent()];
    }

    private function campaignIsReady(?PromotionCampaign $campaign): bool
    {
        if (! $campaign) {
            return false;
        }

        $prizes = $campaign->prizes()
            ->whereIn('code', ['AMAZON20', 'AMAZON5', 'SURPRISE'])
            ->lockForUpdate()
            ->get()
            ->keyBy('code');

        foreach (['AMAZON20', 'AMAZON5', 'SURPRISE'] as $code) {
            $prize = $prizes->get($code);
            if (! $prize || ! $prize->is_active || $prize->quota < 1) {
                return false;
            }
        }

        foreach (['AMAZON20', 'AMAZON5'] as $code) {
            if ($prizes->get($code)->fulfillment_mode !== PromotionPrize::FULFILLMENT_EXTERNAL) {
                return false;
            }
        }

        return (bool) data_get($prizes->get('SURPRISE')->configuration, 'mode_confirmed', false);
    }

    private function createInitialPrizes(PromotionCampaign $campaign): void
    {
        $campaign->prizes()->createMany([
            ['code' => 'AMAZON20', 'name' => 'Amazon-Gutschein 20 €', 'fulfillment_mode' => PromotionPrize::FULFILLMENT_EXTERNAL, 'quota' => 0, 'is_active' => false, 'sort_order' => 10],
            ['code' => 'AMAZON5', 'name' => 'Amazon-Gutschein 5 €', 'fulfillment_mode' => PromotionPrize::FULFILLMENT_EXTERNAL, 'quota' => 0, 'is_active' => false, 'sort_order' => 20],
            ['code' => 'SURPRISE', 'name' => 'Surprise', 'fulfillment_mode' => PromotionPrize::FULFILLMENT_ONSITE, 'quota' => 0, 'is_active' => false, 'sort_order' => 30, 'configuration' => ['mode_confirmed' => false]],
        ]);
    }

    private function ensurePrizeConfigurationEvents(PromotionCampaign $campaign, PromotionAuditChain $audit): void
    {
        $configuredPrizeIds = PromotionWinEvent::query()
            ->where('campaign_id', $campaign->id)
            ->where('event_type', 'prize.configured')
            ->orderBy('sequence')
            ->get()
            ->map(static fn (PromotionWinEvent $event): int => (int) data_get($event->payload, 'prize.id', 0))
            ->filter()
            ->unique()
            ->all();

        $prizes = $campaign->prizes()->orderBy('id')->lockForUpdate()->get();

        foreach ($prizes as $prize) {
            if (in_array((int) $prize->id, $configuredPrizeIds, true)) {
                continue;
            }

            $audit->appendConfiguration($campaign, 'prize.configured', [
                'prize' => $this->prizeAuditState($prize),
            ], auth()->user(), $this->accessContext());
        }
    }

    private function assertConfigurationIntegrityBeforeMutation(PromotionCampaign $campaign, PromotionAuditChain $audit): bool
    {
        $hasAuditEvents = PromotionWinEvent::query()
            ->where('campaign_id', $campaign->id)
            ->exists();

        if (! $hasAuditEvents) {
            if ($campaign->is_active) {
                throw new \RuntimeException('Eine aktive Kampagne ohne Konfigurations-Audit darf nicht verändert werden.');
            }

            return false;
        }

        if (! $audit->verify($campaign)) {
            throw new \RuntimeException('Die bestehende Promotion-Konfiguration konnte vor der Änderung nicht verifiziert werden.');
        }

        return true;
    }

    /** @return array<string, bool>|null */
    private function prizeConfiguration(?PromotionPrize $existing, string $code): ?array
    {
        $configuration = $existing?->configuration;

        if (mb_strtoupper($code) === 'SURPRISE') {
            $configuration = array_merge($configuration ?? [], ['mode_confirmed' => true]);
        }

        return $configuration;
    }

    /** @return array<string, mixed> */
    private function campaignAuditState(PromotionCampaign $campaign): array
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
    private function prizeAuditState(PromotionPrize $prize): array
    {
        return [
            'id' => (int) $prize->id,
            'code' => (string) $prize->code,
            'name_digest' => hash('sha256', (string) $prize->name),
            'fulfillment_mode' => (string) $prize->fulfillment_mode,
            'quota' => (int) $prize->quota,
            'reserved_count' => (int) $prize->reserved_count,
            'is_active' => (bool) $prize->is_active,
            'sort_order' => (int) $prize->sort_order,
            'configuration_digest' => hash('sha256', json_encode($prize->configuration, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)),
        ];
    }
}
