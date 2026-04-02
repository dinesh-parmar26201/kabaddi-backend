<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaidDefenderLineout extends Model
{
    protected $fillable = [
        'raid_id',
        'match_id',
        'defender_id'
    ];

    public function raid()
    {
        return $this->belongsTo(Raid::class);
    }

    public function match()
    {
        return $this->belongsTo(GameMatch::class);
    }

    public function defender()
    {
        return $this->belongsTo(MatchPlayer::class, 'defender_id');
    }
}
