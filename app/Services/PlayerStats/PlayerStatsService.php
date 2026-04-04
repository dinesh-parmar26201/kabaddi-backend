<?php

namespace App\Services\PlayerStats;
use App\Models\Raid;
use App\Models\RaidDefender;
use App\Models\RaidTackler;
use App\Models\MatchPlayer;

class PlayerStatsService implements PlayerStatsServiceInterface
{
    public function getPlayerStats(): array
    {
        $playerId = auth()->id();
        // Total matches played
        $totalMatches = MatchPlayer::where('user_id', $playerId)->count();

        // Raids done by player
        $raids = Raid::where('raider_id', $playerId);

        $totalRaids = $raids->count();

        // Raid Points (touch points)
        $raidPoints = RaidDefender::whereHas('raid', function ($q) use ($playerId) {
            $q->where('raider_id', $playerId);  
        })->count();

        // Bonus points
        $bonusPoints = Raid::where('raider_id', $playerId)
            ->where('bonus_point', true)
            ->count();

        // Super raids
        $superRaids = Raid::where('raider_id', $playerId)
            ->where('super_raid', true)
            ->count();

        // Empty raids
        $emptyRaids = Raid::where('raider_id', $playerId)
            ->where('outcome', 'empty')
            ->count();

        // Successful raids
        $successfulRaids = Raid::where('raider_id', $playerId)
            ->where('outcome', 'successful')
            ->count();

        // Unsuccessful raids
        $unsuccessfulRaids = Raid::where('raider_id', $playerId)
            ->where('outcome', 'unsuccessful')
            ->count();

        // Tackle points
        $tacklePoints = RaidTackler::where('user_id', $playerId)->count();

        // Super tackles
        $superTackles = Raid::where('super_tackle', true)
            ->whereHas('tacklers', function ($q) use ($playerId) {
                $q->where('user_id', $playerId);
            })
            ->count();

        // Successful tackles
        $successfulTackles = RaidTackler::where('user_id', $playerId)->count();

        // Unsuccessful tackles
        $unsuccessfulTackles = RaidDefender::where('user_id', $playerId)->count();

        // Cards
        $greenCards = MatchPlayer::where('user_id', $playerId)->where('green_card', true)->count();
        $yellowCards = MatchPlayer::where('user_id', $playerId)->where('yellow_card', true)->count();
        $redCards = MatchPlayer::where('user_id', $playerId)->where('red_card', true)->count();

        return [
            'total_matches' => $totalMatches,
            'total_points' => $raidPoints + $tacklePoints + $bonusPoints + $superTackles,
            'total_raid_points' => $raidPoints + $bonusPoints,
            'total_tackle_points' => $tacklePoints + $superTackles,

            'raid_stats' => [
                'touch_points' => $raidPoints,
                'bonus_points' => $bonusPoints,
                'super_raids' => $superRaids,
                'empty_raids' => $emptyRaids,
                'successful_raids' => $successfulRaids,
                'unsuccessful_raids' => $unsuccessfulRaids,
                'total_lifetime_raids' => $totalRaids,
            ],

            'tackle_stats' => [
                'tackle_points' => $tacklePoints,
                'super_tackle' => $superTackles,
                'successful_tackle' => $successfulTackles,
                'unsuccessful_tackle' => $unsuccessfulTackles,
                'total_lifetime_tackles' => $successfulTackles + $unsuccessfulTackles
            ],
            
            'card_stats' => [
                'green_cards' => $greenCards,
                'yellow_cards' => $yellowCards,
                'red_cards' => $redCards,
            ]
        ];
    }
}
