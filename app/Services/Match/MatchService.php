<?php

namespace App\Services\Match;

use App\Models\GameMatch;
use App\Models\MatchTeam;
use App\Models\TeamPlayer;
use App\Models\MatchPlayer;
use App\Http\Requests\Match\CreateMatchRequest;
use App\Http\Requests\Match\UpdateMatchTeamCourtRequest;
use App\Http\Requests\Match\UpdateMatchTeamPlayersRequest;

class MatchService implements MatchServiceInterface
{
    public function create(CreateMatchRequest $request)
    {
        $match = GameMatch::create($request->validated());

        if (!empty($request->getTeams())) {
            foreach ($request->getTeams() as $team) {
                MatchTeam::create([
                    'match_id' => $match->id,
                    'team_id' => $team['id'] ?? null,
                    'tshirt_color' => $team['tshirt_color'] ?? null,
                ]);

                $players = TeamPlayer::where('team_id', $team['id'])->get()->toArray();
                $this->addPlayersToMatchTeam(
                    $match->id,
                    $team['id'],
                    array_map(fn($player) => ['id' => $player['user_id']], $players)
                );
            }
        }

        return $match->load('teams');
    }

    public function update(int $matchId, array $data)
    {
        $match = GameMatch::findOrFail($matchId);
        $match->update($data);

        return $match->load('teams');
    }

    public function list(array $filters = [])
    {
        $query = GameMatch::query();

        if (!empty($filters['tournament_id'])) {
            $query->where('tournament_id', $filters['tournament_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->get();
    }

    public function detail(int $matchId)
    {
        return GameMatch::with(['teams'])->findOrFail($matchId);
    }

    public function delete(int $matchId)
    {
        $match = GameMatch::findOrFail($matchId);
        $match->update(['status' => 'cancelled']);

        return true;
    }

    public function toss(int $matchId, array $data)
    {
        $match = GameMatch::findOrFail($matchId);

        $match->update([
            'toss_winner_team_id' => $data['toss_winner_team_id'] ?? null,
            'toss_decision' => $data['toss_decision'] ?? null,
        ]);

        return $match;
    }

    public function updateTeamPlayers(UpdateMatchTeamPlayersRequest $request, int $matchId)
    {

        $match = GameMatch::findOrFail($matchId);

        // delete existing players for this match + team
        MatchPlayer::where('match_id', $matchId)
            ->where('team_id', $request->getTeamId())
            ->delete();

        // add new players
        $this->addPlayersToMatchTeam(
            $matchId,
            $request->getTeamId(),
            $request->getPlayers()
        );
        return $match->load('matchPlayers');
    }

    public function updateTeamCourt(UpdateMatchTeamCourtRequest $request, int $matchId)
    {
        $matchTeam = MatchTeam::where('match_id', $matchId)
            ->where('team_id', $request->getTeamId())
            ->first();

        if ($matchTeam) {
            $matchTeam->update([
                'court_side' => $request->getCourtSide(),
            ]);
        }

        return GameMatch::with(['teams'])->findOrFail($matchId);
    }

    protected function addPlayersToMatchTeam(int $matchId, int $teamId, array $players): void
    {
        foreach ($players as $player) {
            MatchPlayer::create([
                'match_id'      => $matchId,
                'team_id'       => $teamId,
                'user_id'       => $player['id'],
                'is_substitute' => $player['is_substitute'] ?? false,
                'is_playing'    => !($player['is_substitute'] ?? false),
            ]);
        }
    }
}
