<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'title',
        'tournament_id',
        'tournament_match_no',
        'team_a_id',
        'team_b_id',
        'start_date',
        'start_time',
        'end_time',
        'venue',
        'ground_name',
        'organizer_phone',
        'organizer_email',
        'toss_winner_team_id',
        'toss_decision',
        'status',
    ];

    public function teams()
    {
        return $this->hasMany(MatchTeam::class, 'match_id')->with('team');
    }

    public function players()
    {
        return $this->hasMany(MatchPlayer::class, 'match_id');
    }

    public function raids()
    {
        return $this->hasMany(Raid::class, 'match_id');
    }

    public function matchPlayers()
    {
        return $this->hasMany(MatchPlayer::class, 'match_id');
    }

    public function events()
    {
        return $this->hasMany(EventLog::class, 'match_id');
    }
}
