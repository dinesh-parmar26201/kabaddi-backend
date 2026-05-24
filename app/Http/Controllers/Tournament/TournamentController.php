<?php

namespace App\Http\Controllers\Tournament;

use Illuminate\Http\Request;
use App\Models\Tournament;
use App\DTO\TeamResponseDTO;
use App\DTO\MatchResponseDTO;
use App\DTO\TournamentResponseDTO;
use App\Http\Controllers\Controller;
use App\Services\Tournament\TournamentServiceInterface;
use App\Http\Requests\Tournament\StoreTournamentRequest;
use App\Http\Requests\Tournament\UpdateTournamentRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\Tournament\AddTournamentTeamsRequest;

class TournamentController extends Controller
{

    use AuthorizesRequests;

    public function __construct(
        private TournamentServiceInterface $service
    ) {}

    public function store(StoreTournamentRequest $request)
    {
        $this->authorize('create', Tournament::class);
        $tournament = $this->service->store($request);

        return response()->json([
            'message' => 'Tournament created successfully',
            'data' => TournamentResponseDTO::fromModel($tournament)
        ]);
    }

    public function update(UpdateTournamentRequest $request, $id)
    {
        $tournament = Tournament::findOrFail($id);
        $this->authorize('update', $tournament);
        $tournament = $this->service->update($id, $request);

        return response()->json([
            'message' => 'Tournament updated successfully',
            'data' => TournamentResponseDTO::fromModel($tournament)
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Tournament::class);

        $tournaments = $this->service->list($request->search);

        $tournaments->getCollection()->transform(function ($tournament) {
            return TournamentResponseDTO::fromModel($tournament);
        });

        return response()->json([
            'message' => 'Tournaments retrieved successfully',
            'data' => $tournaments
        ]);
    }

    public function show($id)
    {
        $this->authorize('view', Tournament::class);
        return response()->json(
            [
                'message' => 'Tournament retrieved successfully',
                'data' =>
                TournamentResponseDTO::fromModel(
                    $this->service->find($id)
                )
            ]
        );
    }

    public function destroy($id)
    {
        $tournament = Tournament::findOrFail($id);
        $this->authorize('delete', $tournament);
        $this->service->delete($id);
        return response()->json([
            'message' => 'Tournament deleted successfully'
        ]);
    }

    public function addTeams(AddTournamentTeamsRequest $request, $id)
    {
        $tournament = Tournament::findOrFail($id);
        $this->authorize('addTeams', $tournament);

        $tournament = $this->service->addTeams(
            $id,
            $request->team_ids
        );

        return response()->json([
            'message' => 'Teams added to tournament successfully',
            'data' => TournamentResponseDTO::fromModel($tournament)
        ]);
    }

    public function getTeams($id)
    {
        $this->authorize('view', Tournament::class);

        $teams = $this->service->getTeams($id);

        return response()->json([
            'message' => 'Tournament teams retrieved successfully',
            'data' => TeamResponseDTO::fromModels($teams)
        ]);
    }

    public function getMatches($id)
    {
        $this->authorize('view', Tournament::class);

        $tournament = Tournament::findOrFail($id);
        $perPage = (int) request()->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;
        $matches = $tournament->matches()->latest()->paginate($perPage);

        $matches->getCollection()->transform(function ($match) {
            return MatchResponseDTO::fromModel($match, ['teams', 'raids', 'teamBreakdowns']);
        });

        return response()->json([
            'message' => 'Tournament matches retrieved successfully',
            'data' => $matches
        ]);
    }

    public function removeTeam(int $id, int $teamId)
    {
        $tournament = Tournament::findOrFail($id);
        $this->authorize('removeTeam', $tournament);

        if (!$tournament->teams()->where('team_id', $teamId)->exists()) {
            return response()->json([
                'message' => 'Team not found in tournament'
            ], 200);
        }

        $this->service->removeTeam($id, $teamId);

        return response()->json([
            'message' => 'Team removed from tournament successfully'
        ]);
    }
}
