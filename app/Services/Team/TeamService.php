<?php

namespace App\Services\Team;

use Exception;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Requests\Team\AddPlayerToTeamRequest;

class TeamService implements TeamServiceInterface
{
    public function list(): iterable
    {
        return Team::where('created_by', Auth::id())->latest()->get();
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

            $team = Team::create([
                'name' => $request->input('name'),
                'city' => $request->input('city'),
                'logo' => $path ?? null,
                'created_by' => Auth::id(),
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

            // Update team data
            $team->update([
                'name' => $request->input('name'),
                'city' => $request->input('city'),
                'logo' => $team->logo,
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

    public function getMatches(int $teamId): iterable
    {
        $team = Team::findOrFail($teamId);
        return $team->matches()->get();
    }
}
