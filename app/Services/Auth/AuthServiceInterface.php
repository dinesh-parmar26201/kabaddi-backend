<?php

namespace App\Services\Auth;

use App\Http\Requests\Auth\LoginRequest;

interface AuthServiceInterface
{
    public function login(LoginRequest $request): array;

    public function refresh(): array;

    public function logout(): void;
}
