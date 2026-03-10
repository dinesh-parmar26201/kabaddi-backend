<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\PlayerStats\PlayerStatsServiceInterface;

class PlayerStatsController extends Controller
{
    protected $service;

    public function __construct(PlayerStatsServiceInterface $service)
    {
        $this->service = $service;
    }

    public function show()
    {
        $stats = $this->service->getPlayerStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
