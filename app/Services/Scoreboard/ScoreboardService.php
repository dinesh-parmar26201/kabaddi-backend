<?php

namespace App\Services\Scoreboard;

use App\Services\Scoreboard\ScoreboardServiceInterface;
use App\DTO\ScoreboardResponseDTO;
use App\DTO\TeamBreakdownDTO;
use App\DTO\PlayerStatsDTO;
use App\Enums\EventType;
use App\Models\GameMatch;

class ScoreboardService implements ScoreboardServiceInterface
{
    public function getMatchScoreboard(int $matchId): ScoreboardResponseDTO
    {
        $match = GameMatch::with([
            'teams.team',
            'raids.defenders',
            'raids.tacklers',
            'raids.defenderLineouts',
            'matchPlayers.user',
        ])->findOrFail($matchId);

        $teamAId = $match->team_a_id;
        $teamBId = $match->team_b_id;

        /*
        |--------------------------------------------------------------------------
        | TEAM MAP
        |--------------------------------------------------------------------------
        */
        $teamsMap = [
            $teamAId => [
                'id' => $teamAId,
                'name' => $match->teams->firstWhere('team.id', $teamAId)?->team->name,
                'raidPoints' => 0,
                'tacklePoints' => 0,
                'allOutPoints' => 0,
                'extraPoints' => 0,
            ],
            $teamBId => [
                'id' => $teamBId,
                'name' => $match->teams->firstWhere('team.id', $teamBId)?->team->name,
                'raidPoints' => 0,
                'tacklePoints' => 0,
                'allOutPoints' => 0,
                'extraPoints' => 0,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | PLAYER MAP
        |--------------------------------------------------------------------------
        */
        $playerStatsMap = [];

        foreach ($match->matchPlayers as $player) {
            $playerStatsMap[$player->user_id] = [
                'playerId' => $player->user_id,
                'playerName' => $player->user->fullname,
                'teamId' => $player->team_id,
                'raidPoints' => 0,
                'tacklePoints' => 0,
                'bonusPoints' => 0,
                'superRaids' => 0,
                'superTackles' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PROCESS RAIDS
        |--------------------------------------------------------------------------
        */
        foreach ($match->raids as $raid) {

            $raidingTeamId   = $raid->raid_team_id;
            $defendingTeamId = $raidingTeamId === $teamAId ? $teamBId : $teamAId;

            /*
            |--------------------------------------------------------------------------
            | SUCCESSFUL RAID
            |--------------------------------------------------------------------------
            */
            if ($raid->outcome === 'successful') {

                // Defender out → raider + team
                $defenderCount = $raid->defenders->count();

                if ($defenderCount > 0 && isset($playerStatsMap[$raid->raider_id])) {
                    $playerStatsMap[$raid->raider_id]['raidPoints'] += $defenderCount;
                    $teamsMap[$raidingTeamId]['raidPoints'] += $defenderCount;
                }

                // Bonus → raider + team (extra)
                if ($raid->bonus_point && isset($playerStatsMap[$raid->raider_id])) {
                    $playerStatsMap[$raid->raider_id]['bonusPoints'] += 1;
                    $teamsMap[$raidingTeamId]['extraPoints'] += 1;
                }

                // Super raid → count only
                if ($raid->super_raid && isset($playerStatsMap[$raid->raider_id])) {
                    $playerStatsMap[$raid->raider_id]['superRaids'] += 1;
                }

                // Raider caught → defending team only
                if ($raid->tacklers && $raid->tacklers->count() > 0) {
                    $teamsMap[$defendingTeamId]['tacklePoints'] += 1;
                }

                // All out → raiding team only
                if ($raid->all_out) {
                    $teamsMap[$raidingTeamId]['allOutPoints'] += 2;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UNSUCCESSFUL RAID
            |--------------------------------------------------------------------------
            */
            if ($raid->outcome === 'unsuccessful') {

                // Raider out → tackler player + team
                if ($raid->tacklers && $raid->tacklers->count() > 0) {

                    foreach ($raid->tacklers as $tackler) {
                        if (isset($playerStatsMap[$tackler->user_id])) {
                            $playerStatsMap[$tackler->user_id]['tacklePoints'] += 1;
                        }
                    }

                    $teamsMap[$defendingTeamId]['tacklePoints'] += 1;
                }

                // Bonus → raider + team
                if ($raid->bonus_point && isset($playerStatsMap[$raid->raider_id])) {
                    $playerStatsMap[$raid->raider_id]['bonusPoints'] += 1;
                    $teamsMap[$raidingTeamId]['extraPoints'] += 1;
                }

                // Super tackle → tackler + team (2 points)
                if ($raid->super_tackle && $raid->tacklers && $raid->tacklers->count() > 0) {

                    foreach ($raid->tacklers as $tackler) {
                        if (isset($playerStatsMap[$tackler->user_id])) {
                            $playerStatsMap[$tackler->user_id]['tacklePoints'] += 2;
                            $playerStatsMap[$tackler->user_id]['superTackles'] += 1;
                        }
                    }

                    $teamsMap[$defendingTeamId]['tacklePoints'] += 2;
                }

                // Raider lineout → no points
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BUILD TEAM DTOs
        |--------------------------------------------------------------------------
        */
        $teamBreakdowns = [];

        foreach ($teamsMap as $team) {
            $total =
                $team['raidPoints']
                + $team['tacklePoints']
                + $team['allOutPoints']
                + $team['extraPoints'];

            $teamBreakdowns[] = new TeamBreakdownDTO(
                teamId: $team['id'],
                teamName: $team['name'],
                raidPoints: $team['raidPoints'],
                tacklePoints: $team['tacklePoints'],
                allOutPoints: $team['allOutPoints'],
                extraPoints: $team['extraPoints'],
                totalPoints: $total
            );
        }

        /*
        |--------------------------------------------------------------------------
        | BUILD PLAYER DTOs
        |--------------------------------------------------------------------------
        */
        $playerStats = [];

        foreach ($playerStatsMap as $player) {
            $playerStats[] = new PlayerStatsDTO(
                playerId: $player['playerId'],
                playerName: $player['playerName'],
                teamId: $player['teamId'],
                raidPoints: $player['raidPoints'],
                tacklePoints: $player['tacklePoints'],
                superRaids: $player['superRaids'],
                superTackles: $player['superTackles'],
                totalPoints:
                    $player['raidPoints']
                    + $player['tacklePoints']
                    + $player['bonusPoints']
            );
        }

        return new ScoreboardResponseDTO(
            teamBreakdowns: $teamBreakdowns,
            playerStats: $playerStats
        );
    }
}
