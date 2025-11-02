<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AuthService
{
    /**
     * Attempt login using Passport password grant token.
     */
    public function attemptLogin(string $login, string $password, string $scope = ''): array
    {
        // Lire les identifiants du password client
        $client = DB::table('oauth_clients')->where('password_client', true)->first();

        if (!$client) {
            return [
                'status' => 500,
                'body' => ['message' => 'Aucun client OAuth de type password trouvé'],
                'cookies' => []
            ];
        }

        // Déterminer l'utilisateur
        $user = User::where('login', $login)->first();

        if (!$user) {
            return [
                'status' => 404,
                'body' => ['message' => 'Utilisateur introuvable'],
                'cookies' => []
            ];
        }

        // Déterminer scope automatiquement si non fourni
        if (empty($scope)) {
            $scope = $user->is_admin ? 'admin client' : 'client';
        }

        // Appel interne au endpoint OAuth
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
            'cookies' => []
        ];
    }

    /**
     * Logout
     */
    public function logout(Request $request): array
    {
        $token = $request->user()?->token();

        if ($token) {
            $token->revoke();
            DB::table('oauth_refresh_tokens')->where('access_token_id', $token->id)->update(['revoked' => true]);
        }

        return [
            'status' => 200,
            'body' => ['message' => 'Logged out'],
            'cookies' => []
        ];
    }
}
