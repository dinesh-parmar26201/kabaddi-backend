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
            'raids.tacklers',          // hasOne
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
                'id'           => $teamAId,
                'name'         => $match->teams->firstWhere('team.id', $teamAId)?->team->name,
                'raidPoints'   => 0,
                'tacklePoints' => 0,
                'allOutPoints' => 0,
                'extraPoints'  => 0,
                'technicalPoints' => 0,
                'superTackles'  => 0,
            ],
            $teamBId => [
                'id'           => $teamBId,
                'name'         => $match->teams->firstWhere('team.id', $teamBId)?->team->name,
                'raidPoints'   => 0,
                'tacklePoints' => 0,
                'allOutPoints' => 0,
                'extraPoints'  => 0,
                'technicalPoints' => 0,
                'superTackles'  => 0,
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
                'playerId'     => $player->user_id,
                'playerName'   => $player->user->fullname,
                'teamId'       => $player->team_id,
                'raidPoints'   => 0,
                'tacklePoints' => 0,
                'bonusPoints'  => 0,
                'superRaids'   => 0,
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
            $defendingTeamId = ($raidingTeamId == $teamAId) ? $teamBId : $teamAId;

            // hasOne → single model or null
            $hasTackler = !is_null($raid->tacklers);

            /*
            |--------------------------------------------------------------------------
            | SUCCESSFUL RAID
            |--------------------------------------------------------------------------
            | - 1 point per defender touched/out  → raiding team (raid points) + raider
            | - bonus point                        → raiding team (extra points) + raider
            | - super raid flag                    → counted on raider only (no extra pts)
            | - raider was also caught (touch+tackle scenario) → defending team +1 tackle pt
            | - all out                            → defending team +2 all-out points
            |--------------------------------------------------------------------------
            */
            if ($raid->outcome === 'successful') {

                // Defender outs
                $defenderCount = $raid->defenders->count();
                if ($defenderCount > 0) {
                    $teamsMap[$raidingTeamId]['raidPoints'] += $defenderCount;

                    if (isset($playerStatsMap[$raid->raider_id])) {
                        $playerStatsMap[$raid->raider_id]['raidPoints'] += $defenderCount;
                    }
                }

                // Bonus
                if ($raid->bonus_point) {
                    $teamsMap[$raidingTeamId]['extraPoints'] += 1;

                    if (isset($playerStatsMap[$raid->raider_id])) {
                        $playerStatsMap[$raid->raider_id]['bonusPoints'] += 1;
                    }
                }

                // Super raid counter
                if ($raid->super_raid && isset($playerStatsMap[$raid->raider_id])) {
                    $playerStatsMap[$raid->raider_id]['superRaids'] += 1;
                }

                // Raider caught after touch
                if ($hasTackler) {
                    $teamsMap[$defendingTeamId]['tacklePoints'] += 1;

                    $tackler = $raid->tacklers;
                    if (isset($playerStatsMap[$tackler->user_id])) {
                        $playerStatsMap[$tackler->user_id]['tacklePoints'] += 1;
                    }
                }

                // All-out bonus
                if ($raid->all_out) {
                    $teamsMap[$raidingTeamId]['allOutPoints'] += 2;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UNSUCCESSFUL RAID
            |--------------------------------------------------------------------------
            | - raider out (tackled)    → defending team +1 tackle point + each tackler +1
            | - super tackle            → defending team +2 additional + each tackler +2
            |                             (super tackle replaces regular tackle point,
            |                              so net = +2 to team and +2 to tackler)
            | - bonus point on empty   → raiding team +1 extra + raider +1 bonus
            | - raider lineout         → defending team +1
            | - all members of raiding team are out (all-out) → defending team +2 all-out points
            |--------------------------------------------------------------------------
            */
            if ($raid->outcome === 'unsuccessful') {

                // Defender lineouts → raiding team gets points
                $lineoutCount = $raid->defenderLineouts->count();

                if ($lineoutCount > 0) {
                    $teamsMap[$raidingTeamId]['raidPoints'] += $lineoutCount;

                    if (isset($playerStatsMap[$raid->raider_id])) {
                        $playerStatsMap[$raid->raider_id]['raidPoints'] += $lineoutCount;
                    }
                }

                if ($hasTackler) {
                    $tackler = $raid->tacklers;

                    if ($raid->super_tackle) {

                        $teamsMap[$defendingTeamId]['tacklePoints'] += 1;

                        if (isset($playerStatsMap[$tackler->user_id])) {
                            $playerStatsMap[$tackler->user_id]['tacklePoints'] += 1;
                            $playerStatsMap[$tackler->user_id]['superTackles'] += 1;
                            $teamsMap[$defendingTeamId]['superTackles'] += 1;
                        }

                    } else {

                        $teamsMap[$defendingTeamId]['tacklePoints'] += 1;

                        if (isset($playerStatsMap[$tackler->user_id])) {
                            $playerStatsMap[$tackler->user_id]['tacklePoints'] += 1;
                        }
                    }
                }

                if ($raid->bonus_point) {
                    $teamsMap[$raidingTeamId]['extraPoints'] += 1;

                    if (isset($playerStatsMap[$raid->raider_id])) {
                        $playerStatsMap[$raid->raider_id]['bonusPoints'] += 1;
                    }
                }

                if ($raid->raider_lineout) {
                    $teamsMap[$defendingTeamId]['extraPoints'] += 1;
                }

                // All-out bonus
                if ($raid->all_out) {
                    $teamsMap[$defendingTeamId]['allOutPoints'] += 2;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PROCESS TECHNICAL POINTS
        |--------------------------------------------------------------------------
        | - technical points are awarded for non-raid events (e.g. fouls, timeouts)
        | - they can be awarded to either team and may not be associated with a specific player
        |--------------------------------------------------------------------------
        */
        foreach ($match->events as $event) {
            if ($event->type) {
                if ($event->type->value == EventType::TECHNICAL_POINT->value) {
                    $teamsMap[$event->team_id]['technicalPoints'] += 1;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TEAM DTOs
        |--------------------------------------------------------------------------
        */
        $teamBreakdowns = [];

        foreach ($teamsMap as $team) {
            $total = $team['raidPoints']
                + $team['tacklePoints']
                + $team['allOutPoints']
                + $team['extraPoints']
                + $team['technicalPoints']
                + $team['superTackles'];

            $teamBreakdowns[] = new TeamBreakdownDTO(
                teamId: $team['id'],
                teamName: $team['name'],
                raidPoints: $team['raidPoints'],
                tacklePoints: $team['tacklePoints'],
                allOutPoints: $team['allOutPoints'],
                extraPoints: $team['extraPoints'],
                technicalPoints: $team['technicalPoints'],
                totalPoints: $total
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PLAYER DTOs
        |--------------------------------------------------------------------------
        */
        $playerStats = [];

        foreach ($playerStatsMap as $player) {
            $total = $player['raidPoints']
                + $player['tacklePoints']
                + $player['bonusPoints']
                //+ $player['superRaids']
                + $player['superTackles'];

            $playerStats[] = new PlayerStatsDTO(
                playerId: $player['playerId'],
                playerName: $player['playerName'],
                teamId: $player['teamId'],
                raidPoints: $player['raidPoints'],
                tacklePoints: $player['tacklePoints'],
                superRaids: $player['superRaids'],
                superTackles: $player['superTackles'],
                bonusPoints: $player['bonusPoints'],
                totalPoints: $total
            );
        }

        return new ScoreboardResponseDTO(
            teamBreakdowns: $teamBreakdowns,
            playerStats: $playerStats
        );
    }
}
