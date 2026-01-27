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
}
