<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\Team\TeamController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Match\MatchController;
use App\Http\Controllers\Raid\RaidController;
use App\Http\Controllers\ScoreboardController;
use App\Http\Controllers\Tournament\TournamentController;
use App\Http\Controllers\User\PlayerStatsController;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::prefix('players')->group(function () {
        Route::post('update', [UserController::class, 'update']);
        Route::get('profile', [UserController::class, 'profile']);
        Route::post('search', [UserController::class, 'search']);
        Route::get('stats', [PlayerStatsController::class, 'show']);
    });

    Route::prefix('team')->group(function () {
        Route::post('', [TeamController::class, 'store']);
        Route::post('{id}', [TeamController::class, 'update']);
        Route::get('list', [TeamController::class, 'index']);
        Route::get('{id}', [TeamController::class, 'show']);
        Route::delete('{id}', [TeamController::class, 'destroy']);
        Route::post('{id}/add-player', [TeamController::class, 'addPlayer']);
        Route::post('{id}/remove-player/{player_id}', [TeamController::class, 'removePlayer']);
        Route::get('{id}/matches', [TeamController::class, 'matches']);
    });

    Route::prefix('tournament')->group(function () {
        Route::get('list', [TournamentController::class, 'index']);
        Route::post('', [TournamentController::class, 'store']);
        Route::get('{id}', [TournamentController::class, 'show']);
        Route::post('{id}', [TournamentController::class, 'update']);
        Route::delete('{id}', [TournamentController::class, 'destroy']);
        Route::post('{id}/teams', [TournamentController::class, 'addTeams']);
        Route::post('{id}/remove-team/{team_id}', [TournamentController::class, 'removeTeam']);
        Route::get('{id}/teams', [TournamentController::class, 'getTeams']);
        Route::get('{id}/matches', [TournamentController::class, 'getMatches']);
    });

    Route::prefix('match')->group(function () {
        Route::get('list', [MatchController::class, 'index']);
        Route::post('', [MatchController::class, 'store']);
        Route::get('{id}', [MatchController::class, 'show']);
        Route::post('{id}', [MatchController::class, 'update']);
        Route::delete('{id}', [MatchController::class, 'destroy']);
        Route::post('{id}/toss', [MatchController::class, 'toss']);
        Route::post('{id}/team-players', [MatchController::class, 'updateTeamPlayers']);
        Route::post('{id}/team-court', [MatchController::class, 'updateTeamCourt']);
        Route::post('{id}/swap-player', [MatchController::class, 'swap']);
        Route::post('{id}/card', [MatchController::class, 'updateCard']);
    });

    Route::prefix('matches/{match}')->group(function () {
        Route::get('/raids', [RaidController::class, 'index']);
        Route::post('/raids', [RaidController::class, 'store']);
        Route::post('/raids/skip', [RaidController::class, 'skip']);
        Route::post('/raids/{raid}', [RaidController::class, 'update']);
        Route::delete('/raids/undo', [RaidController::class, 'undo']);
    });

    Route::prefix('matches')->group(function () {
        Route::get('{match}/scoreboard', [ScoreboardController::class, 'show']);
    });

    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index']);
        Route::post('/', [EventController::class, 'store']);
        Route::post('{event}', [EventController::class, 'update']);
    });

    Route::post('logout', [AuthController::class, 'logout']);
});
