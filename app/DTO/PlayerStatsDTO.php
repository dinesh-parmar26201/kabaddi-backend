<?php

namespace App\DTO;

class PlayerStatsDTO
{
    public function __construct(
        public int $playerId,
        public string|null $playerName,
        public string|null $photo,
        public int $teamId,
        public int $raidPoints,
        public int $tacklePoints,
        public int $superRaids,
        public int $superTackles,
        public int $bonusPoints,
        // public int $defenderLineoutPoints,
        public int $totalRaids,
        public int $totalPoints,
        public int $successfulRaids = 0,
        public int $unsuccessfulRaids = 0,
        public int $successfulTackles = 0,
        public int $unsuccessfulTackles = 0,
        public int $mvpScore = 0
    ) {}
}
