<?php

namespace App\Http\Controllers\Team;

use App\Models\Team;
use App\DTO\TeamResponseDTO;
use App\DTO\MatchResponseDTO;
use App\Http\Controllers\Controller;
use App\Services\Team\TeamServiceInterface;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Requests\Team\AddPlayerToTeamRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TeamController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TeamServiceInterface $teamService
    ) {}

    public function index()
    {
        $teams = $this->teamService->list();

        return response()->json([
            'message' => 'Teams retrieved successfully',
            'data' => TeamResponseDTO::collection($teams)
        ]);
    }

    public function store(StoreTeamRequest $request)
    {
        $this->authorize('create', Team::class);

        $team = $this->teamService->create($request);

        return response()->json(
            [
                'message' => 'Team created successfully',
                'data' => TeamResponseDTO::fromModel($team)
            ],
            201
        );
    }

    public function update(UpdateTeamRequest $request, int $id)
    {
        $team = Team::findOrFail($id);
        $this->authorize('update', $team);

        $team = $this->teamService->update($id, $request);

        return response()->json([
            'message' => 'Team updated successfully',
            'data' => TeamResponseDTO::fromModel($team)
        ]);
    }

    public function destroy(int $id)
    {
        $team = Team::findOrFail($id);
        $this->authorize('delete', $team);

        $this->teamService->delete($id);

        return response()->json([
            'message' => 'Team deleted successfully'
        ]);
    }

    public function addPlayer(AddPlayerToTeamRequest $request, int $id)
    {
        $team = Team::findOrFail($id);
        $this->authorize('addPlayer', $team);

        $this->teamService->addPlayer($team->id, $request);

        return response()->json([
            'message' => 'Players added successfully',
            'data' => TeamResponseDTO::fromModel($team)
        ]);
    }

    public function show(int $id)
    {
        $team = Team::with('players')->findOrFail($id);
        $this->authorize('view', $team);

        return response()->json([
            'message' => 'Team retrieved successfully',
            'data' => TeamResponseDTO::fromModel($team, ['players'])
        ]);
    }

    public function matches(int $id)
    {
        $team = Team::findOrFail($id);
        $this->authorize('view', $team);

        $matches = $this->teamService->getMatches($id);

        return response()->json([
            'message' => 'Team matches retrieved successfully',
            'data' => MatchResponseDTO::fromModels($matches, ['teams'])
        ]);
    }
}
