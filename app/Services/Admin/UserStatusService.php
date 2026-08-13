<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class UserStatusService
{
    /**
     * @param  iterable<mixed>  $targetIds
     * @return array{changed: int, total: int}
     */
    public function setActive(User $actor, iterable $targetIds, bool $active): array
    {
        $ids = $this->normalizeIds($targetIds);

        if ($ids->isEmpty()) {
            return ['changed' => 0, 'total' => 0];
        }

        return DB::transaction(function () use ($actor, $ids, $active): array {
            // Every status mutation takes the complete admin set first. This
            // serializes concurrent checks of the last active global admin.
            $globalAdmins = User::query()
                ->where('role', 'admin')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (User $user): int => (int) $user->getKey());

            // Lock actor and non-admin targets together in stable order. Admin
            // targets are already part of the globally locked collection.
            $otherIds = collect($ids->all())
                ->push((int) $actor->getKey())
                ->unique()
                ->diff($globalAdmins->keys())
                ->sort()
                ->values();

            $otherUsers = User::query()
                ->whereIn('id', $otherIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (User $user): int => (int) $user->getKey());

            $lockedActor = $globalAdmins->get((int) $actor->getKey())
                ?? $otherUsers->get((int) $actor->getKey());

            abort_unless(
                $lockedActor !== null
                    && $lockedActor->isActive()
                    && $lockedActor->hasRbacPermission('users.manage'),
                403,
            );

            $targets = $ids->map(
                fn (int $id): ?User => $globalAdmins->get($id) ?? $otherUsers->get($id),
            );

            abort_unless($targets->filter()->count() === $ids->count(), 404);

            /** @var Collection<int, User> $targets */
            if (! $lockedActor->isAdmin()) {
                // Delegated users.manage is intentionally incapable of
                // activating, deactivating, or bulk-touching a global admin.
                abort_if($targets->contains(fn (User $target): bool => $target->isAdmin()), 403);
            }

            // No account may invalidate its own authenticated session through
            // this workflow. For global admins this also protects the common
            // single-admin case before any write occurs.
            abort_if(
                ! $active && $targets->contains(
                    fn (User $target): bool => $target->is($lockedActor),
                ),
                403,
            );

            if (! $active) {
                $activeAdminIds = $globalAdmins
                    ->filter(fn (User $admin): bool => $admin->isActive())
                    ->keys()
                    ->all();
                $deactivatedAdminIds = $targets
                    ->filter(fn (User $target): bool => $target->isAdmin() && $target->isActive())
                    ->map(fn (User $target): int => (int) $target->getKey())
                    ->all();

                abort_if(
                    $deactivatedAdminIds !== []
                        && array_diff($activeAdminIds, $deactivatedAdminIds) === [],
                    403,
                );
            }

            $changed = 0;

            foreach ($targets as $target) {
                if ($target->isActive() !== $active) {
                    $target->forceFill(['status' => $active])->save();
                    $changed++;
                }
            }

            return ['changed' => $changed, 'total' => $targets->count()];
        }, 3);
    }

    /**
     * @param  iterable<mixed>  $targetIds
     * @return Collection<int, int>
     */
    private function normalizeIds(iterable $targetIds): Collection
    {
        return collect($targetIds)
            ->map(function (mixed $targetId): int {
                abort_unless(
                    is_int($targetId) || (is_string($targetId) && ctype_digit($targetId)),
                    422,
                );

                $targetId = (int) $targetId;
                abort_unless($targetId > 0, 422);

                return $targetId;
            })
            ->unique()
            ->values();
    }
}
