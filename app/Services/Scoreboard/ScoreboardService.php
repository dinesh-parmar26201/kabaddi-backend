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
            'events' => function ($q) {
                $q->where('type', EventType::TECHNICAL_POINT);
            }
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
                'extraPoints' => 0, // lineout + team-only technical
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

            $defenderCount = $raid->defenders->count();
            $lineoutCount  = $raid->defenderLineouts->count();

            /*
            |--------------------------------------------------------------------------
            | TEAM POINTS
            |--------------------------------------------------------------------------
            */
            $teamsMap[$raidingTeamId]['raidPoints'] += $defenderCount;

            // ❗ FIX: bonus REMOVED from team extraPoints
            $teamsMap[$raidingTeamId]['extraPoints'] += $lineoutCount;

            if ($raid->all_out) {
                $teamsMap[$raidingTeamId]['allOutPoints'] += 2;
            }

            if ($raid->tacklers) {
                $teamsMap[$defendingTeamId]['tacklePoints'] += $raid->super_tackle ? 2 : 1;
            }

            /*
            |--------------------------------------------------------------------------
            | RAIDER PLAYER STATS
            |--------------------------------------------------------------------------
            */
            if (isset($playerStatsMap[$raid->raider_id])) {

                $raiderPoints = $defenderCount;

                // bonus → raider ONLY
                if ($raid->bonus_point) {
                    $raiderPoints += 1;
                }

                // lineout does NOT go to raider
                // all-out goes to raider
                if ($raid->all_out) {
                    $raiderPoints += 2;
                }

                $playerStatsMap[$raid->raider_id]['raidPoints'] += $raiderPoints;

                // super raid = 3+ points (classification only)
                if ($raiderPoints >= 3) {
                    $playerStatsMap[$raid->raider_id]['superRaids'] += 1;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | TACKLER PLAYER STATS (hasOne / hasMany safe)
            |--------------------------------------------------------------------------
            */
            if ($raid->tacklers) {

                $tacklers = $raid->tacklers instanceof \Illuminate\Support\Collection
                    ? $raid->tacklers
                    : collect([$raid->tacklers]);

                foreach ($tacklers as $tackler) {

                    if (!isset($playerStatsMap[$tackler->user_id])) {
                        continue;
                    }

                    $playerStatsMap[$tackler->user_id]['tacklePoints'] += 1;

                    if ($raid->super_tackle) {
                        $playerStatsMap[$tackler->user_id]['tacklePoints'] += 1;
                        $playerStatsMap[$tackler->user_id]['superTackles'] += 1;
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TECHNICAL POINTS
        |--------------------------------------------------------------------------
        | player_id → player
        | else → team-only
        |--------------------------------------------------------------------------
        */
        foreach ($match->events as $event) {

            if ($event->player_id && isset($playerStatsMap[$event->player_id])) {
                $playerStatsMap[$event->player_id]['raidPoints'] += 1;
            } else {
                $teamsMap[$event->team_id]['extraPoints'] += 1;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BUILD TEAM DTOs
        |--------------------------------------------------------------------------
        */
        $teamBreakdowns = [];

        foreach ($teamsMap as $teamData) {

            $total = $teamData['raidPoints']
                + $teamData['tacklePoints']
                + $teamData['allOutPoints']
                + $teamData['extraPoints'];

            $teamBreakdowns[] = new TeamBreakdownDTO(
                teamId: $teamData['id'],
                teamName: $teamData['name'],
                raidPoints: $teamData['raidPoints'],
                tacklePoints: $teamData['tacklePoints'],
                allOutPoints: $teamData['allOutPoints'],
                extraPoints: $teamData['extraPoints'],
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
                totalPoints: $player['raidPoints'] + $player['tacklePoints']
            );
        }

        return new ScoreboardResponseDTO(
            teamBreakdowns: $teamBreakdowns,
            playerStats: $playerStats
        );
    }
}
