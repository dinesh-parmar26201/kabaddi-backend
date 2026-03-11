<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaidDefender extends Model
{
    protected $fillable = [
        'raid_id',
        'user_id',
    ];

    public function raid()
    {
        return $this->belongsTo(Raid::class);
    }
}
