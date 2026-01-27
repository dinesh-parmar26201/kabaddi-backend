<?php

namespace App\Http\Controllers\Tournament;

use App\DTO\TournamentResponseDTO;
use App\Http\Controllers\Controller;
use App\Services\Tournament\TournamentServiceInterface;
use App\Http\Requests\Tournament\StoreTournamentRequest;
use App\Http\Requests\Tournament\UpdateTournamentRequest;

class TournamentController extends Controller
{
    public function __construct(
        private TournamentServiceInterface $service
    ) {}

    public function store(StoreTournamentRequest $request)
    {
        $tournament = $this->service->store($request);

        return response()->json(
            TournamentResponseDTO::fromModel($tournament)
        );
    }

    public function update(UpdateTournamentRequest $request, $id)
    {
        $tournament = $this->service->update($id, $request);

        return response()->json(
            TournamentResponseDTO::fromModel($tournament)
        );
    }

    public function index()
    {
        $tournaments = $this->service->list();

        return response()->json([
            'message' => 'Tournaments retrieved successfully',
            'data' => TournamentResponseDTO::collection($tournaments)
        ]
        );
    }

    public function show($id)
    {
        return response()->json(
            TournamentResponseDTO::fromModel(
                $this->service->find($id)
            )
        );
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Tournament deleted']);
    }
}
