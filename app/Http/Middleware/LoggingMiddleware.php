<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        $logData = [
            'date' => now()->toDateString(),
            'heure' => now()->toTimeString(),
            'host' => $request->getHost(),
            'operation' => $request->method(),
            'ressource' => $request->path(),
            'status' => $response->getStatusCode(),
            'utilisateur' => $user ? $user->login : 'anonymous',
            'compteId' => $request->route('compteId') ?? null,
        ];

        Log::info('API Request', $logData);

        return $response;
    }
}
