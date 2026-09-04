<?php

namespace App\Support\Rbac;

use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class StaffInvitationService
{
    public function __construct(private readonly StaffTeamService $teams) {}

    /** @return array{invitation: StaffInvitation, token: string} */
    public function issue(User $inviter, string $email, int $teamId, ?string $position = null): array
    {
        if (! $inviter->isAdmin() || ! $inviter->isActive()) {
            throw new AuthorizationException('Nur ein aktiver Administrator darf Mitarbeiterzugänge anlegen.');
        }

        $email = mb_strtolower(trim($email));
        $position = filled($position) ? trim((string) $position) : null;

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'Zu dieser E-Mail-Adresse existiert bereits ein Konto.']);
        }

        $plainToken = bin2hex(random_bytes(32));

        $invitation = DB::transaction(function () use ($inviter, $email, $teamId, $position, $plainToken): StaffInvitation {
            $team = $this->teams->requireAssignable($teamId, 'teamId', true);

            StaffInvitation::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->update(['expires_at' => now()]);

            return StaffInvitation::create([
                'email' => $email,
                'token_hash' => StaffInvitation::tokenHash($plainToken),
                'team_id' => $team->id,
                'invited_by' => $inviter->id,
                'role' => 'staff',
                'position' => $position,
                'expires_at' => now()->addHours(72),
            ]);
        });

        return ['invitation' => $invitation, 'token' => $plainToken];
    }

    public function accept(string $plainToken, string $name, string $password): User
    {
        return DB::transaction(function () use ($plainToken, $name, $password): User {
            $invitation = StaffInvitation::query()
                ->where('token_hash', StaffInvitation::tokenHash($plainToken))
                ->lockForUpdate()
                ->first();

            if (! $invitation || ! $invitation->isUsable() || $invitation->role !== 'staff') {
                throw ValidationException::withMessages([
                    'email' => 'Dieser Einrichtungslink ist nicht mehr gültig.',
                ]);
            }

            $team = $this->teams->requireAssignable((int) $invitation->team_id, 'email', true);

            if (User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation->email)])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Zu diesem Einrichtungslink existiert bereits ein Konto.',
                ]);
            }

            $user = User::create([
                'name' => trim($name),
                'email' => mb_strtolower($invitation->email),
                'password' => Hash::make($password),
                'role' => 'staff',
                'status' => true,
                // Der Einrichtungslink selbst wurde an diese Adresse
                // zugestellt. Es folgt keine zweite Verifikationsstufe.
                'email_verified_at' => now(),
                'current_team_id' => $team->id,
            ]);

            $team->users()->attach($user->id, ['role' => 'team_access']);
            $invitation->forceFill(['accepted_at' => now()])->save();

            return $user->setRelation('currentTeam', $team);
        }, 3);
    }
}
