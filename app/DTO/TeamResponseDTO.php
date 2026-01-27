<?php

namespace App\DTO;

use App\Models\Team;

class TeamResponseDTO
{
    public static function fromModel(Team $team): array
    {
        return [
            'id'         => $team->id,
            'name'       => $team->name,
            'logo'       => $team->logo ? asset('storage/' . $team->logo) : null,
            'city'       => $team->city,
            'player_count' => $team->getPlayerCount(),
            'captain'    => $team->captain()?->only(['id', 'fullname', 'dob', 'role', 'phone', 'state', 'country', 'photo']),
        ];
    }

    public static function collection(iterable $teams): array
    {
        return collect($teams)
            ->map(fn($team) => self::fromModel($team))
            ->toArray();
    }
}
