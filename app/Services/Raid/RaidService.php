<?php

namespace App\Services\Raid;

use App\Enums\MatchStatus;
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
            throw new Exception('Selected team is not part of this match.');
        }

        $validPlayers = $match->matchPlayers()
            ->where('team_id', $data['raid_team_id'])
            // ->where('is_playing', true)
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

        DB::transaction(function () use ($matchId, $data, $raidNumber, $match) {
            $teamIds = $match->teams->pluck('team_id');

            $defendingTeamId = $teamIds->first(function ($id) use ($data) {
                return $id != $data['raid_team_id'];
            });

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
                'all_out' => $data['all_out'] ?? false
            ]);

            // Save defenders
            foreach ($data['defenders'] ?? [] as $defender) {
                $raid->defenders()->create([
                    'raid_id' => $raid->id,
                    'user_id' => $defender,
                ]);

                if ($data['outcome'] == 'successful') {
                    $match->matchPlayers()
                        ->where('user_id', $defender)
                        ->update([
                            'is_playing' => false,
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($data['outcome'] == 'unsuccessful') {
                $match->matchPlayers()
                    ->where('user_id', $data['raider_id'])
                    ->update([
                        'is_playing' => false,
                        'updated_at' => now(),
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
                        'user_id' => $defenderId
                    ]);

                    // Player OUT
                    $match->matchPlayers()
                        ->where('user_id', $defenderId)
                        ->update([
                            'is_playing' => false,
                            'updated_at' => now(),
                        ]);
                }
            }
            if ($data['half'] == 1) {
                $match->status = MatchStatus::FIRST_HALF;
            } else if ($data['half'] == 2) {
                $match->status = MatchStatus::SECOND_HALF;
            }
            $match->save();

            // Calculate raid points
            $pointsEarned = 0;

            // 1 point per defender out
            $pointsEarned += count($data['defenders'] ?? []);
            $pointsEarned += count($data['defender_lineouts'] ?? []);

            // Bonus
            // if (!empty($data['bonus_point'])) {
            //     $pointsEarned += 1;
            // }

            // Super tackle gives defending team 2 points
            if (!empty($data['super_tackle'])) {

                // Raider OUT
                $match->matchPlayers()
                    ->where('user_id', $data['raider_id'])
                    ->update([
                        'is_playing' => false,
                        'updated_at' => now(),
                    ]);

                // Defending team revives 2 players
                $this->revivePlayers($match, $defendingTeamId, 2);
            }


            // Raider out (tackle)
            if (!empty($data['raider_lineout'])) {
                $pointsEarned = 0;

                $this->revivePlayers($match, $defendingTeamId, 1);
            }

            if ($pointsEarned > 0) {

                // Get OUT players of raid team (FIFO order)
                // $outPlayers = $match->matchPlayers()
                //     ->where('team_id', $data['raid_team_id'])
                //     ->where('is_playing', false)
                //     ->where('is_substitute', false)
                //     ->orderBy('updated_at', 'asc') // first out first revive
                //     ->take($pointsEarned)
                //     ->get();

                // foreach ($outPlayers as $player) {
                //     $player->is_playing = true;
                //     $player->is_substitute = false;
                //     $player->save();
                // }

                $this->revivePlayers($match, $data['raid_team_id'], $pointsEarned);
            }

            if (!empty($data['raider_lineout'])) {
                $match->matchPlayers()
                    ->where('user_id', $data['raider_id'])
                    ->update([
                        'is_playing' => false,
                        'updated_at' => now(),
                    ]);
            }

            $opponentTeamId = collect($match->teams)
                ->pluck('team_id')
                ->first(fn($id) => $id != $data['raid_team_id']);

            $remaining = $match->matchPlayers()
                ->where('team_id', $opponentTeamId)
                ->where('is_playing', true)
                ->count();

            if ($remaining == 0) {

                // Give 2 extra points (via scoreboard logic ideally)

                // Revive entire team
                $match->matchPlayers()
                    ->where('team_id', $opponentTeamId)
                    ->where('is_substitute', false)
                    ->update([
                        'is_playing' => true,
                        //'is_substitute' => false,
                        'updated_at' => now(),
                    ]);
            }

            if ($data['outcome'] == 'unsuccessful' && ($data['all_out'] ?? false)) {
                $match->matchPlayers()
                    ->where('team_id', $data['raid_team_id'])
                    ->where('is_substitute', false)
                    ->update([
                        'is_playing' => true,
                        //'is_substitute' => false,
                        'updated_at' => now(),
                    ]);
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
        $match = GameMatch::with(['teams', 'matchPlayers'])->findOrFail($matchId);

        $raid = Raid::where('match_id', $matchId)->findOrFail($raidId);

        DB::transaction(function () use ($matchId, $raidId, $data, $match, $raid) {

            $raidNumber = $raid->raid_number;

            // Update raid
            $raid->update([
                'raider_id' => $data['raider_id'],
                'outcome' => $data['outcome'],
                'bonus_point' => $data['bonus_point'] ?? false,
                'super_raid' => $data['super_raid'] ?? false,
                'super_tackle' => $data['super_tackle'] ?? false,
                'raider_lineout' => $data['raider_lineout'] ?? false,
                'all_out' => $data['all_out'] ?? false
            ]);

            // Delete old relations
            $raid->defenders()->delete();
            $raid->tacklers()->delete();
            $raid->defenderLineouts()->delete();

            // Save defenders
            foreach ($data['defenders'] ?? [] as $defender) {
                $raid->defenders()->create([
                    'raid_id' => $raid->id,
                    'user_id' => $defender,
                ]);
            }

            // Save tackler
            if (!empty($data['tackler'])) {
                $raid->tacklers()->create([
                    'raid_id' => $raid->id,
                    'user_id' => $data['tackler'],
                ]);
            }

            // Save defender lineouts
            if (!empty($data['defender_lineouts'])) {
                foreach ($data['defender_lineouts'] as $defenderId) {
                    $raid->defenderLineouts()->create([
                        'raid_id' => $raid->id,
                        'match_id' => $raid->match_id,
                        'defender_id' => $defenderId,
                        'user_id' => $defenderId
                    ]);
                }
            }

            /*
            RESET MATCH STATE
            */

            // $match->matchPlayers()->update([
            //     'is_playing' => true,
            //     'updated_at' => now(),
            // ]);

            /*
            REPLAY RAIDS
            */

            $raids = Raid::with(['defenders', 'tacklers', 'defenderLineouts'])
                ->where('match_id', $matchId)
                ->orderBy('raid_number')
                ->get();

            foreach ($raids as $r) {

                // Successful raid → defenders OUT
                if ($r->outcome == 'successful') {

                    foreach ($r->defenders as $defender) {

                        $match->matchPlayers()
                            ->where('user_id', $defender->user_id)
                            ->update([
                                'is_playing' => false,
                                'updated_at' => now()
                            ]);
                    }
                }

                // Defender lineouts
                foreach ($r->defenderLineouts as $lineout) {

                    $match->matchPlayers()
                        ->where('user_id', $lineout->user_id)
                        ->update([
                            'is_playing' => false,
                            'updated_at' => now()
                        ]);
                }

                // Raider OUT
                if ($r->outcome == 'unsuccessful' || $r->raider_lineout) {

                    $match->matchPlayers()
                        ->where('user_id', $r->raider_id)
                        ->update([
                            'is_playing' => false,
                            'updated_at' => now()
                        ]);
                }
            }

            /*
            UPDATE SCOREBOARD
            */

            $scoreService = app(ScoreboardServiceInterface::class);
            $scoreboard = $scoreService->getMatchScoreboard($match->id);

            $raid->eventLog()->update([
                'summary' => $data['event_summary'] ?? '',
                'score_after_raid' => $scoreboard->teamBreakdowns ?? []
            ]);
        });

        return $raid->load(['defenders', 'tacklers', 'defenderLineouts']);
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
        $match = GameMatch::findOrFail($matchId);
        $lastRaid = Raid::where('match_id', $matchId)
            ->latest('raid_number')
            ->first();

        if ($lastRaid) {
            if ($lastRaid->half == 1) {
                $match->status = MatchStatus::FIRST_HALF;
            } else if ($lastRaid->half == 2) {
                $match->status = MatchStatus::SECOND_HALF;
            }
        } else {
            $match->status = MatchStatus::UPCOMING;
        }
        $match->save();
    }

    public function skip(int $matchId, array $data): Raid
    {
        $match = GameMatch::with(['teams', 'matchPlayers'])->findOrFail($matchId);

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

    private function revivePlayers($match, $teamId, $count)
    {
        $outPlayers = $match->matchPlayers()
            ->where('team_id', $teamId)
            ->where('is_playing', false)
            ->where('is_substitute', false)
            ->orderBy('updated_at', 'asc')
            ->take($count)
            ->get();

        foreach ($outPlayers as $player) {
            $player->update([
                'is_playing' => true
                //, 'is_substitute' => false
            ]);
        }
    }
}
