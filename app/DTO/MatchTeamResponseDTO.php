<?php

namespace App\DTO;

use App\Models\Team;
use App\Models\GameMatch;

class MatchTeamResponseDTO
{
    public static function fromModel(Team $team, GameMatch $match, $teamMatchData = null): array
    {
        $teamData = TeamResponseDTO::fromModel($team);
        unset($teamData['player_count']);

        return array_merge($teamData, [
            'court_side' => $teamMatchData ? $teamMatchData->court_side : null,
            'players' => MatchPlayerResponseDTO::collection(
                $team->matchPlayers
                    ->where('match_id', $match->id)
            ),
        ]);
    }

    public static function collection($teams, GameMatch $match): array
    {
        return collect($teams)
            ->map(fn($team) => self::fromModel($team, $match))
            ->toArray();
    }
}
