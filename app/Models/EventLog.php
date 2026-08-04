<?php

namespace App\Models;

use App\DTO\RaidResponseDTO;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    protected $fillable = [
        'type',
        'team_id',
        'match_id',
        'raid_id',
        'half',
        'raid_number',
        'summary',
        'score_after_raid',
        'user_id',
        'card_type'
    ];

    protected $casts = [
        'type' => EventType::class,
        'score_after_raid' => 'array',
    ];

    protected $hidden = ['raid'];
    protected $appends = ['raid_dto'];

    public function raid()
    {
        return $this->belongsTo(Raid::class, 'raid_id', 'id');
    }

    public function getRaidDtoAttribute()
    {
        return $this->raid ? RaidResponseDTO::fromModel($this->raid) : null;
    }
}
