<?php

namespace App\Http\Middleware;

use App\Services\PeriodeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SinkronkanPeriode
{
    public function __construct(private PeriodeService $periodeService) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->periodeService->sinkronkan();

        return $next($request);
    }
}
