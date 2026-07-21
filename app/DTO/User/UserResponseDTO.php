<?php

namespace App\DTO\User;

use App\DTO\TeamResponseDTO;

class UserResponseDTO
{
    public static function make($user, array $includes = []): array
    {
        $data = [
            'id' => $user->id,
            'name' => $user->fullname,
            'jersey_no' => $user->jersey_no,
            'phone' => $user->phone,
            'fcm_token' => $user->fcm_token,
            'dob' => $user->dob,
            'state' => $user->state,
            'city' => $user->city,
            'role' => $user->role,
            'positions' => $user->positions ?? [],
            'country' => $user->country,
            'photo' => $user->photo ? asset('storage/' . $user->photo) : null,
            'bio' => $user->bio,
            'qr_code' => $user->qr_code ? asset('storage/' . $user->qr_code) : null,
        ];

        if (in_array('teams', $includes)) {
            $data['teams'] = self::teams($user);
        }
        return $data;
    }

    public static function fromModel($user): array
    {
        return self::make($user);
    }

    public static function collection($users): array
    {
        return array_map(fn($user) => self::make($user), $users->all());
    }

    public static function teams($user): array
    {
        $teams = $user->teams()->get();

        if ($teams->count() > 0) {
            return TeamResponseDTO::collection($teams);
        }
        return [];
    }
}
