<?php

namespace App\Http\Controllers;

use App\Models\StaffInvitation;
use App\Support\Rbac\StaffInvitationService;
use App\Support\Rbac\StaffLandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class StaffInvitationController extends Controller
{
    private const TOKEN_SESSION_KEY = 'staff_invitation_token';

    public function show(string $token)
    {
        $invitation = $this->resolve($token);

        abort_unless($invitation->isUsable(), 410, 'Dieser Einrichtungslink ist nicht mehr gültig.');

        session()->put(self::TOKEN_SESSION_KEY, $token);

        return redirect()->route('staff-invitations.accept');
    }

    public function accept()
    {
        $token = (string) session()->get(self::TOKEN_SESSION_KEY, '');
        $invitation = $this->resolve($token);

        abort_unless($invitation->isUsable(), 410, 'Dieser Einrichtungslink ist nicht mehr gültig.');

        // The route is a bearer-token page and must not inherit Livewire's
        // request-global asset auto injection from another rendered view in
        // the same long-running worker/test request.
        config()->set('livewire.inject_assets', false);

        return view('auth.accept-staff-invitation', compact('invitation'));
    }

    public function store(
        Request $request,
        StaffInvitationService $invitations,
        StaffLandingPage $landingPage,
    ) {
        $token = (string) $request->session()->get(self::TOKEN_SESSION_KEY, '');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $invitations->accept($token, $validated['name'], $validated['password']);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(self::TOKEN_SESSION_KEY);

        return redirect()
            ->route($landingPage->routeName($user))
            ->with('status', 'Ihr Mitarbeiterzugang wurde eingerichtet. Sie sind direkt angemeldet.');
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
