<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'created_by',
        'winner_team_id',
    ];

    protected $casts = [
        'status' => MatchStatus::class,
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

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value instanceof MatchStatus
                ? $value->value
                : $value
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getEmptyRaidsCount(int $teamId): int
    {
        // Find the latest raid performed by this team
        $latestRaid = $this->raids()
            ->where('raid_team_id', $teamId)
            ->latest()
            ->first();

        // The Raid model has an 'empty_raids' accessor that counts consecutive empty raids
        return $latestRaid ? $latestRaid->empty_raids : 0;
    }
}
