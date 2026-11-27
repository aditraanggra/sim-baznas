<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PastikanAksesBidang;
use App\Http\Middleware\PastikanDuaFaktor;
use App\Http\Middleware\PastikanPeran;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            PastikanDuaFaktor::class,
        ]);

        $middleware->alias([
            'bidang' => PastikanAksesBidang::class,
            'peran' => PastikanPeran::class,
            'wajib.2fa' => PastikanDuaFaktor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response) {
            return match ($response->getStatusCode()) {
                403 => Inertia::render('error', ['status' => 403, 'pesan' => 'Anda tidak memiliki akses ke halaman ini.'])
                    ->toResponse(request())
                    ->setStatusCode(403),
                404 => Inertia::render('error', ['status' => 404, 'pesan' => 'Halaman tidak ditemukan.'])
                    ->toResponse(request())
                    ->setStatusCode(404),
                default => $response,
            };
        });
    })->create();
