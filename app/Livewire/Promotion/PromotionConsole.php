<?php

namespace App\Livewire\Promotion;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Models\PromotionCampaign;
use App\Models\PromotionPrize;
use App\Models\PromotionWin;
use App\Services\Promotion\PromotionQrCodeService;
use App\Services\Promotion\PromotionWinService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class PromotionConsole extends Component
{
    use RequiresRbacPermission;
    use WithPagination;

    protected function requiredRbacPermission(): string
    {
        return 'promotion.wins.record';
    }

    #[Locked]
    public ?int $campaignId = null;

    #[Locked]
    public ?int $issuedWinId = null;

    public string $issuedQrSvg = '';

    public string $issuedUrl = '';

    public function mount(): void
    {
        $this->authorize('promotion.wins.record');
        $this->campaignId = PromotionCampaign::query()
            ->where('is_active', true)
            ->orderByDesc('starts_at')
            ->value('id');
    }

    public function chooseCampaign(int $campaignId): void
    {
        $this->authorize('promotion.wins.record');
        $campaign = PromotionCampaign::query()->findOrFail($campaignId);
        abort_unless($campaign->isCurrentlyActive(), 422, 'Diese Kampagne ist nicht aktiv.');

        $this->campaignId = $campaign->id;
        $this->clearIssuedCode();
        $this->resetPage();
    }

    public function issueWin(int $prizeId, PromotionWinService $wins, PromotionQrCodeService $qr): void
    {
        $this->authorize('promotion.wins.record');

        $prize = PromotionPrize::query()->with('campaign')->findOrFail($prizeId);
        abort_unless((int) $prize->campaign_id === (int) $this->campaignId, 422);

        $qr->assertConfigured();
        $issued = $wins->issue($prize->campaign, $prize, auth()->user(), $this->accessContext());
        $this->issuedWinId = $issued->win->id;
        $this->issuedUrl = $qr->redemptionUrl($issued->plainToken);
        $this->issuedQrSvg = $qr->svg($this->issuedUrl);
    }

    public function fulfill(int $winId, PromotionWinService $wins): void
    {
        $win = PromotionWin::query()->with(['prize', 'participation.user'])->findOrFail($winId);
        abort_unless(in_array($win->fulfillment_mode_snapshot, [
            PromotionPrize::FULFILLMENT_EXTERNAL,
            PromotionPrize::FULFILLMENT_ONSITE,
        ], true), 422, 'Unbekannter Ausgabemodus.');

        $permission = $win->fulfillment_mode_snapshot === PromotionPrize::FULFILLMENT_EXTERNAL
            ? 'promotion.fulfillment.external'
            : 'promotion.fulfillment.onsite';

        $this->authorize($permission);

        if ($win->fulfillment_mode_snapshot === PromotionPrize::FULFILLMENT_EXTERNAL) {
            abort_unless(auth()->user()->isAdmin(), 403);
        }

        abort_unless(
            $win->status === PromotionWin::STATUS_CONFIRMED
            && $win->participation?->user?->hasVerifiedEmail(),
            422,
            'Ausgabe erst nach Teilnehmerbestaetigung und E-Mail-Verifikation.',
        );

        $wins->fulfill($win, auth()->user(), $this->accessContext());
        session()->flash('status', 'Die Ausgabe wurde unveraenderlich protokolliert.');
    }

    public function clearIssuedCode(): void
    {
        $this->authorize('promotion.wins.record');
        $this->reset('issuedWinId', 'issuedQrSvg', 'issuedUrl');
    }

    public function render()
    {
        $this->authorize('promotion.wins.record');

        $campaigns = PromotionCampaign::query()->where('is_active', true)->orderBy('name')->get();
        $campaign = $this->campaignId
            ? PromotionCampaign::query()->with(['prizes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])->find($this->campaignId)
            : null;

        $winnerQuery = PromotionWin::query()
            ->whereNotNull('participation_id')
            ->when($this->campaignId, fn ($query) => $query->where('campaign_id', $this->campaignId))
            ->with([
                'campaign:id,name',
                'prize:id,name,fulfillment_mode',
                'issuedBy:id,name',
                'participation.user:id,name,email,email_verified_at',
            ])
            ->latest();

        return view('livewire.promotion.promotion-console', [
            'campaigns' => $campaigns,
            'campaign' => $campaign,
            'wins' => Gate::allows('promotion.wins.view_all') ? $winnerQuery->paginate(20) : collect(),
            'showFullIdentity' => auth()->user()->isAdmin(),
        ])->layout('layouts.promotion');
    }

    /** @return array{ip_address: string|null, user_agent: string|null} */
    private function accessContext(): array
    {
        return ['ip_address' => request()->ip(), 'user_agent' => request()->userAgent()];
    }
}
