<?php


namespace App\Services\Scoreboard;

use App\DTO\ScoreboardResponseDTO;

interface ScoreboardServiceInterface
{
    public function getMatchScoreboard(int $matchId): ScoreboardResponseDTO;
}
