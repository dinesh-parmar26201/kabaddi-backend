<?php

namespace App\Http\Controllers\Match;

use Illuminate\Http\Request;
use App\DTO\MatchResponseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Match\TossRequest;
use App\Services\Match\MatchServiceInterface;
use App\Http\Requests\Match\CreateMatchRequest;
use App\Http\Requests\Match\UpdateMatchRequest;

class MatchController extends Controller
{
    public function __construct(
        protected MatchServiceInterface $matchService
    ) {}

    public function store(CreateMatchRequest $request)
    {
        $match = $this->matchService->create($request->validated());

        return response()->json(
            MatchResponseDTO::fromModel($match)
        );
    }

    public function update(UpdateMatchRequest $request, int $id)
    {
        $match = $this->matchService->update($id, $request->validated());

        return response()->json(
            MatchResponseDTO::fromModel($match)
        );
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->matchService->list($request->all())
        );
    }

    public function show(int $id)
    {
        return response()->json(
            MatchResponseDTO::fromModel(
                $this->matchService->detail($id)
            )
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
}
