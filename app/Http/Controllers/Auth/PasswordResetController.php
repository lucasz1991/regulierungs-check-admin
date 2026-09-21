<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function send(Request $request, PasswordResetService $passwords): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email', 'max:255']], [
            'email.required' => 'Bitte gib deine E-Mail-Adresse ein.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'email.max' => 'Die E-Mail-Adresse darf höchstens 255 Zeichen lang sein.',
        ]);
        $passwords->send($request->string('email')->toString());

        return back()->with('status', PasswordResetService::SENT_MESSAGE);
    }

    public function reset(Request $request, PasswordResetService $passwords): RedirectResponse
    {
        $status = $passwords->reset($request->only('email', 'token', 'password', 'password_confirmation'));

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Dein Passwort wurde zurückgesetzt. Du kannst dich jetzt bei Regulierungs-CHECK anmelden.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'Der Link ist ungültig oder abgelaufen. Bitte fordere einen neuen Link an.']);
    }
}
