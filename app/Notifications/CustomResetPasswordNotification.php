<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class CustomResetPasswordNotification extends ResetPassword
{
    /**
     * Keep the legacy constructor call in User compatible while relying on
     * Laravel's maintained notification implementation.
     */
    public function __construct(mixed $user, string $token)
    {
        parent::__construct($token);
    }
}
