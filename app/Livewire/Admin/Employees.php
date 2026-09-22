<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Mail\StaffInvitationMail;
use App\Models\StaffInvitation;
use App\Models\Team;
use App\Models\User;
use App\Services\Auth\PasswordResetService;
use App\Support\Rbac\RbacCatalog;
use App\Support\Rbac\StaffInvitationService;
use App\Support\Rbac\StaffTeamService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
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

    public bool $showInviteModal = false;

    public bool $showAssignModal = false;

    public bool $showAccessModal = false;

    public ?int $selectedAccessTeamId = null;

    /** @var array<string, bool> */
    public array $accessPermissions = [];

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
        $this->selectedAccessTeamId = $this->teamId;
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
        $this->showInviteModal = false;
        session()->flash('status', 'Der Einrichtungslink wurde direkt per E-Mail versendet und ist 72 Stunden gültig.');
    }

    public function openInviteModal(): void
    {
        $this->authorize('staff.manage');
        $this->resetValidation();
        $this->showInviteModal = true;
    }

    public function closeInviteModal(): void
    {
        $this->showInviteModal = false;
        $this->resetValidation();
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
        $this->showAssignModal = false;
        session()->flash('status', 'Das bestehende Konto wurde als Mitarbeiter dem Team '.$user->currentTeam->name.' zugeordnet. Eine E-Mail-Verifizierung ist im Admin-Bereich nicht erforderlich.');
    }

    public function openAssignModal(): void
    {
        $this->authorize('staff.manage');
        $this->resetValidation();
        $this->showAssignModal = true;
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->resetValidation();
    }

    public function openAccessModal(): void
    {
        $this->authorize('roles.manage');
        $this->resetValidation();
        $this->showAccessModal = true;
        $this->selectAccessTeam($this->selectedAccessTeamId ?: $this->teamId);
    }

    public function closeAccessModal(): void
    {
        $this->showAccessModal = false;
        $this->resetValidation();
    }

    public function selectAccessTeam(?int $teamId): void
    {
        $this->authorize('roles.manage');
        $this->selectedAccessTeamId = $teamId;
        $team = $teamId ? Team::query()->findOrFail($teamId) : null;
        $this->accessPermissions = $team?->permissionMatrix() ?? [];
    }

    public function toggleAccessPermission(string $permission): void
    {
        $this->authorize('roles.manage');
        abort_unless(RbacCatalog::isKnown($permission) && ! RbacCatalog::isAdminOnly($permission), 422);

        $team = Team::query()->findOrFail($this->selectedAccessTeamId);
        $promotionPermission = in_array($permission, RbacCatalog::promotionTeamPermissions(), true);
        abort_if($team->isPromotionTeam() && ! $promotionPermission, 422);
        abort_if(! $team->isPromotionTeam() && $promotionPermission, 422);

        $this->accessPermissions[$permission] = ! (bool) ($this->accessPermissions[$permission] ?? false);
    }

    public function saveAccess(): void
    {
        $this->authorize('roles.manage');
        $team = Team::query()->findOrFail($this->selectedAccessTeamId);

        if ($team->isPromotionTeam()) {
            $matrix = RbacCatalog::promotionTeamMatrix();
        } else {
            $matrix = RbacCatalog::normalize(array_filter(
                $this->accessPermissions,
                static fn ($granted, $permission): bool => (bool) $granted
                    && ! RbacCatalog::isAdminOnly((string) $permission)
                    && ! in_array((string) $permission, RbacCatalog::promotionTeamPermissions(), true),
                ARRAY_FILTER_USE_BOTH,
            ));
        }

        $team->forceFill(['rbac_permissions' => $matrix])->save();
        $this->accessPermissions = $matrix;

        activity('rbac')
            ->causedBy(auth()->user())
            ->performedOn($team)
            ->withProperties(['permissions' => array_keys($matrix)])
            ->log('Teamrechte aktualisiert');

        $this->showAccessModal = false;
        session()->flash('status', 'Die Teamrechte wurden gespeichert.');
    }

    public function sendPasswordReset(int $userId, PasswordResetService $passwords): void
    {
        $this->authorize('staff.manage');
        $this->resetErrorBag();
        $employee = User::query()->whereIn('role', ['admin', 'staff'])->where('status', true)->findOrFail($userId);
        $status = $passwords->send($employee->email);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->addError('passwordReset', 'Bitte warte eine Minute, bevor du erneut einen Reset-Link versendest.');

            return;
        }

        session()->flash('status', 'Der Passwort-Reset-Link wurde über den Admin-Mailversand an den Mitarbeiter gesendet.');
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
            'permissionGroups' => RbacCatalog::groups(),
            'adminOnlyPermissions' => RbacCatalog::adminOnlyPermissions(),
        ])->layout('layouts.master');
    }
}
