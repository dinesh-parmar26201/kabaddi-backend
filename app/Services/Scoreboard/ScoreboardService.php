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
                'points' => 0,
            ],
            $teamBId => [
                'id' => $teamBId,
                'name' => $match->teams->firstWhere('team.id', $teamBId)?->team->name,
                'points' => 0,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | PLAYER MAP (key = user_id)
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
            $defendingTeamId = $raidingTeamId == $teamAId ? $teamBId : $teamAId;

            /*
            |--------------------------------------------------------------------------
            | SUCCESSFUL RAID
            |--------------------------------------------------------------------------
            */
            if ($raid->outcome === 'successful') {

                // Defender out → team + raider
                $defenderCount = $raid->defenders->count();

                if ($defenderCount > 0) {
                    $teamsMap[$raidingTeamId]['points'] += $defenderCount;

                    if (isset($playerStatsMap[$raid->raider_id])) {
                        $playerStatsMap[$raid->raider_id]['raidPoints'] += $defenderCount;
                    }
                }

                // Bonus → raider only (separate stat)
                if ($raid->bonus_point && isset($playerStatsMap[$raid->raider_id])) {
                    $playerStatsMap[$raid->raider_id]['bonusPoints'] += 1;
                }

                // Super raid → count only
                if ($raid->super_raid && isset($playerStatsMap[$raid->raider_id])) {
                    $playerStatsMap[$raid->raider_id]['superRaids'] += 1;
                }

                // Raider caught (defender out raider)
                if ($raid->tacklers) {
                    $teamsMap[$defendingTeamId]['points'] += 1;
                }

                // All out → team only
                if ($raid->all_out) {
                    $teamsMap[$raidingTeamId]['points'] += 2;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UNSUCCESSFUL RAID
            |--------------------------------------------------------------------------
            */
            if ($raid->outcome === 'unsuccessful') {

                // Raider out → defender + team
                if ($raid->tacklers) {

                    $tacklers = $raid->tacklers instanceof \Illuminate\Support\Collection
                        ? $raid->tacklers
                        : collect([$raid->tacklers]);

                    foreach ($tacklers as $tackler) {

                        if (isset($playerStatsMap[$tackler->user_id])) {
                            $playerStatsMap[$tackler->user_id]['tacklePoints'] += 1;
                        }
                    }

                    $teamsMap[$defendingTeamId]['points'] += 1;
                }

                // Bonus → raider + team
                if ($raid->bonus_point) {

                    if (isset($playerStatsMap[$raid->raider_id])) {
                        $playerStatsMap[$raid->raider_id]['bonusPoints'] += 1;
                    }

                    $teamsMap[$raidingTeamId]['points'] += 1;
                }

                // Super tackle → defender + team (2 points)
                if ($raid->super_tackle && $raid->tacklers) {

                    $tacklers = $raid->tacklers instanceof \Illuminate\Support\Collection
                        ? $raid->tacklers
                        : collect([$raid->tacklers]);

                    foreach ($tacklers as $tackler) {

                        if (isset($playerStatsMap[$tackler->user_id])) {
                            $playerStatsMap[$tackler->user_id]['tacklePoints'] += 2;
                            $playerStatsMap[$tackler->user_id]['superTackles'] += 1;
                        }
                    }

                    $teamsMap[$defendingTeamId]['points'] += 2;
                }

                // Raider lineout → no points (store only, already saved)
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BUILD TEAM DTOs
        |--------------------------------------------------------------------------
        */
        $teamBreakdowns = [];

        foreach ($teamsMap as $team) {
            $teamBreakdowns[] = new TeamBreakdownDTO(
                teamId: $team['id'],
                teamName: $team['name'],
                raidPoints: $team['points'],
                tacklePoints: 0,
                allOutPoints: 0,
                extraPoints: 0,
                totalPoints: $team['points']
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
                totalPoints: $player['raidPoints']
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
