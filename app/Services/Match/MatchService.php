<?php

namespace App\Services\Match;

use App\Models\GameMatch;
use App\Models\MatchTeam;

class MatchService implements MatchServiceInterface
{
    public function create(array $data)
    {
        $match = GameMatch::create($data);

        if (!empty($data['teams'])) {
            foreach ($data['teams'] as $team) {
                MatchTeam::create([
                    'match_id' => $match->id,
                    'team_id' => $team['team_id'] ?? null,
                    'tshirt_color' => $team['tshirt_color'] ?? null,
                ]);
            }
        }

        return $match->load('teams');
    }

    public function update(int $matchId, array $data)
    {
        $match = GameMatch::findOrFail($matchId);
        $match->update($data);

        if (!empty($data['teams'])) {
            foreach ($data['teams'] as $team) {
                MatchTeam::updateOrCreate(
                    [
                        'match_id' => $match->id,
                        'team_id' => $team['team_id'],
                    ],
                    [
                        'tshirt_color' => $team['tshirt_color'] ?? null,
                    ]
                );
            }
        }

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
}
