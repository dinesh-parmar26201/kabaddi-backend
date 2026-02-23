<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    protected $fillable = [
        'match_id',
        'raid_id',
        'half',
        'raid_number',
        'summary',
        'score_after_raid',
        'notes',
    ];

    protected $casts = [
        'score_after_raid' => 'array',
    ];
}
