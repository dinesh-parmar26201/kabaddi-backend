<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ServiceBindingProvider extends ServiceProvider
{
    public array $bindings = [
        \App\Services\Auth\AuthServiceInterface::class => \App\Services\Auth\AuthService::class,
        \App\Services\User\UserServiceInterface::class => \App\Services\User\UserService::class,
        \App\Services\Team\TeamServiceInterface::class => \App\Services\Team\TeamService::class,
        \App\Services\Tournament\TournamentServiceInterface::class => \App\Services\Tournament\TournamentService::class,
        \App\Services\Match\MatchServiceInterface::class => \App\Services\Match\MatchService::class,
        \App\Services\Raid\RaidServiceInterface::class => \App\Services\Raid\RaidService::class,
    ];
}
