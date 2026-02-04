<?php

use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function ($exceptions) {
        $exceptions->render(function (
            AuthenticationException $e,
            $request
        ) {
            if (! $request->expectsJson()) {
                return;
            }

            $hasAuthHeader = $request->headers->has('authorization');

            return response()->json([
                'success' => false,
                'message' => $hasAuthHeader
                    ? 'Invalid or expired access token'
                    : 'Access token is missing',
                'error' => 'unauthenticated',
            ], Response::HTTP_UNAUTHORIZED);
        });
    })->create();
