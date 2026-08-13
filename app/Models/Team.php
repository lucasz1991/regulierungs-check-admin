<?php

namespace App\Models;

use App\Support\Rbac\RbacCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'personal_team' => 'boolean',
        'rbac_permissions' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
        'rbac_permissions',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /** @return array<string, bool> */
    public function permissionMatrix(): array
    {
        return RbacCatalog::normalize($this->rbac_permissions ?? []);
    }

    public function grants(string $permission): bool
    {
        return (bool) ($this->permissionMatrix()[$permission] ?? false);
    }

    public function isPromotionTeam(): bool
    {
        return mb_strtolower(trim($this->name)) === mb_strtolower(RbacCatalog::PROMOTION_TEAM_NAME)
            && ! $this->personal_team;
    }

    public function staffInvitations()
    {
        return $this->hasMany(StaffInvitation::class);
    }
}
