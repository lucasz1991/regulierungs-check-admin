<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Mail\StaffInvitationMail;
use App\Models\StaffInvitation;
use App\Models\Team;
use App\Models\User;
use App\Support\Rbac\StaffInvitationService;
use App\Support\Rbac\StaffTeamService;
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

    public ?int $teamId = null;

    public string $existingEmail = '';

    public ?int $existingTeamId = null;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('staff.manage');
        $firstTeamId = Team::query()
            ->where('personal_team', false)
            ->orderBy('name')
            ->value('id');

        $this->teamId = $firstTeamId ? (int) $firstTeamId : null;
        $this->existingTeamId = $this->teamId;
    }

    public function invite(StaffInvitationService $invitations): void
    {
        $this->authorize('staff.manage');

        $validated = $this->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'position' => ['nullable', 'string', 'max:100'],
            'teamId' => ['required', 'integer'],
        ]);

        $issued = $invitations->issue(
            auth()->user(),
            $validated['email'],
            (int) $validated['teamId'],
            $validated['position'] ?: null,
        );
        $invitation = $issued['invitation'];

        try {
            Mail::to($invitation->email)->send(new StaffInvitationMail(
                $invitation,
                route('staff-invitations.show', ['token' => $issued['token']]),
            ));
        } catch (\Throwable $exception) {
            $invitation->forceFill(['expires_at' => now()])->save();
            report($exception);
            $this->addError('email', 'Der Einrichtungslink konnte nicht versendet werden und wurde deaktiviert.');

            return;
        }

        activity('staff')
            ->causedBy(auth()->user())
            ->performedOn($invitation)
            ->withProperties(['email' => $invitation->email, 'team_id' => $invitation->team_id])
            ->log('Mitarbeiter-Einrichtungslink versendet');

        $this->reset('email', 'position');
        session()->flash('status', 'Der Einrichtungslink wurde direkt per E-Mail versendet und ist 72 Stunden gültig.');
    }

    public function revoke(int $invitationId): void
    {
        $this->authorize('staff.manage');

        $invitation = StaffInvitation::query()->findOrFail($invitationId);
        abort_if($invitation->accepted_at !== null, 422, 'Bereits eingerichtete Zugänge können nicht widerrufen werden.');

        $invitation->forceFill(['expires_at' => now()])->save();

        activity('staff')
            ->causedBy(auth()->user())
            ->performedOn($invitation)
            ->log('Mitarbeiter-Einrichtungslink widerrufen');
    }

    public function assignExisting(StaffTeamService $teams): void
    {
        $this->authorize('staff.manage');
        $validated = $this->validate([
            'existingEmail' => ['required', 'email:rfc', 'max:255'],
            'existingTeamId' => ['required', 'integer'],
        ]);
        $admin = auth()->user();
        abort_unless($admin instanceof User, 403);

        $user = $teams->assignExisting(
            $admin,
            $validated['existingEmail'],
            (int) $validated['existingTeamId'],
        );

        activity('staff')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['team_id' => $user->currentTeam->id])
            ->log('Bestehendes Konto als Mitarbeiter einem Team zugeordnet');

        $this->reset('existingEmail');
        session()->flash('status', 'Das bestehende Konto wurde als Mitarbeiter dem Team '.$user->currentTeam->name.' zugeordnet. Eine E-Mail-Verifizierung ist im Admin-Bereich nicht erforderlich.');
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
            'teams' => Team::query()
                ->where('personal_team', false)
                ->orderBy('name')
                ->get(['id', 'name', 'rbac_permissions']),
        ])->layout('layouts.master');
    }
}
