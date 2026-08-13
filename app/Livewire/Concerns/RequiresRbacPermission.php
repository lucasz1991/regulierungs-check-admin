<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Gate;

trait RequiresRbacPermission
{
    abstract protected function requiredRbacPermission(): string;

    /**
     * Livewire's boot hook runs before the component's own mount method. This
     * prevents an unauthorized component from executing even read-side mount
     * work before the permission check.
     */
    public function bootRequiresRbacPermission(): void
    {
        Gate::authorize($this->requiredRbacPermission());
    }

    /**
     * Livewire invokes trait-prefixed lifecycle hooks alongside component
     * hooks. Authorize both the initial lazy mount and every later request so
     * a revoked permission cannot be reused through an old signed snapshot.
     */
    public function mountRequiresRbacPermission(): void
    {
        Gate::authorize($this->requiredRbacPermission());
    }

    public function hydrateRequiresRbacPermission(): void
    {
        Gate::authorize($this->requiredRbacPermission());
    }
}
