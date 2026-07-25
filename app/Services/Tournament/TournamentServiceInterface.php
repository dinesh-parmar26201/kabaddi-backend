<?php

namespace App\Services\Tournament;

use App\Http\Requests\Tournament\StoreTournamentRequest;
use App\Http\Requests\Tournament\UpdateTournamentRequest;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TournamentServiceInterface
{
    public function store(StoreTournamentRequest $request);
    public function update(int $id, UpdateTournamentRequest $request);
    public function delete(int $id): void;
    public function find(int $id);
    public function list(?string $search = null): LengthAwarePaginator;
    public function addTeams(int $tournamentId, array $teamIds);
    public function getTeams(int $tournamentId, int $perPage): LengthAwarePaginator;
    public function getMatches(int $tournamentId, int $perPage): LengthAwarePaginator;
    public function removeTeam(int $tournamentId, int $teamId): void;
}

