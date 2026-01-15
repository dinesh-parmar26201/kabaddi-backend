<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ServiceBindingProvider extends ServiceProvider
{
    public array $bindings = [
        \App\Services\Auth\AuthServiceInterface::class => \App\Services\Auth\AuthService::class,
        \App\Services\User\UserServiceInterface::class => \App\Services\User\UserService::class,
    ];
}
