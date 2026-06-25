<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Auth proyek ini murni token-based (Bearer) — FE & BE beda root-domain
        // (lihat CLAUDE.md), jadi tidak memakai jalur SPA cookie/session Sanctum.
        // statefulApi() sengaja TIDAK dipakai: ia memaksa CSRF pada request dari
        // stateful domain (mis. localhost:3000) sehingga /api/login balas 419.
        $middleware->api(prepend: [
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam beberapa menit.',
                ], 429);
            }
        });
    })->create();
