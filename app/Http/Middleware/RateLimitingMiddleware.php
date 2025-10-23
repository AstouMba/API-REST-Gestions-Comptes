<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->ip();
        $maxAttempts = 100;
        $decayMinutes = 1;

        if (cache()->get("rate_limit_{$key}", 0) >= $maxAttempts) {
            return response()->json(['message' => 'Too many requests'], 429);
        }

        cache()->put("rate_limit_{$key}", cache()->get("rate_limit_{$key}", 0) + 1, $decayMinutes * 60);

        return $next($request);
    }
}
