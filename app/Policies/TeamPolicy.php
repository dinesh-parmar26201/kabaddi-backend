<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine if the user can view the team list.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view their own teams
    }

    /**
     * Determine if the user can view the team.
     */
    public function view(User $user, Team $team): bool
    {
        return true; // All authenticated users can view their own teams
        // return $user->id === $team->created_by || $team->allPlayers()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can create teams.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create teams
    }

    /**
     * Determine if the user can update the team.
     */
    public function update(User $user, Team $team): bool
    {
        return $user->id === $team->created_by;
    }

    /**
     * Determine if the user can delete the team.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->id === $team->created_by;
    }

    /**
     * Determine if the user can add players to the team.
     */
    public function addPlayer(User $user, Team $team): bool
    {
        return $user->id === $team->created_by;
    }

    /**
     * Determine if the user can remove players from the team.
     */
    public function removePlayer(User $user, Team $team): bool
    {
        return $user->id === $team->created_by;
    }
}
