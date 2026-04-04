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
        return response()->json(
            [
                'message' => 'Match list fetched successfully',
                'data' => MatchResponseDTO::fromModels(
                    $this->matchService->list(),
                    ['teams', 'teamBreakdowns']
                )
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
                    ['teams', 'raids', 'teamBreakdowns']
                )
            ]
        );
    }

    public function destroy(int $id)
    {
        $this->matchService->delete($id);
        return response()->json(['message' => 'Match cancelled']);
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
}
