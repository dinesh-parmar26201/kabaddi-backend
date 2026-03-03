<?php
namespace App\DTO;

class PlayerStatsDTO
{
    public function __construct(
        public int $playerId,
        public string $playerName,
        public int $teamId,
        public int $raidPoints,
        public int $tacklePoints,
        public int $superRaids,
        public int $superTackles,
        public int $bonusPoints,
        public int $totalPoints
    ) {}
}
