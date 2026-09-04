<?php

namespace App\Http\Responses;

use App\Support\Rbac\StaffLandingPage;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function __construct(private readonly StaffLandingPage $landingPage) {}

    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->route($this->landingPage->routeName($request->user()));
    }
}
