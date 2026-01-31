<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchTeam extends Model
{
    protected $fillable = [
        'match_id',
        'team_id',
        'tshirt_color',
    ];

    public function match()
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
