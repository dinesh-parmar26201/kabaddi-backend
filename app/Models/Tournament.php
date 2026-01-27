<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $fillable = [
        'name',
        'banner',
        'organizer_name',
        'organizer_phone',
        'organizer_email',
        'ground',
        'city',
        'start_date',
        'end_date',
        'category',
        'status',
        'created_by',
    ];

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'tournament_teams');
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class, 'tournament_id');
    }
}
