<?php

namespace App\Services\Match;

use App\Enums\MatchStatus;
use App\Http\Requests\Match\CreateMatchRequest;
use App\Http\Requests\Match\UpdateMatchTeamCourtRequest;
use App\Http\Requests\Match\UpdateMatchTeamPlayersRequest;
use App\Http\Requests\Match\UpdateMatchPlayerCardRequest;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\MatchTeam;
use App\Models\TeamPlayer;
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
            'created_by' => $request->user()->id,
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
                        'is_captain'    => $player['is_captain'] ?? false,
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

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = GameMatch::query();

        if (!empty($filters['tournament_id'])) {
            $query->where('tournament_id', $filters['tournament_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(15);
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

    //deprecated - Helper method to add players to a match team
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

    public function swapPlayers(int $matchId, array $data): void
    {
        DB::transaction(function () use ($matchId, $data) {

            $matchPlayers = MatchPlayer::where('match_id', $matchId)
                ->where('team_id', $data['team_id'])
                ->get()
                ->keyBy('user_id');

            $inPlayer = $matchPlayers->get($data['in_player_id']);
            $outPlayer = $matchPlayers->get($data['out_player_id']);

            // Validation
            if (!$inPlayer || !$outPlayer) {
                throw new Exception('Players must belong to this match and team.');
            }

            if ($inPlayer->is_playing) {
                throw new Exception('Selected in_player is already playing.');
            }

            if (!$outPlayer->is_playing) {
                throw new Exception('Selected out_player is already out.');
            }

            // Swap
            $inPlayer->update([
                'is_playing' => true,
                'updated_at' => now()
            ]);

            $outPlayer->update([
                'is_playing' => false,
                'updated_at' => now()
            ]);
        });
    }

    public function updateCard(UpdateMatchPlayerCardRequest $request, int $matchId)
    {
        $matchPlayer = MatchPlayer::where('match_id', $matchId)
            ->where('team_id', $request->input('team_id'))
            ->where('user_id', $request->input('player_id'))
            ->firstOrFail();

        if ($request->has('green_card')) {
            $matchPlayer->green_card = $request->input('green_card');
        }

        if ($request->has('red_card')) {
            $matchPlayer->red_card = $request->input('red_card');
        }

        if ($request->has('yellow_card')) {
            $matchPlayer->yellow_card = $request->input('yellow_card');
        }

        $matchPlayer->save();

        return GameMatch::with(['teams'])->findOrFail($matchId);
    }
}
