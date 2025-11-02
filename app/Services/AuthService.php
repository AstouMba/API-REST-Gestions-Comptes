<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AuthService
{
    /**
     * Attempt login using Passport password grant and return structured result with cookies.
     */
    public function attemptLogin(string $login, string $password, string $scope = ''): array
    {
        // Récupérer le client password OAuth
        $client = DB::table('oauth_clients')->where('password_client', true)->first();

        if (!$client) {
            return [
                'status' => 500,
                'body' => ['message' => 'Password grant client not configured'],
                'cookies' => []
            ];
        }

        // Vérifier l'utilisateur
        $user = User::where('login', $login)->first();
        if (!$user) {
            return [
                'status' => 404,
                'body' => ['message' => 'Utilisateur introuvable'],
                'cookies' => []
            ];
        }

        // Déterminer automatiquement le scope si non fourni
        if (empty($scope)) {
            $scope = $user->is_admin ? 'admin' : 'client';
        }

        // Préparer la requête interne pour obtenir le token
        $params = [
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'username' => $login,
            'password' => $password,
            'scope' => $scope,
        ];

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
                    'id' => $user->id,
                    'login' => $user->login,
                    'is_admin' => $user->is_admin,
                ],
                'token_type' => $data['token_type'],
                'expires_in' => $data['expires_in'],
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'scope' => explode(' ', $scope)
            ],
            'cookies' => [$accessCookie, $refreshCookie]
        ];
    }

    /**
     * Refresh access token using refresh token.
     */
    public function refreshToken(?string $refreshToken): array
    {
        if (!$refreshToken) {
            return ['status' => 400, 'body' => ['message' => 'Refresh token not provided'], 'cookies' => []];
        }

        $client = DB::table('oauth_clients')->where('password_client', true)->first();
        if (!$client) {
            return ['status' => 500, 'body' => ['message' => 'Password grant client not configured'], 'cookies' => []];
        }

        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $client->id,
            'client_secret' => $client->secret,
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

        return [
            'status' => 200,
            'body' => ['message' => 'Token refreshed', 'access_token' => $data['access_token']],
            'cookies' => [$accessCookie, $refreshCookie]
        ];
    }

    /**
     * Logout: revoke access token and refresh tokens.
     */
    public function logout(Request $request): array
    {
        $user = $request->user();
        if ($user && $user->token()) {
            $accessTokenId = $user->token()->id;
            $user->token()->revoke();
            DB::table('oauth_refresh_tokens')->where('access_token_id', $accessTokenId)->update(['revoked' => true]);
        }

        $accessCookie = cookie()->forget('access_token');
        $refreshCookie = cookie()->forget('refresh_token');

        return [
            'status' => 200,
            'body' => ['message' => 'Logged out'],
            'cookies' => [$accessCookie, $refreshCookie]
        ];
    }
}
