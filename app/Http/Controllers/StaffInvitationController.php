<?php

namespace App\Http\Controllers;

use App\Models\StaffInvitation;
use App\Models\User;
use App\Support\Rbac\PromotionTeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class StaffInvitationController extends Controller
{
    private const TOKEN_SESSION_KEY = 'staff_invitation_token';

    public function show(string $token)
    {
        $invitation = $this->resolve($token);

        abort_unless($invitation->isUsable(), 410, 'Diese Einladung ist nicht mehr gueltig.');

        session()->put(self::TOKEN_SESSION_KEY, $token);

        return redirect()->route('staff-invitations.accept');
    }

    public function accept()
    {
        $token = (string) session()->get(self::TOKEN_SESSION_KEY, '');
        $invitation = $this->resolve($token);

        abort_unless($invitation->isUsable(), 410, 'Diese Einladung ist nicht mehr gueltig.');

        // The route is a bearer-token page and must not inherit Livewire's
        // request-global asset auto injection from another rendered view in
        // the same long-running worker/test request.
        config()->set('livewire.inject_assets', false);

        return view('auth.accept-staff-invitation', compact('invitation'));
    }

    public function store(Request $request, PromotionTeamService $teams)
    {
        $token = (string) $request->session()->get(self::TOKEN_SESSION_KEY, '');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($token, $validated, $teams): User {
            $invitation = StaffInvitation::query()
                ->with('team')
                ->where('token_hash', StaffInvitation::tokenHash($token))
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invitation->isUsable() || $invitation->role !== 'staff') {
                throw ValidationException::withMessages(['email' => 'Diese Einladung ist nicht mehr gueltig.']);
            }

            $promotionTeam = $teams->requireHardened();

            if ((int) $promotionTeam->id !== (int) $invitation->team_id) {
                throw ValidationException::withMessages(['email' => 'Die Einladung gehoert nicht zum Promotion-Team.']);
            }

            if (User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation->email)])->exists()) {
                throw ValidationException::withMessages(['email' => 'Zu dieser Einladung existiert bereits ein Konto.']);
            }

            $user = User::create([
                'name' => trim($validated['name']),
                'email' => mb_strtolower($invitation->email),
                'password' => Hash::make($validated['password']),
                'role' => 'staff',
                'status' => true,
                'email_verified_at' => now(),
                'current_team_id' => $promotionTeam->id,
            ]);

            $promotionTeam->users()->attach($user->id, ['role' => 'team_access']);
            $invitation->forceFill(['accepted_at' => now()])->save();

            return $user;
        }, 3);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(self::TOKEN_SESSION_KEY);

        return redirect()->route('promotion.console')->with('status', 'Ihr Mitarbeiterkonto wurde eingerichtet.');
    }

    private function resolve(string $token): StaffInvitation
    {
        abort_unless(preg_match('/\A[a-f0-9]{64}\z/i', $token) === 1, 404);

        return StaffInvitation::query()
            ->with('team')
            ->where('token_hash', StaffInvitation::tokenHash($token))
            ->firstOrFail();
    }
}
