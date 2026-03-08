<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tournament;

class TournamentPolicy
{
    /**
     * Determine if the user can view the tournament list.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view their own tournaments
    }

    /**
     * Determine if the user can view the tournament.
     */
    public function view(User $user): bool
    {
        return true; // All authenticated users can view tournaments
    }

    /**
     * Determine if the user can create tournaments.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create tournaments
    }

    /**
     * Determine if the user can update the tournament.
     */
    public function update(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->created_by;
    }

    /**
     * Determine if the user can delete the tournament.
     */
    public function delete(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->created_by;
    }

    /**
     * Determine if the user can add teams to the tournament.
     */
    public function addTeams(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->created_by;
    }

    /**
     * Determine if the user can remove teams from the tournament.
     */
    public function removeTeam(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->created_by;
    }
}
