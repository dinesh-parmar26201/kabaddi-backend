<?php

namespace App\DTO\User;

class UserResponseDTO
{
    public static function make($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->fullname,
            'phone' => $user->phone,
            'fcm_token' => $user->fcm_token,
            'dob' => $user->dob,
            'state' => $user->state,
            'country' => $user->country,
            'photo' => $user->photo ? asset('storage/' . $user->photo) : null,
        ];
    }
}
