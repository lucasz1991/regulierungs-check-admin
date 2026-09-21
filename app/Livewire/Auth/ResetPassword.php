<?php

namespace App\Livewire\Auth;

use App\Services\Auth\PasswordResetService;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(PasswordResetService $passwords)
    {
        $this->resetErrorBag();
        $status = $passwords->reset($this->only('email', 'token', 'password', 'password_confirmation'));

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', 'Der Link ist ungültig oder abgelaufen. Bitte fordere einen neuen Link an.');

            return;
        }

        session()->flash('status', 'Dein Passwort wurde zurückgesetzt. Du kannst dich jetzt bei Regulierungs-CHECK anmelden.');

        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.auth.reset-password')->layout('layouts.app');
    }
}
