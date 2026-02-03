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
    ];

    protected $casts = [
        'is_captain' => 'boolean',
        'is_playing' => 'boolean',
        'is_substitute' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
