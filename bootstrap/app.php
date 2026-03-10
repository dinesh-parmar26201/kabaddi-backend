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
    ->withExceptions(function (Exceptions $exceptions) {

        // Authentication errors
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
            ], 200);
        });


        // Validation errors
        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            $request
        ) {
            if (! $request->expectsJson()) {
                return;
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 200);
        });


        // Model not found (findOrFail)
        $exceptions->render(function (
            \Illuminate\Database\Eloquent\ModelNotFoundException $e,
            $request
        ) {
            if (! $request->expectsJson()) {
                return;
            }

            return response()->json([
                'success' => false,
                'message' => 'Record not found',
            ], 200);
        });


        // Route not found
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            $request
        ) {
            if (! $request->expectsJson()) {
                return;
            }

            return response()->json([
                'success' => false,
                'message' => 'Record not found',
            ], 200);
        });


        // Catch all other exceptions
        $exceptions->render(function (
            Throwable $e,
            $request
        ) {
            if (! $request->expectsJson()) {
                return;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 200);
        });
    })->create();
