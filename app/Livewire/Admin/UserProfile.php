<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Models\Mail;
use App\Models\PromotionSpinResult;
use App\Models\PromotionTicket;
use App\Models\PromotionWin;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Admin\UserStatusService;
use App\Services\Promotion\PromotionResultMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UserProfile extends Component
{
    use RequiresRbacPermission;

    protected function requiredRbacPermission(): string
    {
        return 'users.manage';
    }

    #[Locked]
    public $userId;

    public $user;

    public $showMailModal = false;

    public $mailUserId = null;

    public $mailSubject = '';

    public $mailHeader = '';

    public $mailBody = '';

    public $mailLink = '';

    public function mount($userId)
    {
        $this->userId = $userId;
        $this->loadUser();
    }

    public function loadUser()
    {
        $this->user = User::findOrFail($this->userId);
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

    public function openMailModal()
    {
        // Prüfen, ob der Benutzer vorhanden ist
        if (! $this->user) {
            $this->dispatch('showAlert', 'Benutzer nicht gefunden.', 'error');

            return;
        }

        $this->mailUserId = $this->user->id;
        $this->showMailModal = true;
    }

    public function resetMailModal()
    {
        $this->showMailModal = false;
        $this->mailUserId = null;
        $this->mailSubject = '';
        $this->mailHeader = '';
        $this->mailBody = '';
        $this->mailLink = '';
    }

    public function sendMail()
    {
        // Validierung mit individuellen Fehlermeldungen
        $this->validate([
            'mailSubject' => 'required|string|max:255',
            'mailHeader' => 'required|string|max:255',
            'mailBody' => 'required|string',
        ], [
            'mailSubject.required' => 'Bitte geben Sie einen Betreff ein.',
            'mailSubject.max' => 'Der Betreff darf maximal 255 Zeichen lang sein.',
            'mailHeader.required' => 'Bitte geben Sie eine Überschrift ein.',
            'mailHeader.max' => 'Die Überschrift darf maximal 255 Zeichen lang sein.',
            'mailBody.required' => 'Bitte geben Sie eine Nachricht ein.',
        ]);

        // Inhalte für die Datenbank vorbereiten
        $content = [
            'subject' => $this->mailSubject,
            'header' => $this->mailHeader,
            'body' => $this->mailBody,
            'link' => $this->mailLink, // Link kann optional leer sein
        ];

        // Mail an den gespeicherten Benutzer senden
        if ($this->user) {
            Mail::create([
                'status' => false,
                'content' => $content,
                'recipients' => [
                    [
                        'user_id' => $this->user->id,
                        'email' => $this->user->email,
                        'status' => false, // Status für den Empfänger
                    ],
                ],
            ]);

            $this->dispatch('showAlert', 'E-Mail wurde zur Verarbeitung an '.$this->user->email.' vorbereitet.', 'success');
        } else {
            $this->dispatch('showAlert', 'Benutzer nicht gefunden.', 'error');
        }

        // Modal zurücksetzen
        $this->resetMailModal();
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

    public function render()
    {
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

        return view('livewire.admin.user-profile', [
            'user' => $this->user,
            'promotionTickets' => $tickets,
            'legacyPromotionWins' => Schema::hasTable('wins')
                ? PromotionWin::query()->whereHas('participation', fn ($query) => $query->where('user_id', $this->userId))->with(['campaign', 'prize', 'issuedBy'])->latest()->get()
                : collect(),
            'socialAccounts' => Schema::hasTable('social_accounts')
                ? SocialAccount::query()->where('user_id', $this->userId)->orderBy('provider')->get()
                : collect(),
            'likedProductsCount' => Schema::hasTable('liked_products')
                ? DB::table('liked_products')->where('user_id', $this->userId)->count()
                : 0,
        ])->layout('layouts.master');
    }
}
