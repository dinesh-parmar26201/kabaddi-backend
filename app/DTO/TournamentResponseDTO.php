<?php

namespace App\DTO;

use App\Models\Tournament;
use App\DTO\User\UserResponseDTO;

class TournamentResponseDTO
{
    public static function fromModel(Tournament $tournament): array
    {
        return [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'gender' => $tournament->gender,
            'type' => $tournament->type,
            'age_group' => $tournament->age_group,
            'banner' => $tournament->banner ? asset('storage/' . $tournament->banner) : null,
            'ground' => $tournament->ground,
            'organizer' => [
                'name' => $tournament->organizer_name,
                'phone' => $tournament->organizer_phone,
                'email' => $tournament->organizer_email,
            ],
            'country' => $tournament->country,
            'state' => $tournament->state,
            'city' => $tournament->city,
            'start_date' => $tournament->start_date,
            'end_date' => $tournament->end_date,
            'category' => $tournament->category,
            'status' => $tournament->status,
            'created_by' => UserResponseDTO::fromModel($tournament->creator),
            'teams' => $tournament->teams->map(function ($team) {
                return TeamResponseDTO::fromModel($team);
            })->toArray(),
            'matches_count' => $tournament->matches()->count(),
            'teams_count' => $tournament->teams()->count(),
            'players_count' => $tournament->teams->sum(function ($team) {
                return $team->players()->count();
            }),
        ];
    }

    public static function collection(iterable $tournaments): array
    {
        return collect($tournaments)
            ->map(fn($tournament) => self::fromModel($tournament))
            ->toArray();
    }
}
