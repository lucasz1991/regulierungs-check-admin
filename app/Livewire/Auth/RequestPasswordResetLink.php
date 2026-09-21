<?php

namespace App\Livewire\Auth;

use App\Services\Auth\PasswordResetService;
use Livewire\Component;

class RequestPasswordResetLink extends Component
{
    public string $email = '';

    public function sendResetLink(PasswordResetService $passwords): void
    {
        $this->resetErrorBag();
        $passwords->send($this->email);
        session()->flash('success', PasswordResetService::SENT_MESSAGE);
    }

    public function render()
    {
        return view('livewire.auth.request-password-reset-link')->layout('layouts.app');
    }
}
