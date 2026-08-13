<?php

namespace App\Support\Rbac;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PromotionTeamService
{
    public function find(): ?Team
    {
        return Team::query()
            ->whereRaw('LOWER(name) = ?', [strtolower(RbacCatalog::PROMOTION_TEAM_NAME)])
            ->first();
    }

    public function requireHardened(): Team
    {
        $team = $this->find();

        if (! $team || ! $team->isPromotionTeam() || $team->permissionMatrix() !== RbacCatalog::promotionTeamMatrix()) {
            throw new RuntimeException('Das Promotion-Team fehlt oder besitzt nicht den verbindlichen Rechtestand.');
        }

        return $team;
    }

    public function ensure(User $owner): Team
    {
        if (! $owner->isAdmin() || ! $owner->isActive()) {
            throw new RuntimeException('Nur ein Volladmin darf Eigentuer des Promotion-Teams sein.');
        }

        return DB::transaction(function () use ($owner): Team {
            $matches = Team::query()
                ->whereRaw('LOWER(name) = ?', [strtolower(RbacCatalog::PROMOTION_TEAM_NAME)])
                ->lockForUpdate()
                ->get();

            if ($matches->count() > 1) {
                throw new RuntimeException('Mehrere Teams mit dem Namen Promotion gefunden. Bitte zuerst manuell bereinigen.');
            }

            $team = $matches->first() ?? new Team;
            $team->forceFill([
                'user_id' => $owner->id,
                'name' => RbacCatalog::PROMOTION_TEAM_NAME,
                'personal_team' => false,
                'rbac_permissions' => RbacCatalog::promotionTeamMatrix(),
            ])->save();

            return $team->fresh();
        });
    }
}
