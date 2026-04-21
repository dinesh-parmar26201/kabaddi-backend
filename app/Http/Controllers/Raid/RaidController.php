<?php

namespace App\Http\Controllers\Raid;

use App\Http\Controllers\Controller;
use App\Http\Requests\Raid\StoreRaidRequest;
use App\DTO\RaidResponseDTO;
use App\Http\Requests\Raid\StoreSkipRaidRequest;
use App\Http\Requests\Raid\UpdateRaidRequest;
use App\Services\Raid\RaidServiceInterface;

class RaidController extends Controller
{
    public function __construct(
        protected RaidServiceInterface $raidService
    ) {}

    public function index(int $match)
    {
        $raids = $this->raidService->getRaidsByMatch($match);

        $raids->getCollection()->transform(function ($raid) {
            return RaidResponseDTO::fromModel($raid);
        });

        return response()->json([
            'success' => true,
            'data' => $raids
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

    public function update(int $match, int $raid, UpdateRaidRequest $request)
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

    public function skip(StoreSkipRaidRequest $request, int $match)
    {
        $raid = $this->raidService->skip($match, $request->validated());

        return response()->json([
            'success' => true,
            'data' => RaidResponseDTO::fromModel($raid)
        ]);
    }
}
