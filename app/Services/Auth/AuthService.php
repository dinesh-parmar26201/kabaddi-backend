<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Http\Requests\Auth\LoginRequest;

class AuthService implements AuthServiceInterface
{
    public function login(LoginRequest $request): array
    {
        $user = User::firstOrCreate(
            ['phone' => $request->phone],
            ['fcm_token' => $request->fcm_token]
        );

        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        return [
            'user' => $user,
            'token' => $user->createToken('mobile-auth')->accessToken,
        ];
    }
}
