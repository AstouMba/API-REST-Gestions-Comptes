<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle login request: validate input, delegate to service, and return response.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'scope' => 'sometimes|string',
        ]);

        $result = $this->authService->attemptLogin($request->input('login'), $request->input('password'), $request->input('scope', ''));

        $resp = response()->json($result['body'], $result['status']);

        foreach ($result['cookies'] as $cookie) {
            $resp->withCookie($cookie);
        }

        return $resp;
    }

    /**
     * Handle refresh request.
     */
    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token') ?? $request->input('refresh_token');

        $result = $this->authService->refreshToken($refreshToken);

        $resp = response()->json($result['body'], $result['status']);
        foreach ($result['cookies'] as $cookie) {
            $resp->withCookie($cookie);
        }

        return $resp;
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        $result = $this->authService->logout($request);

        $resp = response()->json($result['body'], $result['status']);
        foreach ($result['cookies'] as $cookie) {
            $resp->withCookie($cookie);
        }

        return $resp;
    }
}
