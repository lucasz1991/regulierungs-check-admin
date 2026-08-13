<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Rbac\RbacCatalog;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user): ?bool {
            return $user->isAdmin() && $user->isActive() ? true : null;
        });

        foreach (RbacCatalog::allPermissions() as $permission) {
            Gate::define($permission, static function (User $user) use ($permission): bool {
                return ! RbacCatalog::isAdminOnly($permission)
                    && $user->hasRbacPermission($permission);
            });
        }
    }
}
