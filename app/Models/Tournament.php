<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $fillable = [
        'name',
        'gender',
        'type',
        'age_group',
        'banner',
        'organizer_name',
        'organizer_phone',
        'organizer_email',
        'ground',
        'city',
        'country',
        'state',
        'start_date',
        'end_date',
        'category',
        'status',
        'created_by',
        'qr_code'
    ];

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'tournament_teams');
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class, 'tournament_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
