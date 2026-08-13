<?php

namespace App\Http\Controllers\Match;

use App\DTO\MatchResponseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Match\CreateMatchRequest;
use App\Http\Requests\Match\SwapPlayerRequest;
use App\Http\Requests\Match\TossRequest;
use App\Http\Requests\Match\UpdateMatchRequest;
use App\Http\Requests\Match\UpdateMatchTeamCourtRequest;
use App\Http\Requests\Match\UpdateMatchTeamPlayersRequest;
use App\Http\Requests\Match\UpdateMatchPlayerCardRequest;
use App\Http\Requests\Match\UpdateMatchPlayerSubstitutesRequest;
use App\Services\Match\MatchServiceInterface;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(
        protected MatchServiceInterface $matchService
    ) {}

    public function store(CreateMatchRequest $request)
    {
        $match = $this->matchService->create($request);

        return response()->json(
            [
                "message" => "Match created successfully",
                "data" => MatchResponseDTO::fromModel($match, ['teams'])
            ]
        );
    }

    public function update(UpdateMatchRequest $request, int $id)
    {
        $match = $this->matchService->update($id, $request->validated());

        return response()->json(
            [
                "message" => "Match updated successfully",
                "data" => MatchResponseDTO::fromModel($match)
            ]
        );
    }

    public function index(Request $request)
    {
        $matches = $this->matchService->list($request->all());

        $matches->getCollection()->transform(function ($match) {
            return MatchResponseDTO::fromModel($match, ['teams', 'teamBreakdowns']);
        });

        return response()->json(
            [
                'message' => 'Match list fetched successfully',
                'data' => $matches
            ]
        );
    }

    public function show(int $id)
    {
        return response()->json(
            [
                'message' => 'Match details fetched successfully',
                'data' =>
                MatchResponseDTO::fromModel(
                    $this->matchService->detail($id),
                    ['teams', 'raids', 'teamBreakdowns', 'tournament']
                )
            ]
        );
    }

    public function destroy(int $id)
    {
        $this->matchService->delete($id);
        return response()->json(['message' => 'Match deleted successfully']);
    }

    public function toss(TossRequest $request, int $id)
    {
        return response()->json(
            $this->matchService->toss($id, $request->validated())
        );
    }

    public function updateTeamPlayers(UpdateMatchTeamPlayersRequest $request, int $id)
    {
        $match = $this->matchService->updateTeamPlayers($request, $id);

        return response()->json(
            [
                "message" => "Match team players updated successfully",
                "data" => MatchResponseDTO::fromModel($match, ['teams'])
            ]
        );
    }

    public function updateTeamCourt(UpdateMatchTeamCourtRequest $request, int $id)
    {
        $match = $this->matchService->updateTeamCourt($request, $id);

        return response()->json(
            [
                "message" => "Match team court updated successfully",
                "data" => MatchResponseDTO::fromModel($match, ['teams'])
            ]
        );
    }

    public function swap(SwapPlayerRequest $request, int $matchId)
    {
        $this->matchService->swapPlayers($matchId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Players swapped successfully'
        ]);
    }

    public function updateCard(UpdateMatchPlayerCardRequest $request, int $matchId)
    {
        $match = $this->matchService->updateCard($request, $matchId);

        return response()->json([
            'message' => 'Card updated successfully',
            'data' => MatchResponseDTO::fromModel($match, ['teams'])
        ]);
    }

    public function summary(int $id)
    {
        $data = $this->matchService->summary($id);

        return response()->json([
            'message' => 'Match summary fetched successfully',
            'data' => $data
        ]);
    }

    public function scorecard(int $id)
    {
        $data = $this->matchService->scorecard($id);

        return response()->json([
            'message' => 'Match scorecard fetched successfully',
            'data' => $data
        ]);
    }

    public function playByPlay(int $id)
    {
        $data = $this->matchService->playByPlay($id);

        return response()->json([
            'message' => 'Match play by play fetched successfully',
            'data' => $data
        ]);
    }

    public function bestPerformance(int $id)
    {
        $data = $this->matchService->bestPerformance($id);

        return response()->json([
            'message' => 'Match best performance fetched successfully',
            'data' => $data
        ]);
    }

    public function playerStats(int $matchId, int $playerId)
    {
        $data = $this->matchService->playerStats($matchId, $playerId);

        return response()->json([
            'message' => 'Match player stats fetched successfully',
            'data' => $data
        ]);
    }

    public function updateSubstitutes(UpdateMatchPlayerSubstitutesRequest $request, int $matchId)
    {
        $match = $this->matchService->updateSubstitutes($request, $matchId);

        return response()->json([
            'message' => 'Match substitutes updated successfully',
            'data' => MatchResponseDTO::fromModel($match, ['teams'])
        ]);
    }
}
