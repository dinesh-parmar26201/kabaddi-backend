<?php

namespace App\DTO\Auth;

use App\DTO\User\UserResponseDTO;

class AuthResponseDTO
{
    public static function make(array $data): array
    {
        $user = $data['user'];
        $token = $data['token'];

        return [
            'access_token' => $token,
            'user' => UserResponseDTO::make($user),
            'is_new' => is_null($user->fullname),
        ];
    }
}
