<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaidTackler extends Model
{
    protected $fillable = [
        'raid_id',
        'user_id',
    ];
}
