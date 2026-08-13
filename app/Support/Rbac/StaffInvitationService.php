<?php

namespace App\Support\Rbac;

use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StaffInvitationService
{
    public function __construct(private readonly PromotionTeamService $teams) {}

    /** @return array{invitation: StaffInvitation, token: string} */
    public function issue(User $inviter, string $email, ?string $position = null): array
    {
        if (! $inviter->isAdmin() || ! $inviter->isActive()) {
            throw new AuthorizationException('Nur ein aktiver Volladmin darf Mitarbeiter einladen.');
        }

        $email = mb_strtolower(trim($email));
        $position = filled($position) ? trim((string) $position) : null;

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'Zu dieser E-Mail-Adresse existiert bereits ein Konto.']);
        }

        $plainToken = bin2hex(random_bytes(32));

        $invitation = DB::transaction(function () use ($inviter, $email, $position, $plainToken): StaffInvitation {
            $team = $this->teams->ensure($inviter);

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
}
