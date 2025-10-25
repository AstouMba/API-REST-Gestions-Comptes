<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index()
    {
        $clients = Client::all();
        return ClientResource::collection($clients);
    }

    public function show($id)
    {
        $client = Client::findOrFail($id);
        return new ClientResource($client);
    }

    public function store(StoreClientRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $user = User::create([
                'id' => Str::uuid(),
                'login' => $request->input('login', $request->input('email')),
                'password' => bcrypt('password'), // Default password
                'is_admin' => false,
            ]);

            $client = Client::create([
                'id' => Str::uuid(),
                'utilisateur_id' => $user->id,
                'nom' => $request->input('nom'),
                'email' => $request->input('email'),
                'adresse' => $request->input('adresse'),
                'telephone' => $request->input('telephone'),
            ]);

            return new ClientResource($client);
        });
    }
}
