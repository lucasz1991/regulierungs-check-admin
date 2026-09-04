<?php

namespace App\Support\Rbac;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StaffTeamService
{
    public function requireAssignable(int $teamId, string $errorKey = 'teamId', bool $lock = false): Team
    {
        $query = Team::query()
            ->whereKey($teamId)
            ->where('personal_team', false);

        if ($lock) {
            $query->lockForUpdate();
        }

        $team = $query->first();

        if (! $team) {
            throw ValidationException::withMessages([
                $errorKey => 'Bitte wählen Sie ein gültiges gemeinsames Team aus.',
            ]);
        }

        if ($team->isPromotionTeam() && $team->permissionMatrix() !== RbacCatalog::promotionTeamMatrix()) {
            throw ValidationException::withMessages([
                $errorKey => 'Das Promotion-Team besitzt nicht den vorgeschriebenen Rechtestand.',
            ]);
        }

        return $team;
    }

    public function assignExisting(User $actor, string $email, int $teamId): User
    {
        $this->authorizeAdministrator($actor);
        $email = mb_strtolower(trim($email));

        return DB::transaction(function () use ($email, $teamId): User {
            $team = $this->requireAssignable($teamId, 'existingTeamId', true);
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if (! $user) {
                throw ValidationException::withMessages([
                    'existingEmail' => 'Zu dieser E-Mail-Adresse wurde kein Konto gefunden.',
                ]);
            }

            if ($user->isAdmin()) {
                throw ValidationException::withMessages([
                    'existingEmail' => 'Admin-Konten können nicht in Mitarbeiterkonten umgewandelt werden.',
                ]);
            }

            $user->forceFill([
                'role' => 'staff',
                'status' => true,
                'current_team_id' => $team->id,
            ])->save();

            // Mitarbeiter besitzen genau die ausdrücklich gewählte aktive
            // Teamzuordnung. Alte Benutzer- oder Mitarbeiterpivots dürfen
            // keine unbemerkten Nebenrechte zurücklassen.
            $user->teams()->sync([
                $team->id => ['role' => 'team_access'],
            ]);

            return $user->fresh()->setRelation('currentTeam', $team);
        }, 3);
    }

    private function authorizeAdministrator(User $actor): void
    {
        if (! $actor->isAdmin() || ! $actor->isActive()) {
            throw new AuthorizationException('Nur ein aktiver Administrator darf Mitarbeiter Teams zuweisen.');
        }
    }
}
