<?php

namespace App\Services\PlayerStats;

interface PlayerStatsServiceInterface
{
    public function getPlayerStats(int $playerId): array;
}
