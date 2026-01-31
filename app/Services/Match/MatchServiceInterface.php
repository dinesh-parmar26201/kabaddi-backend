<?php

namespace App\Services\Match;

use App\Http\Requests\Match\CreateMatchRequest;
use App\Http\Requests\Match\UpdateMatchTeamCourtRequest;
use App\Http\Requests\Match\UpdateMatchTeamPlayersRequest;

interface MatchServiceInterface
{
    public function create(CreateMatchRequest $request);
    public function update(int $matchId, array $data);
    public function list(array $filters = []);
    public function detail(int $matchId);
    public function delete(int $matchId);
    public function toss(int $matchId, array $data);
    public function updateTeamPlayers(UpdateMatchTeamPlayersRequest $request, int $matchId);
    public function updateTeamCourt(UpdateMatchTeamCourtRequest $request, int $matchId);
}
