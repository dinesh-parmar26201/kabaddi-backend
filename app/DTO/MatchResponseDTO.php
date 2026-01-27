<?php

namespace App\DTO;

use App\Models\GameMatch;

class MatchResponseDTO
{
    public static function fromModel(GameMatch $match): array
    {
        return [
            'id' => $match->id,
            'tournament_id' => $match->tournament_id,
            'team_a_id' => $match->team_a_id,
            'team_b_id' => $match->team_b_id,
            'start_date' => $match->start_date,
            'start_time' => $match->start_time,
            'end_time' => $match->end_time,
            'status' => $match->status,
            'teams' => $match->teams->map(fn ($team) => [
                'team_id' => $team->team_id,
                'tshirt_color' => $team->tshirt_color,
            ])->toArray(),
        ];
    }
}
