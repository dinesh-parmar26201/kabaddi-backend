<?php
namespace App\DTO;

use App\Models\MatchPlayer;
use App\DTO\User\UserResponseDTO;

class MatchPlayerResponseDTO
{
    public static function fromModel(MatchPlayer $mp): array
    {
        $userData = UserResponseDTO::fromModel($mp->user);

        return array_merge($userData, [
            'is_playing' => $mp->is_playing,
            'is_substitute' => $mp->is_substitute,
            'is_captain' => $mp->is_captain,
        ]);
    }

    public static function collection($players): array
    {
        return collect($players)
            ->map(fn ($mp) => self::fromModel($mp))
            ->values()
            ->toArray();
    }
}
