<?php

use App\Http\Middleware\PeriksaPeran;
use App\Http\Middleware\SinkronkanPeriode;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Kecualikan webhook Midtrans dari CSRF (Midtrans tidak mengirim token CSRF)
        $middleware->validateCsrfTokens(except: [
            'midtrans/notification',
        ]);

        $middleware->alias([
            'peran' => PeriksaPeran::class,
            'sinkronkan.periode' => SinkronkanPeriode::class,
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: function () {
                $user = Auth::user();

                return match ($user?->peran) {
                    User::PERAN_ADMIN => '/admin/dashboard',
                    User::PERAN_CALON_SANTRI => '/status-pendaftaran',
                    User::PERAN_SANTRI => '/tagihan',
                    default => '/',
                };
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
