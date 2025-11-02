<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'=>'required|string',
            'password'=>'required|string',
            'scope'=>'sometimes|string'
        ]);

        $result = $this->authService->attemptLogin(
            $request->input('login'),
            $request->input('password'),
            $request->input('scope','')
        );

        $resp = response()->json($result['body'],$result['status']);
        foreach($result['cookies'] as $cookie){
            $resp->withCookie($cookie);
        }

        return $resp;
    }
}
