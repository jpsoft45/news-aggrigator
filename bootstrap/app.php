<?php

use App\Console\Commands\FetchGuardianArticles;
use App\Console\Commands\FetchNewsArticles;
use App\Console\Commands\FetchNYTimesArticles;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withCommands(
        [
            FetchNewsArticles::class,
            FetchGuardianArticles::class,
            FetchNYTimesArticles::class,
        ]
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
