<?php

namespace App\Http\Controllers\Tournament;

use App\Models\Tournament;
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

    public function index()
    {
        $this->authorize('viewAny', Tournament::class);
        $tournaments = $this->service->list();

        return response()->json(
            [
                'message' => 'Tournaments retrieved successfully',
                'data' => TournamentResponseDTO::collection($tournaments)
            ]
        );
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
}
