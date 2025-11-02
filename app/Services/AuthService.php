<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;

class AuthService
{
    public function attemptLogin(string $login, string $password, string $scope = ''): array
    {
        // Try to get client from env first, fallback to database
        $clientId = env('PASSPORT_PASSWORD_CLIENT_ID');
        $clientSecret = env('PASSPORT_PASSWORD_CLIENT_SECRET');

        if ($clientId && $clientSecret) {
            $client = (object) [
                'id' => $clientId,
                'secret' => $clientSecret
            ];
        } else {
            $client = DB::table('oauth_clients')->where('password_client', true)->first();
        }

        if (!$client) {
            return ['status' => 500, 'body' => ['message' => 'Password grant client not configured'], 'cookies' => []];
        }

        $user = User::where('login', $login)->first();
        if (!$user) {
            return ['status' => 404, 'body' => ['message' => 'Utilisateur introuvable'], 'cookies' => []];
        }

        if (!Hash::check($password, $user->password)) {
            return ['status' => 401, 'body' => ['message' => 'Identifiants incorrects'], 'cookies' => []];
        }

        if (empty($scope)) {
            $scope = $user->is_admin ? 'admin' : 'client';
        }

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
            return ['status' => $status, 'body' => $data ?? ['message' => 'Authentication failed'], 'cookies' => []];
        }

        $accessCookie = cookie('access_token', $data['access_token'], 60, '/', null, env('APP_ENV') !== 'local', true);
        $refreshCookie = cookie('refresh_token', $data['refresh_token'], 30*24*60, '/', null, env('APP_ENV') !== 'local', true);

        return [
            'status' => 200,
            'body' => [
                'message' => 'Authenticated',
                'user' => ['id'=>$user->id,'login'=>$user->login,'is_admin'=>$user->is_admin],
                'access_token'=>$data['access_token'],
                'refresh_token'=>$data['refresh_token'],
                'token_type'=>$data['token_type'],
                'expires_in'=>$data['expires_in'],
                'scope'=>explode(' ',$scope)
            ],
            'cookies'=>[$accessCookie,$refreshCookie]
        ];
    }
}
