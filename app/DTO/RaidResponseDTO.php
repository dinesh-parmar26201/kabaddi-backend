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
            'super_tackle' => (bool) $raid->super_tackle,
            'raider_lineout' => (bool) $raid->raider_lineout,
            'all_out' => (bool) $raid->all_out,
            'technical_point_team_id' => $raid->technical_point_team_id,

            'defenders' => $raid->defenders->pluck('user_id')->values(),
            'tacklers' => $raid->tacklers ? (int) $raid->tacklers->user_id : null,
            'defender_lineouts' => $raid->defenderLineouts->pluck('defender_id')->values(),
            'event_summary' => $raid->eventLog ? $raid->eventLog->summary : null,
            'score_after_raid' => $raid->eventLog ? $raid->eventLog->score_after_raid : null,
        ];
    }
}
