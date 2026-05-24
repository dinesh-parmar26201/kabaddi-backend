<?php

namespace App\DTO;

use App\DTO\User\UserResponseDTO;
use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Services\Scoreboard\ScoreboardServiceInterface;

class MatchResponseDTO
{
    public static function fromModel(GameMatch $match, array $includes = []): array
    {
        $data = [
            'id' => $match->id,
            'title' => $match->title,
            'tournament_id' => $match->tournament_id,
            'tournament_match_no' => $match->tournament_match_no,
            'start_date' => $match->start_date,
            'start_time' => $match->start_time,
            'end_time' => $match->end_time,
            'venue' => $match->venue,
            'ground_name' => $match->ground_name,
            'organizer' => [
                'phone' => $match->organizer_phone,
                'email' => $match->organizer_email,
            ],
            'status' => MatchStatus::from($match->status)->label(),
            'toss_winner_team_id' => $match->toss_winner_team_id,
            'toss_decision' => $match->toss_decision,
            'stage' => $match->stage,
            'current raid' => $match->raids()->latest()->first() ? RaidResponseDTO::fromModel($match->raids()->latest()->first()) : null,
            'created_by' => $match->created_by ? UserResponseDTO::fromModel($match->creator) : null,
        ];

        if (in_array('teams', $includes)) {
            $data['teams'] = self::teams($match);
        }

        if (in_array('raids', $includes)) {
            $data['raids'] = self::raids($match);
        }

        if (in_array('teamBreakdowns', $includes)) {
            $scoreService = app(ScoreboardServiceInterface::class);
            $scoreboard = $scoreService->getMatchScoreboard($match->id);
            $data['teamBreakdowns'] = $scoreboard->teamBreakdowns ?? [];
        }

        return $data;
    }

    public static function teams(GameMatch $match)
    {

        return $match->teams->map(function ($team) use ($match) {
            return MatchTeamResponseDTO::fromModel($team->team, $match, $team);
        });
    }

    public static function fromModels($matches, array $includes = []): array
    {
        return $matches->map(function ($match) use ($includes) {
            return self::fromModel($match, $includes);
        })->toArray();
    }

    public static function collection(iterable $matches, array $includes = []): array
    {
        return collect($matches)
            ->map(fn($match) => self::fromModel($match, $includes))
            ->toArray();
    }

    public static function raids(GameMatch $match)
    {

        return $match->raids->map(function ($raid) use ($match) {
            return RaidResponseDTO::fromModel($raid);
        });
    }
}
