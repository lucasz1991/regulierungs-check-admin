<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use App\Models\Team;
use App\Support\Rbac\RbacCatalog;
use Livewire\Component;

class TeamPermissions extends Component
{
    use RequiresRbacPermission;

    protected function requiredRbacPermission(): string
    {
        return 'roles.manage';
    }

    public ?int $selectedTeamId = null;

    /** @var array<string, bool> */
    public array $permissions = [];

    public function mount(): void
    {
        $this->authorize('roles.manage');
        $this->selectTeam(Team::query()->orderBy('name')->value('id'));
    }

    public function selectTeam(?int $teamId): void
    {
        $this->authorize('roles.manage');
        $this->selectedTeamId = $teamId;
        $team = $teamId ? Team::query()->findOrFail($teamId) : null;
        $this->permissions = $team?->permissionMatrix() ?? [];
    }

    public function save(): void
    {
        $this->authorize('roles.manage');
        $team = Team::query()->findOrFail($this->selectedTeamId);

        if ($team->isPromotionTeam()) {
            $matrix = RbacCatalog::promotionTeamMatrix();
        } else {
            $matrix = RbacCatalog::normalize(array_filter(
                $this->permissions,
                static fn ($granted, $permission): bool => (bool) $granted
                    && ! RbacCatalog::isAdminOnly((string) $permission)
                    && ! in_array((string) $permission, RbacCatalog::promotionTeamPermissions(), true),
                ARRAY_FILTER_USE_BOTH,
            ));
        }

        $team->forceFill(['rbac_permissions' => $matrix])->save();
        $this->permissions = $matrix;

        activity('rbac')
            ->causedBy(auth()->user())
            ->performedOn($team)
            ->withProperties(['permissions' => array_keys($matrix)])
            ->log('Teamrechte aktualisiert');

        session()->flash('status', 'Die Teamrechte wurden gespeichert.');
    }

    public function togglePermission(string $permission): void
    {
        $this->authorize('roles.manage');
        abort_unless(RbacCatalog::isKnown($permission) && ! RbacCatalog::isAdminOnly($permission), 422);

        $team = Team::query()->findOrFail($this->selectedTeamId);
        abort_if($team->isPromotionTeam() && ! in_array($permission, RbacCatalog::promotionTeamPermissions(), true), 422);
        abort_if(! $team->isPromotionTeam() && in_array($permission, RbacCatalog::promotionTeamPermissions(), true), 422);

        $this->permissions[$permission] = ! (bool) ($this->permissions[$permission] ?? false);
    }

    public function render()
    {
        $this->authorize('roles.manage');

        return view('livewire.admin.team-permissions', [
            'teams' => Team::query()->orderBy('name')->get(),
            'selectedTeam' => $this->selectedTeamId ? Team::query()->find($this->selectedTeamId) : null,
            'groups' => RbacCatalog::groups(),
            'adminOnly' => RbacCatalog::adminOnlyPermissions(),
        ])->layout('layouts.master');
    }
}
