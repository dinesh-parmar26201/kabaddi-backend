<?php

namespace App\Services\Raid;

use App\Models\Raid;

interface RaidServiceInterface
{
    public function getRaidsByMatch(int $matchId);

    public function store(int $matchId, array $data): Raid;

    public function update(int $matchId, int $raidId, array $data): Raid;

    public function undoLastRaid(int $matchId): void;

    public function skip(int $matchId, array $data): Raid;
}
