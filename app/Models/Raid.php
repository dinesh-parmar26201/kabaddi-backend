<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Raid extends Model
{
    protected $fillable = [
        'match_id',
        'raid_number',
        'half',
        'raid_team_id',
        'raider_id',
        'outcome',
        'bonus_point',
        'super_raid',
        'raider_lineout',
        'all_out',
        'technical_point_team_id',
    ];

    public function defenders()
    {
        return $this->hasMany(RaidDefender::class);
    }

    public function tacklers()
    {
        return $this->hasMany(RaidTackler::class);
    }
}
