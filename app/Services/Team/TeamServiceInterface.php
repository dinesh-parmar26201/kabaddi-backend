<?php

namespace App\Services\Team;

use App\Models\Team;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Requests\Team\AddPlayerToTeamRequest;

interface TeamServiceInterface
{
    public function list(?string $search = null): iterable;

    public function create(StoreTeamRequest $request): Team;

    public function update(int $id, UpdateTeamRequest $request): Team;

    public function delete(int $id): void;

    public function addPlayer(int $teamId, AddPlayerToTeamRequest $request): void;

    public function getMatches(int $teamId): iterable;
}
