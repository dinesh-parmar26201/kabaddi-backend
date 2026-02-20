<?php

namespace App\Http\Controllers\Raid;

use App\Http\Controllers\Controller;
use App\Http\Requests\Raid\StoreRaidRequest;
use App\DTO\RaidResponseDTO;
use App\Services\Raid\RaidServiceInterface;

class RaidController extends Controller
{
    public function __construct(
        protected RaidServiceInterface $raidService
    ) {}

    public function index(int $match)
    {
        $raids = $this->raidService->getRaidsByMatch($match);

        return response()->json([
            'success' => true,
            'data' => $raids->map(fn($raid) => RaidResponseDTO::fromModel($raid))
        ]);
    }
    
    public function store(StoreRaidRequest $request, int $match)
    {
        $raid = $this->raidService->store($match, $request->validated());

        return response()->json([
            'success' => true,
            'data' => RaidResponseDTO::fromModel($raid)
        ]);
    }

    public function update(int $match, int $raid, StoreRaidRequest $request)
    {
        $raid = $this->raidService->update($match, $raid, $request->validated());

        return response()->json([
            'success' => true,
            'data' => RaidResponseDTO::fromModel($raid)
        ]);
    }

    public function undo(int $match)
    {
        $this->raidService->undoLastRaid($match);

        return response()->json([
            'success' => true,
            'message' => 'Last raid undone successfully.'
        ]);
    }
}
