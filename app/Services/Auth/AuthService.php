<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
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

    public function refresh()
    {
        try {
            $user = auth('api')->user();

            return response()->json([
                'message' => 'Token status retrieved successfully',
                'data' => [
                    'is_valid' => $user !== null,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Token status retrieved successfully',
                'data' => [
                    'is_valid' => false,
                ],
            ]);
        }
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens->each(function ($token) {
                $token->delete();
            });
        }
    }
}
