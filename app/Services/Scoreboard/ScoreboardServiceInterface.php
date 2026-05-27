<?php


namespace App\Services\Scoreboard;

use App\DTO\ScoreboardResponseDTO;
use App\Models\GameMatch;
use App\Models\MatchPlayer;

interface ScoreboardServiceInterface
{
    public function getMatchScoreboard(int $matchId, ?int $half = null): ScoreboardResponseDTO;

    public function getMatchPlayerStats(GameMatch $match, MatchPlayer $matchPlayer);
}
