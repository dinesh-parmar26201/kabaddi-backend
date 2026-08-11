<?php

namespace App\Services\Match;

use App\Http\Requests\Match\CreateMatchRequest;
use App\Http\Requests\Match\UpdateMatchTeamCourtRequest;
use App\Http\Requests\Match\UpdateMatchTeamPlayersRequest;
use App\Http\Requests\Match\UpdateMatchPlayerCardRequest;
use App\Http\Requests\Match\UpdateMatchPlayerSubstitutesRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MatchServiceInterface
{
    public function create(CreateMatchRequest $request);
    public function update(int $matchId, array $data);
    public function list(array $filters = []): LengthAwarePaginator;
    public function detail(int $matchId);
    public function delete(int $matchId);
    public function toss(int $matchId, array $data);
    public function updateTeamPlayers(UpdateMatchTeamPlayersRequest $request, int $matchId);
    public function updateTeamCourt(UpdateMatchTeamCourtRequest $request, int $matchId);
    public function swapPlayers(int $matchId, array $data): void;
    public function updateCard(UpdateMatchPlayerCardRequest $request, int $matchId);
    public function summary(int $matchId);
    public function scorecard(int $matchId);
    public function playByPlay(int $matchId);
    public function bestPerformance(int $matchId);
    public function playerStats(int $matchId, int $playerId);
    public function updateSubstitutes(UpdateMatchPlayerSubstitutesRequest $request, int $matchId);
}
