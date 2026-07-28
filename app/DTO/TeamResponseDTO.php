<?php

namespace App\DTO;

use App\Models\Team;
use App\DTO\User\UserResponseDTO;

class TeamResponseDTO
{
    public static function fromModel(Team $team, array $includes = []): array
    {
        $stats = $team->loadCount([
            'allPlayers as total_raiders' => function ($query) {
                $query->where('role', 'raider');
            },
            'allPlayers as total_defenders' => function ($query) {
                $query->where('role', 'defender');
            },
            'allPlayers as total_all_rounders' => function ($query) {
                $query->where('role', 'all-rounder');
            },
            'matches as total_matches_played' => function ($query) {
                $query->where('status', 'completed');
            },
            'matches as total_matches_won' => function ($query) use ($team) {
                $query->where('status', 'completed')
                    ->where('winner_team_id', $team->id);
            },
        ]);

        $played = $stats->total_matches_played ?? 0;
        $won = $stats->total_matches_won ?? 0;
        $stats->win_percentage = $played > 0 ? round(($won / $played) * 100, 2) : 0;

        $data = [
            'id'         => $team->id,
            'name'       => $team->name,
            'logo'       => $team->logo ? asset('storage/' . $team->logo) : null,
            'city'       => $team->city,
            'player_count' => $team->getPlayerCount(),
            'captain'    => $team->captain() ? UserResponseDTO::make($team->captain()) : null,
            'created_by' => $team->created_by ? UserResponseDTO::fromModel($team->creator) : null,
            'total_raiders' => $stats->total_raiders,
            'total_defenders' => $stats->total_defenders,
            'total_all_rounders' => $stats->total_all_rounders,
            'total_matches_played' => $stats->total_matches_played,
            'total_matches_won' => $stats->total_matches_won,
            'win_percentage' => $stats->win_percentage,
            'qr_code' => $team->qr_code ? asset('storage/' . $team->qr_code) : null,
        ];

        if (in_array('players', $includes)) {
            if (!$team->players()->exists()) {
                $data['players'] = [];
                return $data;
            }
            $data['players'] = UserResponseDTO::collection($team->players);
        }

        if (in_array('matches', $includes)) {
            if (!$team->matches()->exists()) {
                $data['matches'] = [];
                return $data;
            }
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
            ->map(fn($team) => self::fromModel($team))
            ->toArray();
    }
}
