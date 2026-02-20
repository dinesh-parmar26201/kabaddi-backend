<?php

namespace App\Http\Controllers;

use App\Services\Scoreboard\ScoreboardServiceInterface;
use Illuminate\Http\JsonResponse;

class ScoreboardController extends Controller
{
    public function __construct(
        private readonly ScoreboardServiceInterface $scoreboardService
    ) {}

    public function show(int $matchId): JsonResponse
    {
        $response = $this->scoreboardService->getMatchScoreboard($matchId);

        return response()->json($response);
    }
}
