<?php

namespace App\Services\Tournament;

use Symfony\Component\HttpKernel\HttpCache\Store;
use App\Http\Requests\Tournament\StoreTournamentRequest;
use App\Http\Requests\Tournament\UpdateTournamentRequest;

interface TournamentServiceInterface
{
    public function store(StoreTournamentRequest $request);
    public function update(int $id, UpdateTournamentRequest $request);
    public function delete(int $id): void;
    public function find(int $id);
    public function list();
    public function addTeams(int $tournamentId, array $teamIds);
}

