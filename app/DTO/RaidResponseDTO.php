<?php

namespace App\DTO;

use App\Models\Raid;

class RaidResponseDTO
{
    public static function fromModel(Raid $raid): array
    {
        return [
            'id' => $raid->id,
            'match_id' => $raid->match_id,
            'raid_number' => $raid->raid_number,
            'half' => $raid->half,

            'raid_team_id' => $raid->raid_team_id,
            'raider_id' => $raid->raider_id,

            'outcome' => $raid->outcome,
            'bonus_point' => (bool) $raid->bonus_point,
            'super_raid' => (bool) $raid->super_raid,
            'raider_lineout' => (bool) $raid->raider_lineout,
            'all_out' => (bool) $raid->all_out,
            'technical_point_team_id' => $raid->technical_point_team_id,

            'defenders' => $raid->defenders->pluck('user_id')->values(),

            'tacklers' => $raid->tacklers->map(function ($t) {
                return [
                    'player_id' => $t->player_id,
                    'points' => $t->points,
                ];
            })->values(),
        ];
    }
}
