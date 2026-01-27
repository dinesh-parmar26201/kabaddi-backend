<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchPlayer extends Model
{
    protected $fillable = [
        'match_id',
        'team_id',
        'user_id',
        'is_playing',
        'is_substitute',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
