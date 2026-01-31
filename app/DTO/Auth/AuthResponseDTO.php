<?php

namespace App\DTO\Auth;

class AuthResponseDTO
{
    public static function make(array $data): array
    {
        $user = $data['user'];
        $token = $data['token'];

        return [
            'access_token' => $token,
            'user' => [
                'name' => $user->fullname,
                'phone' => $user->phone,
                'fcm_token' => $user->fcm_token,
                'dateOfBirth' => $user->dob,
                'state' => $user->state,
                'city' => $user->city,
                'role' => $user->role,
                'country' => $user->country,
                'photo' => $user->photo,
            ],
            'is_new' => is_null($user->name),
        ];
    }
}
