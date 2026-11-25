<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Models\ClaimRating;
use App\Livewire\Concerns\ComposesCustomerMail;
use Illuminate\Support\Collection;
use App\Models\PromotionSpinResult;
use App\Models\PromotionTicket;
use App\Models\PromotionTurn;
use App\Models\PromotionWin;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Admin\UserStatusService;
use App\Services\Promotion\PromotionResultMailer;
use App\Services\Promotion\PromotionSettingsService;
use App\Services\Promotion\PromotionTicketService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class UserProfile extends Component
{
    use RequiresRbacPermission;
    use ComposesCustomerMail;
    use WithPagination;

    protected function requiredRbacPermission(): string
    {
        return 'users.manage';
    }

    #[Locked]
    public $userId;

    public $user;


    public function mount($userId)
    {
        $this->userId = $userId;
        $this->loadUser();
    }

    public function loadUser()
    {
        $query = User::query();
        $hasCustomerTable = Schema::hasTable('customers');
        $hasTeamTables = Schema::hasTable('teams') && Schema::hasTable('team_user');

        if ($hasCustomerTable) {
            $query->with('customer');
        }

        if ($hasTeamTables) {
            $query->with('currentTeam');
        }

        $this->user = $query->findOrFail($this->userId);

        if (! $hasTeamTables) {
            $this->user->setRelation('currentTeam', null);
        }

        if (! $hasCustomerTable) {
            $this->user->setRelation('customer', null);
        }
    }

    public function activateUser(UserStatusService $statuses)
    {
        $result = $this->mutateUserStatus($statuses, true);

        if ($result['changed'] === 1) {
            $this->dispatch('showAlert', 'Benutzer erfolgreich aktiviert.', 'success');
        } else {
            $this->dispatch('showAlert', 'Benutzer ist bereits aktiv.', 'info');
        }

        $this->loadUser();
    }

    public function deactivateUser(UserStatusService $statuses)
    {
        $result = $this->mutateUserStatus($statuses, false);

        if ($result['changed'] === 1) {
            $this->dispatch('showAlert', 'Benutzer erfolgreich deaktiviert.', 'success');
        } else {
            $this->dispatch('showAlert', 'Benutzer ist bereits inaktiv.', 'info');
        }

        $this->loadUser();
    }

    /** @return array{changed: int, total: int} */
    private function mutateUserStatus(UserStatusService $statuses, bool $active): array
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        return $statuses->setActive($actor, [$this->userId], $active);
    }

    protected function selectedMailIds(?int $id): array
    {
        return [$this->user->getKey()];
    }

    protected function mailAudience(array $ids): Collection
    {
        return User::whereKey($this->user->getKey())->whereIntegerInRaw('id', $ids)->get(['id', 'email']);
    }

    public function resendPromotionMail(int $resultId, PromotionResultMailer $mailer): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && $actor->isAdmin() && $actor->isActive(), 403);
        $result = PromotionSpinResult::query()->with('ticket')->findOrFail($resultId);
        abort_unless((int) $result->ticket?->user_id === (int) $this->userId, 404);

        $sent = $mailer->resend($result, $actor);
        $this->dispatch('showAlert', $sent ? 'Ergebnis-E-Mail wurde erneut versendet.' : 'Die Ergebnis-E-Mail konnte nicht versendet werden.', $sent ? 'success' : 'error');
    }

    public function issuePromotionTestTicket(PromotionTicketService $tickets, PromotionSettingsService $settings): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && $actor->isAdmin() && $actor->isActive(), 403);
        $campaignId = $settings->publicCampaignId();
        abort_unless($campaignId, 422, 'Keine öffentliche Kampagne aktiv.');
        $tickets->issueTestTicket(User::query()->findOrFail($this->userId), \App\Models\PromotionCampaign::query()->findOrFail($campaignId), $actor);
        $this->dispatch('showAlert', 'Test-Ticket wurde ausgestellt. Es löst keine Kontingente, Codes oder Teilnehmernachrichten aus.', 'success');
    }

    public function resetPromotionTestTicket(int $ticketId, PromotionTicketService $tickets): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User && $actor->isAdmin() && $actor->isActive(), 403);
        $ticket = PromotionTicket::query()->where('user_id', $this->userId)->findOrFail($ticketId);
        $tickets->resetTestTicket($ticket, $actor);
        $this->dispatch('showAlert', 'Test-Drehung zurückgesetzt; ein neues Test-Ticket wurde ausgestellt.', 'success');
    }

    public function render()
    {
        $canViewRatings = Gate::allows('reviews.manage');
        $ratingsReady = Schema::hasTable('claim_ratings');
        $ratings = $canViewRatings && $ratingsReady
            ? ClaimRating::query()
                ->where('user_id', $this->userId)
                ->select(['id', 'user_id', 'insurance_id', 'insurance_type_id', 'insurance_subtype_id', 'status', 'is_public', 'rating_score', 'created_at'])
                ->with(['insurance:id,name', 'insuranceType:id,name', 'insuranceSubtype:id,name'])
                ->latest('created_at')->latest('id')
                ->paginate(8, ['*'], 'ratingsPage')
            : null;

        $promotionReady = Schema::hasTable('promotion_tickets') && Schema::hasTable('promotion_spin_results');
        $tickets = $promotionReady
            ? PromotionTicket::query()
                ->where('user_id', $this->userId)
                ->with([
                    'campaign:id,name,code',
                    'participation:id,public_id',
                    'turns.startedBy:id,name',
                    'results.recordedBy:id,name',
                    'results.fulfilledBy:id,name',
                ])
                ->latest('issued_at')
                ->get()
            : collect();

        $staffPromotionTurns = Schema::hasTable('promotion_turns')
            ? PromotionTurn::query()
                ->where('started_by', $this->userId)
                ->with([
                    'campaign:id,name,code',
                    'ticket:id,user_id,participation_id',
                    'ticket.user:id,name,email',
                    'ticket.participation:id,public_id',
                ])
                ->latest('started_at')
                ->limit(50)
                ->get()
            : collect();

        return view('livewire.admin.user-profile', [
            'user' => $this->user,
            'isEmployeeProfile' => $this instanceof StaffProfile,
            'ratings' => $ratings,
            'canViewRatings' => $canViewRatings,
            'ratingsReady' => $ratingsReady,
            'promotionTickets' => $tickets,
            'staffPromotionTurns' => $staffPromotionTurns,
            'canManageUserStatus' => auth()->user()?->hasRbacPermission('users.manage') ?? false,
            'canSendUserMail' => auth()->user()?->hasRbacPermission('mails.manage') ?? false,
            'legacyPromotionWins' => Schema::hasTable('wins')
                ? PromotionWin::query()->whereHas('participation', fn ($query) => $query->where('user_id', $this->userId))->with(['campaign', 'prize', 'issuedBy'])->latest()->get()
                : collect(),
            'socialAccounts' => Schema::hasTable('social_accounts')
                ? SocialAccount::query()->where('user_id', $this->userId)->orderBy('provider')->get()
                : collect(),
        ])->layout('layouts.master');
    }
}
