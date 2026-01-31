<?php

namespace App\DTO;

use App\Models\Team;
use App\DTO\User\UserResponseDTO;

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
            'captain'    => $team->captain() ? UserResponseDTO::make($team->captain()) : null,
        ];
    }

    public static function collection(iterable $teams): array
    {
        return collect($teams)
            ->map(fn($team) => self::fromModel($team))
            ->toArray();
    }
}
