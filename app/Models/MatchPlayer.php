<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchPlayer extends Model
{
    protected $fillable = [
        'match_id',
        'team_id',
        'user_id',
        'is_captain',
        'is_playing',
        'is_substitute',
        'green_card',
        'yellow_card',
        'red_card',
    ];

    protected $casts = [
        'is_captain' => 'boolean',
        'is_playing' => 'boolean',
        'is_substitute' => 'boolean',
        'green_card' => 'integer',
        'yellow_card' => 'integer',
        'red_card' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function match()
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
