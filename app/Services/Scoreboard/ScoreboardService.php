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

        $playerStatsMap = [];

        foreach ($match->matchPlayers as $player) {
            $playerStatsMap[$player->id] = [
                'playerId' => $player->id,
                'playerName' => $player->user->fullname,
                'teamId' => $player->team_id,
                'raidPoints' => 0,
                'tacklePoints' => 0,
                'superRaids' => 0,
                'superTackles' => 0,
            ];
        }

        foreach ($match->raids as $raid) {

            $raidingTeamId = $raid->raid_team_id;
            $defendingTeamId = $raidingTeamId == $teamAId ? $teamBId : $teamAId;

            $defenderCount = $raid->defenders->count();
            $lineoutCount = $raid->defenderLineouts->count();
            $tackleCount = $raid->tacklers ? 1 : 0; // hasOne relation

            /*
            |--------------------------------------------------------------------------
            | RAID TEAM POINTS
            |--------------------------------------------------------------------------
            */

            // touch points
            $teamPoints = $defenderCount;

            // bonus
            if ($raid->bonus_point) {
                $teamPoints += 1;
            }

            // defender lineout
            $teamPoints += $lineoutCount;

            // all out
            if ($raid->all_out) {
                $teamPoints += 2;
                $teamsMap[$raidingTeamId]['allOutPoints'] += 2;
            }

            $teamsMap[$raidingTeamId]['raidPoints'] += $defenderCount;
            $teamsMap[$raidingTeamId]['extraPoints'] += ($raid->bonus_point ? 1 : 0) + $lineoutCount;

            /*
            |--------------------------------------------------------------------------
            | DEFENDING TEAM POINTS
            |--------------------------------------------------------------------------
            */

            if ($raid->tacklers) {
                $teamsMap[$defendingTeamId]['tacklePoints'] += 1;

                if ($raid->super_tackle) {
                    $teamsMap[$defendingTeamId]['tacklePoints'] += 1; // +1 extra (total 2)
                }
            }

            /*
            |--------------------------------------------------------------------------
            | PLAYER STATS
            |--------------------------------------------------------------------------
            */

            // Raider stats
            if (isset($playerStatsMap[$raid->raider_id])) {
                $playerStatsMap[$raid->raider_id]['raidPoints'] += $defenderCount;

                if ($defenderCount >= 3) {
                    $playerStatsMap[$raid->raider_id]['superRaids'] += 1;
                }
            }

            // Defender touch stats
            foreach ($raid->defenders as $defender) {
                if (isset($playerStatsMap[$defender->user_id])) {
                    // defender got out — no points
                }
            }

            // Tackler stats
            if ($raid->tacklers) {
                $tacklerId = $raid->tacklers->user_id;

                if (isset($playerStatsMap[$tacklerId])) {
                    $playerStatsMap[$tacklerId]['tacklePoints'] += 1;

                    if ($raid->super_tackle) {
                        $playerStatsMap[$tacklerId]['tacklePoints'] += 1;
                        $playerStatsMap[$tacklerId]['superTackles'] += 1;
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TECHNICAL POINTS FROM EVENTS
        |--------------------------------------------------------------------------
        */

        foreach ($match->events as $event) {
            if ($event->type === EventType::TECHNICAL_POINT) {

                if (isset($teamsMap[$event->team_id])) {
                    $teamsMap[$event->team_id]['extraPoints'] += 1;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Build DTOs
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
