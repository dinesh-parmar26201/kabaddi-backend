<?php

namespace App\DTO;

use App\Models\Tournament;

class TournamentResponseDTO
{
    public static function fromModel(Tournament $tournament): array
    {
        return [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'banner' => $tournament->banner ? asset('storage/' . $tournament->banner) : null,
            'ground' => $tournament->ground,
            'organizer_name' => $tournament->organizer_name,
            'organizer_phone' => $tournament->organizer_phone,
            'organizer_email' => $tournament->organizer_email,
            'city' => $tournament->city,
            'start_date' => $tournament->start_date,
            'end_date' => $tournament->end_date,
            'status' => $tournament->status,
        ];
    }

    public static function collection(iterable $tournaments): array
    {
        return collect($tournaments)
            ->map(fn($tournament) => self::fromModel($tournament))
            ->toArray();
    }
}
