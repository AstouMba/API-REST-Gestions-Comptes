<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request and ensure token has required scope(s).
     * Usage: ->middleware('role:admin') or ->middleware('role:client')
     */
    public function handle(Request $request, Closure $next, $requiredScope = null)
    {
        // Si aucun scope requis, autoriser
        if (!$requiredScope) {
            return $next($request);
        }

        $user = $request->user();

        // Si pas d'utilisateur authentifié, refuser
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Supporte plusieurs scopes séparés par des virgules, par ex. 'admin,client'
        $scopes = array_map('trim', explode(',', $requiredScope));

        // Vérifier le scope du token
        if (method_exists($user, 'tokenCan')) {
            foreach ($scopes as $scope) {
                if ($scope === 'admin') {
                    if ($user->is_admin && $user->tokenCan('admin')) {
                        return $next($request);
                    }
                    continue;
                }

                // Pour les autres scopes (ex: client) on vérifie tokenCan
                if ($user->tokenCan($scope)) {
                    return $next($request);
                }
            }
        }

        return response()->json([
            'message' => 'Forbidden. Insufficient scope.',
            'required_scope' => $requiredScope,
        ], 403);
    }
}