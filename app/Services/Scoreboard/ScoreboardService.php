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
        | TEAM MAP — track points by category
        |--------------------------------------------------------------------------
        */
        $teamsMap = [
            $teamAId => [
                'id'          => $teamAId,
                'name'        => $match->teams->firstWhere('team.id', $teamAId)?->team->name,
                'raidPoints'  => 0,
                'tacklePoints'=> 0,
                'allOutPoints'=> 0,
                'extraPoints' => 0,  // bonus points earned by raiders on this team
            ],
            $teamBId => [
                'id'          => $teamBId,
                'name'        => $match->teams->firstWhere('team.id', $teamBId)?->team->name,
                'raidPoints'  => 0,
                'tacklePoints'=> 0,
                'allOutPoints'=> 0,
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
                'playerId'    => $player->user_id,
                'playerName'  => $player->user->fullname,
                'teamId'      => $player->team_id,
                'raidPoints'  => 0,
                'tacklePoints'=> 0,
                'bonusPoints' => 0,
                'superRaids'  => 0,
                'superTackles'=> 0,
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

            $hasTacklers = $raid->tacklers instanceof \Illuminate\Support\Collection
                        && $raid->tacklers->isNotEmpty();

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

                // Points for each defender put out
                $defenderCount = $raid->defenders->count();
                if ($defenderCount > 0) {
                    $teamsMap[$raidingTeamId]['raidPoints'] += $defenderCount;

                    if (isset($playerStatsMap[$raid->raider_id])) {
                        $playerStatsMap[$raid->raider_id]['raidPoints'] += $defenderCount;
                    }
                }

                // Bonus point (raider crossed baulk line without touching)
                if ($raid->bonus_point) {
                    $teamsMap[$raidingTeamId]['extraPoints'] += 1;

                    if (isset($playerStatsMap[$raid->raider_id])) {
                        $playerStatsMap[$raid->raider_id]['bonusPoints'] += 1;
                    }
                }

                // Super raid flag — just a counter, points already counted above
                if ($raid->super_raid && isset($playerStatsMap[$raid->raider_id])) {
                    $playerStatsMap[$raid->raider_id]['superRaids'] += 1;
                }

                // Raider was caught mid-raid (touch + caught) → defending team +1
                if ($hasTacklers) {
                    $teamsMap[$defendingTeamId]['tacklePoints'] += 1;

                    foreach ($raid->tacklers as $tackler) {
                        if (isset($playerStatsMap[$tackler->user_id])) {
                            $playerStatsMap[$tackler->user_id]['tacklePoints'] += 1;
                        }
                    }
                }

                // All-out bonus → defending team earns 2 extra points
                if ($raid->all_out) {
                    $teamsMap[$defendingTeamId]['allOutPoints'] += 2;
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
            | - raider lineout          → no points (recorder only)
            |--------------------------------------------------------------------------
            */
            if ($raid->outcome === 'unsuccessful') {

                if ($hasTacklers) {
                    if ($raid->super_tackle) {
                        // Super tackle: +2 to team, +2 to each tackler (replaces normal +1)
                        $teamsMap[$defendingTeamId]['tacklePoints'] += 2;

                        foreach ($raid->tacklers as $tackler) {
                            if (isset($playerStatsMap[$tackler->user_id])) {
                                $playerStatsMap[$tackler->user_id]['tacklePoints'] += 2;
                                $playerStatsMap[$tackler->user_id]['superTackles']  += 1;
                            }
                        }
                    } else {
                        // Normal tackle: +1 to team, +1 to each tackler
                        $teamsMap[$defendingTeamId]['tacklePoints'] += 1;

                        foreach ($raid->tacklers as $tackler) {
                            if (isset($playerStatsMap[$tackler->user_id])) {
                                $playerStatsMap[$tackler->user_id]['tacklePoints'] += 1;
                            }
                        }
                    }
                }

                // Bonus point on an unsuccessful raid (e.g. raider crossed baulk but got out)
                if ($raid->bonus_point) {
                    $teamsMap[$raidingTeamId]['extraPoints'] += 1;

                    if (isset($playerStatsMap[$raid->raider_id])) {
                        $playerStatsMap[$raid->raider_id]['bonusPoints'] += 1;
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BUILD TEAM DTOs
        |--------------------------------------------------------------------------
        */
        $teamBreakdowns = [];

        foreach ($teamsMap as $team) {
            $total = $team['raidPoints']
                + $team['tacklePoints']
                + $team['allOutPoints']
                + $team['extraPoints'];

            $teamBreakdowns[] = new TeamBreakdownDTO(
                teamId:      $team['id'],
                teamName:    $team['name'],
                raidPoints:  $team['raidPoints'],
                tacklePoints:$team['tacklePoints'],
                allOutPoints:$team['allOutPoints'],
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
            $total = $player['raidPoints']
                + $player['tacklePoints']
                + $player['bonusPoints'];

            $playerStats[] = new PlayerStatsDTO(
                playerId:    $player['playerId'],
                playerName:  $player['playerName'],
                teamId:      $player['teamId'],
                raidPoints:  $player['raidPoints'],
                tacklePoints:$player['tacklePoints'],
                superRaids:  $player['superRaids'],
                superTackles:$player['superTackles'],
                totalPoints: $total
            );
        }

        return new ScoreboardResponseDTO(
            teamBreakdowns: $teamBreakdowns,
            playerStats:    $playerStats
        );
    }
}
