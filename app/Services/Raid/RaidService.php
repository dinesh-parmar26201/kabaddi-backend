<?php

namespace App\Services\Raid;

use App\Models\EventLog;
use App\Models\GameMatch;
use App\Models\Raid;
use App\Services\Scoreboard\ScoreboardServiceInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class RaidService implements RaidServiceInterface
{
    public function getRaidsByMatch(int $matchId)
    {
        return Raid::with(['defenders', 'tacklers', 'defenderLineouts'])
            ->where('match_id', $matchId)
            ->orderBy('raid_number', 'asc')
            ->get();
    }

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
            throw new Exception('Raider is not a playing member of this team.', 200);
        }

        // $lastRaid = Raid::where('match_id', $matchId)
        //     ->latest('raid_number')
        //     ->first();

        // if ($lastRaid && $lastRaid->raid_team_id == $data['raid_team_id']) {
        //     throw new \Exception('Raid must alternate between teams.');
        // }

        // if ($lastRaid && $data['half'] < $lastRaid->half) {
        //     throw new \Exception('Half cannot go backwards.');
        // }

        // $outs = collect($data['defenders'] ?? [])
        //     ->where('is_out', true)
        //     ->count();

        // if (($data['super_raid'] ?? false) && $outs < 3) {
        //     throw new \Exception('Super raid requires at least 3 defenders out.');
        // }

        $raidNumber = Raid::query()->where('match_id', $matchId)
            ->orderBy('raid_number', 'desc')
            ->value('raid_number') ?? 0;
        $raidNumber += 1;

        DB::transaction(function () use ($matchId, $data, $raidNumber) {

            $raid = Raid::create([
                'match_id' => $matchId,
                'raid_number' => $raidNumber,
                'half' => $data['half'],
                'raid_team_id' => $data['raid_team_id'],
                'raider_id' => $data['raider_id'],
                'outcome' => $data['outcome'],
                'bonus_point' => $data['bonus_point'] ?? false,
                'super_raid' => $data['super_raid'] ?? false,
                'super_tackle' => $data['super_tackle'] ?? false,
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
            // foreach ($data['tacklers'] ?? [] as $tackler) {
            if (isset($data['tackler'])) {
                $raid->tacklers()->create([
                    'raid_id' => $raid->id,
                    'user_id' => $data['tackler'],
                ]);
            }
            // }

            // Store defender lineouts
            if (!empty($data['defender_lineouts'])) {
                foreach ($data['defender_lineouts'] as $defenderId) {
                    $raid->defenderLineouts()->create([
                        'raid_id' => $raid->id,
                        'match_id' => $raid->match_id,
                        'defender_id' => $defenderId,
                    ]);
                }
            }
        });
        $raid = Raid::with(['defenders', 'tacklers', 'defenderLineouts'])
            ->where('match_id', $matchId)
            ->where('raid_number', $raidNumber)
            ->first();

        $scoreService = app(ScoreboardServiceInterface::class);
        $scoreboard = $scoreService->getMatchScoreboard($match->id);
        $data['teamBreakdowns'] = $scoreboard->teamBreakdowns ?? [];

        EventLog::create([
            'match_id' => $matchId,
            'raid_id' => $raid->id,
            'half' => $data['half'],
            'raid_number' => $raidNumber,
            'summary' => $data['event_summary'],
            'score_after_raid' => $data['teamBreakdowns'] ?? [],
        ]);

        return $raid->load(['defenders', 'tacklers', 'defenderLineouts']);
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
            // foreach ($data['tacklers'] ?? [] as $tackler) {
            $raid->tacklers()->create([
                'raid_id' => $raid->id,
                'user_id' => $data['tackler'] ?? null,
            ]);
            // }

            // Store defender lineouts
            if (!empty($data['defender_lineouts'])) {
                foreach ($data['defender_lineouts'] as $defenderId) {
                    $raid->defenderLineouts()->create([
                        'raid_id' => $raid->id,
                        'match_id' => $raid->match_id,
                        'defender_id' => $defenderId,
                    ]);
                }
            }

            return $raid->load(['defenders', 'tacklers', 'defenderLineouts']);
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

    public function skip(int $matchId, array $data): Raid
    {
        $match = GameMatch::with(['teams', 'matchPlayers'])->findOrFail($matchId);

        $raidNumber = Raid::query()->where('match_id', $matchId)
            ->orderBy('raid_number', 'desc')
            ->value('raid_number') ?? 0;
        $raidNumber += 1;

        $raid = Raid::with(['defenders', 'tacklers', 'defenderLineouts'])
            ->where('match_id', $matchId)
            ->where('raid_number', $raidNumber)
            ->first();

        DB::transaction(function () use ($matchId, $data, $raidNumber) {

            $raid = Raid::create([
                'match_id' => $matchId,
                'raid_number' => $raidNumber,
                'half' => $data['half'],
                'raid_team_id' => $data['raid_team_id'],
                'outcome' => "skipped",
            ]);
        });
        
        $raid = Raid::with(['defenders', 'tacklers', 'defenderLineouts'])
            ->where('match_id', $matchId)
            ->where('raid_number', $raidNumber)
            ->first();

        $scoreService = app(ScoreboardServiceInterface::class);
        $scoreboard = $scoreService->getMatchScoreboard($match->id);
        $data['teamBreakdowns'] = $scoreboard->teamBreakdowns ?? [];

        EventLog::create([
            'match_id' => $matchId,
            'raid_id' => $raid->id,
            'half' => $data['half'],
            'raid_number' => $raidNumber,
            'summary' => $data['event_summary'],
            'score_after_raid' => $data['teamBreakdowns'] ?? [],
        ]);
        return $raid->load(['defenders', 'tacklers', 'defenderLineouts']);
    }
}
