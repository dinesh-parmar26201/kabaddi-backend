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
            'banner' => $tournament->banner ? asset('storage/' . $tournament->banner) : null,
            'ground' => $tournament->ground,
            'organizer' => [
                'name' => $tournament->organizer_name,
                'phone' => $tournament->organizer_phone,
                'email' => $tournament->organizer_email,
            ],
            'city' => $tournament->city,
            'start_date' => $tournament->start_date,
            'end_date' => $tournament->end_date,
            'category' => $tournament->category,
            'status' => $tournament->status,
            'created_by' => UserResponseDTO::fromModel($tournament->creator),
            'teams' => $tournament->teams->map(function ($team) {
                return TeamResponseDTO::fromModel($team);
            })->toArray(),
        ];
    }

    public static function collection(iterable $tournaments): array
    {
        return collect($tournaments)
            ->map(fn($tournament) => self::fromModel($tournament))
            ->toArray();
    }
}
