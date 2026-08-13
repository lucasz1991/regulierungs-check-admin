<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Mail\StaffInvitationMail;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Support\Rbac\PromotionTeamService;
use App\Support\Rbac\StaffInvitationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class Employees extends Component
{
    use RequiresRbacPermission;
    use WithPagination;

    protected function requiredRbacPermission(): string
    {
        return 'staff.manage';
    }

    public string $email = '';

    public string $position = '';

    public string $existingEmail = '';

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('staff.manage');
    }

    public function invite(StaffInvitationService $invitations): void
    {
        $this->authorize('staff.manage');

        $validated = $this->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'position' => ['nullable', 'string', 'max:100'],
        ]);

        $issued = $invitations->issue(auth()->user(), $validated['email'], $validated['position'] ?: null);
        $invitation = $issued['invitation'];

        try {
            Mail::to($invitation->email)->send(new StaffInvitationMail(
                $invitation,
                route('staff-invitations.show', ['token' => $issued['token']]),
            ));
        } catch (\Throwable $exception) {
            $invitation->forceFill(['expires_at' => now()])->save();
            report($exception);
            $this->addError('email', 'Die Einladung konnte nicht versendet werden und wurde deaktiviert.');

            return;
        }

        activity('staff')
            ->causedBy(auth()->user())
            ->performedOn($invitation)
            ->withProperties(['email' => $invitation->email, 'team_id' => $invitation->team_id])
            ->log('Mitarbeitereinladung versendet');

        $this->reset('email', 'position');
        session()->flash('status', 'Die 72-Stunden-Einladung wurde versendet.');
    }

    public function revoke(int $invitationId): void
    {
        $this->authorize('staff.manage');

        $invitation = StaffInvitation::query()->findOrFail($invitationId);
        abort_if($invitation->accepted_at !== null, 422, 'Angenommene Einladungen koennen nicht widerrufen werden.');

        $invitation->forceFill(['expires_at' => now()])->save();

        activity('staff')
            ->causedBy(auth()->user())
            ->performedOn($invitation)
            ->log('Mitarbeitereinladung widerrufen');
    }

    public function promoteExisting(PromotionTeamService $teams): void
    {
        $this->authorize('staff.manage');
        $validated = $this->validate([
            'existingEmail' => ['required', 'email:rfc', 'max:255', 'exists:users,email'],
        ]);
        $email = mb_strtolower(trim($validated['existingEmail']));
        $admin = auth()->user();
        abort_unless($admin instanceof User, 403);

        $user = DB::transaction(function () use ($email, $teams, $admin): User {
            $promotionTeam = $teams->ensure($admin);
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($user->isAdmin(), 422, 'Volladmin-Konten werden nicht in Mitarbeiterkonten umgewandelt.');

            $user->forceFill([
                'role' => 'staff',
                'status' => true,
                'current_team_id' => $promotionTeam->id,
            ])->save();
            $user->teams()->syncWithoutDetaching([
                $promotionTeam->id => ['role' => 'team_access'],
            ]);

            return $user->setRelation('currentTeam', $promotionTeam);
        }, 3);

        activity('staff')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['team_id' => $user->currentTeam->id])
            ->log('Bestehendes Konto zum Promotion-Mitarbeiter hochgestuft');

        $this->reset('existingEmail');
        session()->flash('status', 'Das bestehende Konto wurde dem Promotion-Team zugeordnet. Eine unverifizierte E-Mail-Adresse bleibt bis zur Bestätigung gesperrt.');
    }

    public function render()
    {
        $this->authorize('staff.manage');

        return view('livewire.admin.employees', [
            'employees' => User::query()
                ->whereIn('role', ['admin', 'staff'])
                ->with('currentTeam')
                ->latest()
                ->paginate(10),
            'invitations' => StaffInvitation::query()
                ->with(['team:id,name', 'inviter:id,name'])
                ->latest()
                ->limit(30)
                ->get(),
        ])->layout('layouts.master');
    }
}
