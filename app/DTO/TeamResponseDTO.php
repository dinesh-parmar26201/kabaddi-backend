<?php

namespace App\DTO;

use App\Models\Team;
use App\DTO\User\UserResponseDTO;

class TeamResponseDTO
{
    public static function fromModel(Team $team, array $includes = []): array
    {
        $data = [
            'id'         => $team->id,
            'name'       => $team->name,
            'logo'       => $team->logo ? asset('storage/' . $team->logo) : null,
            'city'       => $team->city,
            'player_count' => $team->getPlayerCount(),
            'captain'    => $team->captain() ? UserResponseDTO::make($team->captain()) : null,
            'created_by' => $team->created_by ? UserResponseDTO::fromModel($team->creator) : null,
        ];

        if (in_array('players', $includes)) {
            $data['players'] = UserResponseDTO::collection($team->players);
        }

        if (in_array('matches', $includes)) {
            $data['matches'] = MatchResponseDTO::collection($team->matches);
        }

        return $data;
    }

    public static function collection(iterable $teams): array
    {
        return collect($teams)
            ->map(fn($team) => self::fromModel($team))
            ->toArray();
    }

    public static function make($team): array
    {
        return self::fromModel($team);
    }

    public static function fromModels($teams): array
    {
        return collect($teams)
            ->map(fn ($team) => self::fromModel($team))
            ->toArray();
    }
}
