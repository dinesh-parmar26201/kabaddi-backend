<?php

namespace App\Services\Match;

use Illuminate\Http\Request;

interface MatchServiceInterface
{
    public function create(array $data);
    public function update(int $matchId, array $data);
    public function list(array $filters = []);
    public function detail(int $matchId);
    public function delete(int $matchId);
    public function toss(int $matchId, array $data);
}
