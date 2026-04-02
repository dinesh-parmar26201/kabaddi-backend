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
        'super_tackle',
        'raider_lineout',
        'all_out'
    ];

    protected $casts = [
        'bonus_point' => 'boolean',
        'super_raid' => 'boolean',
        'super_tackle' => 'boolean',
        'raider_lineout' => 'boolean',
        'all_out' => 'boolean',
        'raid_number' => 'integer',
    ];

    public function defenders()
    {
        return $this->hasMany(RaidDefender::class);
    }

    public function tacklers()
    {
        return $this->hasOne(RaidTackler::class);
    }

    public function defenderLineouts()
    {
        return $this->hasMany(RaidDefenderLineout::class);
    }

    public function eventLog()
    {
        return $this->hasOne(EventLog::class);
    }

    // Accessor for emptyRaids attribute
    public function getEmptyRaidsAttribute()
    {
        // Get all raids for the current match, ordered by latest created date
        $raids = self::where('match_id', $this->match_id)
            ->orderByDesc('created_at')
            ->get();

        $count = 0;
        foreach ($raids as $raid) {
            if ($raid->outcome === 'empty') {
                $count++;
            } else {
                break;
            }
        }
        return $count;
    }

}
