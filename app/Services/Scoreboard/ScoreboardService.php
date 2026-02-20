<?php
namespace App\Services\Scoreboard;

use App\Services\Scoreboard\ScoreboardServiceInterface;
use App\DTO\ScoreboardResponseDTO;
use App\DTO\TeamBreakdownDTO;
use App\DTO\PlayerStatsDTO;
use App\Models\GameMatch;

class ScoreboardService implements ScoreboardServiceInterface
{
    public function getMatchScoreboard(int $matchId): ScoreboardResponseDTO
    {
        $match = GameMatch::with([
            'raids.defenders',
            'raids.tacklers',
            'raids.defenderLineouts',
            'matchPlayers.user',
        ])->findOrFail($matchId);

        $teams = $match->teams; // assuming relation

        $teamBreakdowns = [];
        $playerStats = [];

        foreach ($teams as $team) {

            $teamRaidPoints = 0;
            $teamTacklePoints = 0;
            $teamAllOutPoints = 0;
            $teamExtraPoints = 0;

            foreach ($match->raids as $raid) {
                // dd($raid->toArray());

                if ($raid->raid_team_id == $team->team->id) {

                    $teamRaidPoints += $raid->defenders->count();
                    $teamExtraPoints += $raid->defenderLineouts->count();
                }

                if ($raid->defending_team_id == $team->team->id) {

                    $teamTacklePoints += $raid->tacklers->count();
                }

                if ($raid->is_all_out) {
                    $teamAllOutPoints += 2; // adjust if needed
                }
            }

            $total = $teamRaidPoints + $teamTacklePoints + $teamAllOutPoints + $teamExtraPoints;

            $teamBreakdowns[] = new TeamBreakdownDTO(
                teamId: $team->team->id,
                teamName: $team->team->name,
                raidPoints: $teamRaidPoints,
                tacklePoints: $teamTacklePoints,
                allOutPoints: $teamAllOutPoints,
                extraPoints: $teamExtraPoints,
                totalPoints: $total
            );

            // PLAYER STATS
            foreach ($match->matchPlayers->where('team_id', $team->team->id) as $matchPlayer) {

                $raidPts = 0;
                $tacklePts = 0;
                $superRaids = 0;
                $superTackles = 0;

                foreach ($match->raids as $raid) {

                    if ($raid->raider_id == $matchPlayer->id) {
                        $raidPts += $raid->defenders->count();
                        if ($raid->defenders->count() >= 3) {
                            $superRaids++;
                        }
                    }

                    if ($raid->tacklers && $raid->tacklers->pluck('user_id')->contains($matchPlayer->id)) {
                        $tacklePts++;
                        if ($raid->tacklers->count() >= 3) {
                            $superTackles++;
                        }
                    }
                }

                $playerStats[] = new PlayerStatsDTO(
                    playerId: $matchPlayer->id,
                    playerName: $matchPlayer->user->fullname,
                    teamId: $team->team->id,
                    raidPoints: $raidPts,
                    tacklePoints: $tacklePts,
                    superRaids: $superRaids,
                    superTackles: $superTackles,
                    totalPoints: $raidPts + $tacklePts
                );
            }
        }

        return new ScoreboardResponseDTO(
            teamBreakdowns: $teamBreakdowns,
            playerStats: $playerStats
        );
    }
}
