<?php

namespace App\Services\Match;

use App\Enums\MatchStatus;
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
        $status = $request->filled('status')
            ? MatchStatus::from($request->status)
            : MatchStatus::UPCOMING;

        $match = GameMatch::create([
            'title' => $request->input('title'),
            'team_a_id' => $request->input('teams_a_id'),
            'team_b_id' => $request->input('teams_b_id'),
            'tournament_id' => $request->input('tournament_id'),
            'tournament_match_no' => $request->input('tournament_match_no'),
            'start_date' => $request->input('start_date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'venue' => $request->input('venue'),
            'ground_name' => $request->input('ground_name'),
            'organizer_phone' => $request->input('organizer_phone'),
            'organizer_email' => $request->input('organizer_email'),
            'status' => $status,
            'toss_winner_team_id' => $request->input('toss_winner_team_id'),
            'toss_decision' => $request->input('toss_decision'),
        ]);

        if ($request->getTeamA() && $request->getTeamB()) {

            foreach ([$request->getTeamA(), $request->getTeamB()] as $team) {
                MatchTeam::create([
                    'match_id' => $match->id,
                    'team_id' => $team['id'] ?? null,
                    'tshirt_color' => $team['tshirt_color'] ?? null,
                    'court_side' => $team['court_side'] ?? null,
                ]);

                $players = TeamPlayer::where('team_id', $team['id'])->get()->toArray();

                $i = 1;
                foreach ($players as $player) {
                    MatchPlayer::create([
                        'match_id'      => $match->id,
                        'team_id'       => $team['id'],
                        'user_id'       => $player['user_id'],
                        'is_playing'    => $i <= 7 ? true : false,
                        'is_substitute' => $i > 7 ? true : false,
                    ]);
                    $i++;
                }
            }
        }

        return $match->load('teams');
    }

    public function update(int $matchId, array $data)
    {

        $match = GameMatch::findOrFail($matchId);
        $status = $data['status'] ?? null;
        if ($status) {
            $status = MatchStatus::from($status);
        } else {
            $status = $match->status;
        }
        $data['status'] = $status;
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

        $team = ['id' => $request->getTeamId()];

        foreach ($request->getMainPlayers() as $playerId) {
            MatchPlayer::create([
                'match_id'      => $match->id,
                'team_id'       => $team['id'],
                'user_id'       => $playerId,
                'is_playing'    => true,
                'is_substitute' => false,
            ]);
        }

        foreach ($request->getSubPlayers() as $playerId) {
            MatchPlayer::create([
                'match_id'      => $match->id,
                'team_id'       => $team['id'],
                'user_id'       => $playerId,
                'is_playing'    => false,
                'is_substitute' => true,
            ]);
        }

        if ($request->getCaptainId()) {
            MatchPlayer::where('match_id', $matchId)
                ->where('team_id', $request->getTeamId())
                ->where('user_id', $request->getCaptainId())
                ->update(['is_captain' => true]);
        }
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
