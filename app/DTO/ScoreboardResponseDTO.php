<?php

namespace App\DTO;

class ScoreboardResponseDTO
{
    public function __construct(
        public array $teamBreakdowns,
        public array $playerStats
    ) {}

    public function toArray(): array
    {
        return [
            'teams' => $this->teamBreakdowns,
            'players' => $this->playerStats,
        ];
    }
}
