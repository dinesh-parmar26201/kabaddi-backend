<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'name',
        'city',
        'logo',
        'created_by',
        'qr_code'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function players()
    {
        return $this->belongsToMany(User::class, 'team_user');
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_teams');
    }

    public function captain()
    {
        return $this->belongsToMany(User::class, 'team_user')->wherePivot('is_captain', true)->first();
    }

    public function getPlayerCount()
    {
        return $this->players()->count();
    }

    public function allPlayers()
    {
        return $this->belongsToMany(User::class, 'team_user');
    }

    public function matches()
    {
        return $this->belongsToMany(GameMatch::class, 'match_teams', 'team_id', 'match_id');
    }

    public function matchPlayers()
    {
        return $this->hasMany(MatchPlayer::class, 'team_id');
    }
}
