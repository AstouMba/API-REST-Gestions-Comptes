<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthService
{
    /**
     * Attempt login using Passport password grant and return structured result.
     *
     * @param string $login
     * @param string $password
     * @param string $scope
     * @return array ['status' => int, 'body' => array, 'cookies' => array]
     */
 public function attemptLogin(string $login, string $password, string $scope = ''): array
{
    $passwordClientId = env('PASSPORT_PASSWORD_CLIENT_ID');
    $passwordClientSecret = env('PASSPORT_PASSWORD_CLIENT_SECRET');
    
    // Fallback: lire depuis la base de données
    if (!$passwordClientId || !$passwordClientSecret) {
        try {
            $client = DB::table('oauth_clients')->where('password_client', true)->first();
            if ($client) {
                $passwordClientId = $client->id;
                $passwordClientSecret = $client->secret ?? $passwordClientSecret;
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    if (!$passwordClientId || !$passwordClientSecret) {
        return [
            'status' => 500,
            'body' => ['message' => 'Password grant client not configured'],
            'cookies' => [],
        ];
    }

    // ⚠️ NOUVEAU : Déterminer le scope automatiquement si non fourni
    if (empty($scope)) {
        // Vérifier si l'utilisateur existe et est admin
        $user = \App\Models\User::where('login', $login)->first();
        if ($user && $user->is_admin) {
            $scope = 'admin client'; // Admin a tous les droits
        } else {
            $scope = 'client'; // Client standard
        }
    }

    $params = [
        'grant_type' => 'password',
        'client_id' => $passwordClientId,
        'client_secret' => $passwordClientSecret,
        'username' => $login, // Passport utilisera la méthode username() du modèle
        'password' => $password,
        'scope' => $scope,
    ];

    // Appeler l'endpoint OAuth interne
    $tokenRequest = request()->create('/oauth/token', 'POST', $params);
    $response = app()->handle($tokenRequest);

    $status = $response->getStatusCode();
    $data = json_decode($response->getContent(), true);

    if ($status !== 200) {
        return ['status' => $status, 'body' => $data, 'cookies' => []];
    }

    // Créer les cookies sécurisés
    $accessMinutes = (int) env('PASSPORT_ACCESS_TOKEN_EXPIRES', 60);
    $refreshDays = (int) env('PASSPORT_REFRESH_TOKEN_EXPIRES_DAYS', 30);

    $secure = env('APP_ENV') !== 'local';
    $sameSite = 'Strict';

    $accessCookie = cookie('access_token', $data['access_token'], $accessMinutes, '/', null, $secure, true, false, $sameSite);
    $refreshCookie = cookie('refresh_token', $data['refresh_token'], $refreshDays * 24 * 60, '/', null, $secure, true, false, $sameSite);

    return [
        'status' => 200,
        'body' => [
            'message' => 'Authenticated',
            'user' => [
                'login' => $login,
                'is_admin' => $user->is_admin ?? false,
            ],
            'scopes' => explode(' ', $scope),
            'access_token' => $data['access_token'] ?? null,
            'expires_in' => $data['expires_in'] ?? null,
            'token_type' => $data['token_type'] ?? null,
        ],
        'cookies' => [$refreshCookie],
    ];
}

    /**
     * Refresh access token using refresh token (from cookie or provided string).
     *
     * @param string|null $refreshToken
     * @return array
     */
    public function refreshToken(?string $refreshToken): array
    {
        if (!$refreshToken) {
            return ['status' => 400, 'body' => ['message' => 'Refresh token not provided'], 'cookies' => []];
        }

        $passwordClientId = env('PASSPORT_PASSWORD_CLIENT_ID');
        $passwordClientSecret = env('PASSPORT_PASSWORD_CLIENT_SECRET');

        // Fallback to DB if env not set
        if (!$passwordClientId || !$passwordClientSecret) {
            try {
                $client = DB::table('oauth_clients')->where('password_client', true)->first();
                if ($client) {
                    $passwordClientId = $client->id;
                    $passwordClientSecret = $client->secret ?? $passwordClientSecret;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $passwordClientId,
            'client_secret' => $passwordClientSecret,
        ];

        $tokenRequest = request()->create('/oauth/token', 'POST', $params);
        $response = app()->handle($tokenRequest);

        $status = $response->getStatusCode();
        $data = json_decode($response->getContent(), true);

        if ($status !== 200) {
            return ['status' => $status, 'body' => $data, 'cookies' => []];
        }

        $accessMinutes = (int) env('PASSPORT_ACCESS_TOKEN_EXPIRES', 60);
        $refreshDays = (int) env('PASSPORT_REFRESH_TOKEN_EXPIRES_DAYS', 30);
        $secure = env('APP_ENV') !== 'local';
        $sameSite = 'Strict';

        $accessCookie = cookie('access_token', $data['access_token'], $accessMinutes, '/', null, $secure, true, false, $sameSite);
        $refreshCookie = cookie('refresh_token', $data['refresh_token'], $refreshDays * 24 * 60, '/', null, $secure, true, false, $sameSite);

        return ['status' => 200, 'body' => ['message' => 'Token refreshed'], 'cookies' => [$accessCookie, $refreshCookie]];
    }

    /**
     * Logout: revoke current access token and associated refresh tokens.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function logout(Request $request): array
    {
        $user = $request->user();

        if ($user && $user->token()) {
            $accessTokenId = $user->token()->id;

            $user->token()->revoke();

            DB::table('oauth_refresh_tokens')->where('access_token_id', $accessTokenId)->update(['revoked' => true]);
        } else {
            return ['status' => 401, 'body' => ['message' => 'Unauthenticated.'], 'cookies' => []];
        }

        $accessCookie = cookie()->forget('access_token');
        $refreshCookie = cookie()->forget('refresh_token');

        return ['status' => 200, 'body' => ['message' => 'Logged out'], 'cookies' => [$accessCookie, $refreshCookie]];
    }
}
