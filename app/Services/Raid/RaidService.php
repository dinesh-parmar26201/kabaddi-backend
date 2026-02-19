<?php

namespace App\Services\Raid;

use App\Models\GameMatch;
use App\Models\Raid;
use Illuminate\Support\Facades\DB;

class RaidService implements RaidServiceInterface
{
    public function store(int $matchId, array $data): Raid
    {
        $match = GameMatch::with(['teams', 'matchPlayers'])->findOrFail($matchId);
        $matchTeamIds = $match->teams()->pluck('team_id')->toArray();

        if (!in_array($data['raid_team_id'], $matchTeamIds)) {
            throw new \Exception('Selected team is not part of this match.');
        }

        $validPlayers = $match->matchPlayers()
            ->where('team_id', $data['raid_team_id'])
            ->where('is_playing', true)
            ->pluck('user_id')
            ->toArray();

        if (!in_array($data['raider_id'], $validPlayers)) {
            throw new \Exception('Raider is not a playing member of this team.');
        }

        $lastRaid = Raid::where('match_id', $matchId)
            ->latest('raid_number')
            ->first();

        if ($lastRaid && $lastRaid->raid_team_id == $data['raid_team_id']) {
            throw new \Exception('Raid must alternate between teams.');
        }

        // if ($lastRaid && $data['half'] < $lastRaid->half) {
        //     throw new \Exception('Half cannot go backwards.');
        // }

        // $outs = collect($data['defenders'] ?? [])
        //     ->where('is_out', true)
        //     ->count();

        // if (($data['super_raid'] ?? false) && $outs < 3) {
        //     throw new \Exception('Super raid requires at least 3 defenders out.');
        // }


        return DB::transaction(function () use ($matchId, $data) {

            $raidNumber = Raid::where('match_id', $matchId)
                ->max('raid_number') + 1;

            $raid = Raid::create([
                'match_id' => $matchId,
                'raid_number' => $raidNumber,
                'half' => $data['half'],
                'raid_team_id' => $data['raid_team_id'],
                'raider_id' => $data['raider_id'],
                'outcome' => $data['outcome'],
                'bonus_point' => $data['bonus_point'] ?? false,
                'super_raid' => $data['super_raid'] ?? false,
                'raider_lineout' => $data['raider_lineout'] ?? false,
                'all_out' => $data['all_out'] ?? false,
                'technical_point_team_id' => $data['technical_point_team_id'] ?? null,
            ]);

            // Save defenders
            foreach ($data['defenders'] ?? [] as $defender) {
                $raid->defenders()->create([
                    'raid_id' => $raid->id,
                    'user_id' => $defender,
                ]);
            }

            // Save tacklers
            foreach ($data['tacklers'] ?? [] as $tackler) {
                $raid->tacklers()->create([
                    'raid_id' => $raid->id,
                    'user_id' => $tackler,
                ]);
            }

            return $raid->load(['defenders', 'tacklers']);
        });
    }

    public function update(int $matchId, int $raidId, array $data): Raid
    {
        return DB::transaction(function () use ($matchId, $raidId, $data) {

            $raid = Raid::where('match_id', $matchId)
                ->findOrFail($raidId);

            $raid->update($data);

            $raid->defenders()->delete();
            $raid->tacklers()->delete();

             // Save defenders
            foreach ($data['defenders'] ?? [] as $defender) {
                $raid->defenders()->create([
                    'raid_id' => $raid->id,
                    'user_id' => $defender,
                ]);
            }

            // Save tacklers
            foreach ($data['tacklers'] ?? [] as $tackler) {
                $raid->tacklers()->create([
                    'raid_id' => $raid->id,
                    'user_id' => $tackler,
                ]);
            }

            return $raid->load(['defenders', 'tacklers']);
        });
    }

    public function undoLastRaid(int $matchId): void
    {
        DB::transaction(function () use ($matchId) {

            $lastRaid = Raid::where('match_id', $matchId)
                ->latest('raid_number')
                ->firstOrFail();

            $lastRaid->defenders()->delete();
            $lastRaid->tacklers()->delete();
            $lastRaid->delete();
        });
    }
}
