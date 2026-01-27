<?php

namespace App\Services\Auth;

use App\Http\Requests\Auth\LoginRequest;

interface AuthServiceInterface
{
    public function login(LoginRequest $request): array;

    public function logout(): void;
}
