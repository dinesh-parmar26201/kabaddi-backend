<?php

namespace App\Services\Scoreboard;

use App\Services\Scoreboard\ScoreboardServiceInterface;
use App\DTO\ScoreboardResponseDTO;
use App\DTO\TeamBreakdownDTO;
use App\DTO\PlayerStatsDTO;
use App\Enums\EventType;
use App\Models\GameMatch;
use App\Models\MatchPlayer;

class ScoreboardService implements ScoreboardServiceInterface
{
    public function getMatchScoreboard(int $matchId, ?int $half = null): ScoreboardResponseDTO
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
                'superRaids'   => 0,
                'superTackles' => 0,
                'raiderLineoutPoints' => 0,
                'defenderLineoutPoints' => 0,
                'technicalPoints' => 0,
            ],
            $teamBId => [
                'id'           => $teamBId,
                'name'         => $match->teams->firstWhere('team.id', $teamBId)?->team->name,
                'raidPoints'   => 0,
                'tacklePoints' => 0,
                'allOutPoints' => 0,
                'extraPoints'  => 0,
                'superRaids'   => 0,
                'superTackles' => 0,
                'raiderLineoutPoints' => 0,
                'defenderLineoutPoints' => 0,
                'technicalPoints' => 0,
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
                'photo'        => $player->user->photo ? asset('storage/' . $player->user->photo) : null,
                'teamId'       => $player->team_id,
                'raidPoints'   => 0,
                'tacklePoints' => 0,
                'bonusPoints'  => 0,
                'superRaids'   => 0,
                'superTackles' => 0,
                'defenderLineoutPoints' => 0,
                'totalRaids'   => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PROCESS RAIDS
        |--------------------------------------------------------------------------
        */
        $raids = $half
            ? $match->raids->where('half', $half)
            : $match->raids;

        foreach ($raids as $raid) {

            // Count total raids per raider
            if (isset($playerStatsMap[$raid->raider_id])) {
                $playerStatsMap[$raid->raider_id]['totalRaids']++;
            }

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

                // Defender lineouts → raiding team gets points
                $lineoutCount = $raid->defenderLineouts->count();

                if ($lineoutCount > 0) {
                    // $teamsMap[$raidingTeamId]['raidPoints'] += $lineoutCount;
                    $teamsMap[$raidingTeamId]['defenderLineoutPoints'] += $lineoutCount;

                    // if (isset($playerStatsMap[$raid->raider_id])) {
                    //     $playerStatsMap[$raid->raider_id]['raidPoints'] += $lineoutCount;
                    //     $playerStatsMap[$raid->raider_id]['defenderLineoutPoints'] += $lineoutCount;
                    // }
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
                    $teamsMap[$raidingTeamId]['superRaids'] += 1;
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
                    // $teamsMap[$raidingTeamId]['raidPoints'] += $lineoutCount;
                    $teamsMap[$raidingTeamId]['defenderLineoutPoints'] += $lineoutCount;

                    // if (isset($playerStatsMap[$raid->raider_id])) {
                    //     $playerStatsMap[$raid->raider_id]['raidPoints'] += $lineoutCount;
                    //     $playerStatsMap[$raid->raider_id]['defenderLineoutPoints'] += $lineoutCount;
                    // }
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
                    // $teamsMap[$defendingTeamId]['extraPoints'] += 1;
                    $teamsMap[$defendingTeamId]['raiderLineoutPoints'] += 1;
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
        $events = $half
            ? $match->events->where('half', $half)
            : $match->events;

        foreach ($events as $event) {
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
                + $team['raiderLineoutPoints']
                + $team['defenderLineoutPoints']
                + $team['technicalPoints']
                + $team['superTackles'];

            $teamBreakdowns[] = new TeamBreakdownDTO(
                teamId: $team['id'],
                teamName: $team['name'],
                raidPoints: $team['raidPoints'],
                tacklePoints: $team['tacklePoints'],
                allOutPoints: $team['allOutPoints'],
                extraPoints: $team['extraPoints'],
                superRaids: $team['superRaids'],
                superTackles: $team['superTackles'],
                raiderLineoutPoints: $team['raiderLineoutPoints'],
                defenderLineoutPoints: $team['defenderLineoutPoints'],
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
                photo: $player['photo'],
                teamId: $player['teamId'],
                raidPoints: $player['raidPoints'],
                tacklePoints: $player['tacklePoints'],
                superRaids: $player['superRaids'],
                superTackles: $player['superTackles'],
                bonusPoints: $player['bonusPoints'],
                // defenderLineoutPoints: $player['defenderLineoutPoints'],
                totalRaids: $player['totalRaids'],
                totalPoints: $total
            );
        }

        return new ScoreboardResponseDTO(
            teamBreakdowns: $teamBreakdowns,
            playerStats: $playerStats
        );
    }

    public function getMatchPlayerStats(GameMatch $match, MatchPlayer $matchPlayer) {
        $playerId = $matchPlayer->user_id;

        // Ensure raids and their relations are loaded
        $match->loadMissing([
            'raids.defenders',
            'raids.tacklers',
        ]);

        /*
        |--------------------------------------------------------------------------
        | POINTS
        |--------------------------------------------------------------------------
        */
        $touchPoints  = 0; // raid touch points (defender outs by this raider)
        $bonusPoints  = 0;
        $tacklePoints = 0;

        /*
        |--------------------------------------------------------------------------
        | RAID STATS
        |--------------------------------------------------------------------------
        */
        $totalRaids        = 0;
        $successfulRaids   = 0;
        $unsuccessfulRaids = 0;
        $emptyRaids        = 0;
        $superRaids        = 0;
        $raidSequence      = []; // ordered list of raid outcomes for the dot visualization

        /*
        |--------------------------------------------------------------------------
        | TACKLE STATS
        |--------------------------------------------------------------------------
        */
        $totalTackles        = 0;
        $successfulTackles   = 0;
        $unsuccessfulTackles = 0;
        $superTackles        = 0;

        foreach ($match->raids as $raid) {

            /*
            |--------------------------------------------------------------
            | When this player is the RAIDER
            |--------------------------------------------------------------
            */
            if ($raid->raider_id === $playerId) {
                $totalRaids++;

                if ($raid->outcome === 'successful') {
                    $successfulRaids++;
                    $raidSequence[] = 'successful';

                    $defenderCount = $raid->defenders->count();
                    $touchPoints  += $defenderCount;

                } elseif ($raid->outcome === 'unsuccessful') {
                    $unsuccessfulRaids++;
                    $raidSequence[] = 'unsuccessful';

                } else {
                    // empty
                    $emptyRaids++;
                    $raidSequence[] = 'empty';
                }

                if ($raid->bonus_point) {
                    $bonusPoints++;
                }

                if ($raid->super_raid) {
                    $superRaids++;
                }
            }

            /*
            |--------------------------------------------------------------
            | When this player is the TACKLER
            |--------------------------------------------------------------
            */
            $tackler = $raid->tacklers; // hasOne → model or null
            if ($tackler && $tackler->user_id === $playerId) {
                $successfulTackles++;
                $tacklePoints++;

                if ($raid->super_tackle) {
                    $superTackles++;
                }
            }

            /*
            |--------------------------------------------------------------
            | When this player is a DEFENDER and the raid is successful
            | (i.e. the raider touched them – counts as unsuccessful tackle)
            |--------------------------------------------------------------
            */
            if ($raid->outcome === 'successful' && $raid->raider_id !== $playerId) {
                $wasDefender = $raid->defenders->contains('user_id', $playerId);
                if ($wasDefender) {
                    $unsuccessfulTackles++;
                }
            }
        }

        $totalTackles = $successfulTackles + $unsuccessfulTackles;
        $totalPoints  = $touchPoints + $bonusPoints + $tacklePoints + $superTackles;

        return [
            'playerId'   => $playerId,
            'playerName' => $matchPlayer->user->fullname ?? null,
            'photo'      => $matchPlayer->user->photo ? asset('storage/' . $matchPlayer->user->photo) : null,
            'teamId'     => $matchPlayer->team_id,

            // Points breakdown
            'totalPoints'  => $totalPoints,
            'touchPoints'  => $touchPoints,
            'bonusPoints'  => $bonusPoints,
            'tacklePoints' => $tacklePoints,

            // Raid stats
            'totalRaids'        => $totalRaids,
            'successfulRaids'   => $successfulRaids,
            'unsuccessfulRaids' => $unsuccessfulRaids,
            'emptyRaids'        => $emptyRaids,
            'superRaids'        => $superRaids,
            'raidSequence'      => $raidSequence,

            // Tackle stats
            'totalTackles'        => $totalTackles,
            'successfulTackles'   => $successfulTackles,
            'unsuccessfulTackles' => $unsuccessfulTackles,
            'superTackles'        => $superTackles,
        ];
    }
    
}
