<?php

namespace App\Services\Team;

use App\Enums\MatchStatus;
use Exception;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Requests\Team\AddPlayerToTeamRequest;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TeamService implements TeamServiceInterface
{
    public function list(?string $search = null): LengthAwarePaginator
    {
        if (request()->get('all', false)) {
            $query = Team::query();
        } else {
            $query = Team::where(function ($q) {
                $q->where('created_by', Auth::id())
                    ->orWhereHas('allPlayers', function ($q2) {
                        $q2->where('users.id', Auth::id());
                    });
            });
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('city', 'like', '%' . $search . '%');
        }

        if ($search) {
            $query->orWhere('id', $search);
        }

        $perPage = (int) request()->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        return $query->latest()->paginate($perPage);
    }

    public function create($request): Team
    {
        try {
            if (isset($request->logo) && $request->hasFile('logo')) {
                $path = $request->file('logo')->store('images/TeamLogos', 'public');
                if (!$path) {
                    throw new Exception('Logo upload failed');
                }
            }

            if (isset($request->qr_code) && $request->hasFile('qr_code')) {
                $qr_code_path = $request->file('qr_code')->store('images/TeamLogos/QR', 'public');
                if (!$qr_code_path) {
                    throw new Exception('QR code upload failed');
                }
            }

            $team = Team::create([
                'name' => $request->input('name'),
                'city' => $request->input('city'),
                'logo' => $path ?? null,
                'created_by' => Auth::id(),
                'qr_code' => $qr_code_path ?? null,
            ]);
            return $team;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function update(int $id, UpdateTeamRequest $request): Team
    {
        try {
            $team = Team::findOrFail($id);

            // Handle logo update
            if (isset($request->logo) && $request->hasFile('logo')) {
                // Upload new logo
                $path = $request->file('logo')->store('images/TeamLogos', 'public');

                if (!$path) {
                    throw new Exception('Logo upload failed');
                }
                // Delete old logo if exists
                if ($team->logo) {
                    Storage::disk('public')->delete($team->logo);
                }

                $team->logo = $path;
            }

            // Handle qr_code update
            if (isset($request->qr_code) && $request->hasFile('qr_code')) {
                // Upload new qr_code
                $qr_code_path = $request->file('qr_code')->store('images/TeamLogos/QR', 'public');

                if (!$qr_code_path) {
                    throw new Exception('QR code upload failed');
                }
                // Delete old qr_code if exists
                if ($team->qr_code) {
                    Storage::disk('public')->delete($team->qr_code);
                }

                $team->qr_code = $qr_code_path;
            }

            // Update team data
            $team->update([
                'name' => $request->has('name') ? $request->input('name') : $team->name,
                'city' => $request->has('city') ? $request->input('city') : $team->city,
                'logo' => $team->logo, // logo is already handled above
                'qr_code' => $team->qr_code, // qr_code is already handled above
            ]);

            // Handle players update
            if (isset($request->players)) {
                $team->allPlayers()->detach();
                $playerData = [];
                foreach ($request->players as $player) {
                    $playerData[$player['id']] = [
                        'is_captain' => $player['is_captain'] ?? false,
                    ];
                }
                $team->players()->sync($playerData);
            }

            return $team;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $team = Team::findOrFail($id);
        $team->allPlayers()->detach();
        $team->delete();
    }

    public function addPlayer(int $teamId, AddPlayerToTeamRequest $request): void
    {
        $team = Team::findOrFail($teamId);

        $isCaptain = $request->input('is_captain', false);

        // If adding as captain, remove existing captain
        if ($isCaptain) {
            $currentCaptain = $team->allPlayers()->wherePivot('is_captain', true)->first();
            if ($currentCaptain) {
                $team->allPlayers()->updateExistingPivot($currentCaptain->id, ['is_captain' => false]);
            }
        }

        // Check if player already exists in team
        $existingPlayer = $team->allPlayers()->where('user_id', $request->player_id)->first();
        if ($existingPlayer) {
            // Update existing player record
            $team->allPlayers()->updateExistingPivot($request->player_id, ['is_captain' => $isCaptain]);
        } else {
            // Attach the new player
            $team->allPlayers()->attach($request->player_id, ['is_captain' => $isCaptain]);
        }
    }

    public function getMatches(int $teamId): LengthAwarePaginator
    {
        $team = Team::findOrFail($teamId);
        $matches = $team->matches();

        if (strtolower(request()->get('status')) == 'live') {
            $matches->whereIn('status', MatchStatus::isLive());
        } else if (strtolower(request()->get('status')) == MatchStatus::UPCOMING->value) {
            $matches->where('status', MatchStatus::UPCOMING->value);
        } else if (strtolower(request()->get('status')) == MatchStatus::COMPLETED->value) {
            $matches->where('status', MatchStatus::COMPLETED->value);
        }

        $perPage = (int) request()->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        return $matches->paginate($perPage);
    }

    public function removePlayer(int $teamId, int $playerId): void
    {
        $team = Team::findOrFail($teamId);
        $team->allPlayers()->detach($playerId);
    }

    public function stats(int $id)
    {
        $team = Team::findOrFail($id);
        $stats = $team->loadCount([
            'allPlayers as total_raiders' => function ($query) {
                $query->where('role', 'raider');
            },
            'allPlayers as total_defenders' => function ($query) {
                $query->where('role', 'defender');
            },
            'allPlayers as total_all_rounders' => function ($query) {
                $query->where('role', 'all-rounder');
            },
            'matches as total_matches_played' => function ($query) {
                $query->where('status', 'completed');
            },
            'matches as total_all_matches',
            'matches as total_matches_won' => function ($query) use ($id) {
                $query->where('status', 'completed')
                    ->where('winner_team_id', $id);
            },
        ]);

        $played = $stats->total_matches_played ?? 0;
        $won = $stats->total_matches_won ?? 0;
        $stats->win_percentage = $played > 0 ? round(($won / $played) * 100, 2) : 0;

        return $stats;
    }
}
