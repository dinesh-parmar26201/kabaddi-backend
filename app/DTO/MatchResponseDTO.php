<?php

namespace App\DTO;

use App\Models\GameMatch;

class MatchResponseDTO
{
    public static function fromModel(GameMatch $match, array $includes = []): array
    {
        $data = [
            'id' => $match->id,
            'tournament_id' => $match->tournament_id,
            'start_date' => $match->start_date,
            'start_time' => $match->start_time,
            'end_time' => $match->end_time,
            'venue' => $match->venue,
            'ground_name' => $match->ground_name,
            'organizer' => [
                'phone' => $match->organizer_phone,
                'email' => $match->organizer_email,
            ],
            'status' => $match->status,
            'toss_winner_team_id' => $match->toss_winner_team_id,
            'toss_decision' => $match->toss_decision,
        ];

        if (in_array('teams', $includes)) {
            $data['teams'] = self::teams($match);
        }
        return $data;
    }

    public static function teams(GameMatch $match)
    {
        return $match->teams->map(function ($team) {
            return TeamResponseDTO::fromModel($team->team);
        });
    }
}
