<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompteResource;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use Illuminate\Http\Request;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API Gestion Comptes",
 *     version="1.0.0",
 *     description="API for managing bank accounts"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Local server"
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    protected $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }

    /**
       * Get all accounts
       *
       * @OA\Get(
       *     path="/comptes",
       *     summary="Get all accounts",
       *     @OA\Response(
       *         response=200,
       *         description="List of accounts",
       *         @OA\JsonContent(
       *             type="array",
       *             @OA\Items(
       *                 @OA\Property(property="id", type="integer"),
       *                 @OA\Property(property="numero", type="string"),
       *                 @OA\Property(property="solde", type="number"),
       *                 @OA\Property(property="type", type="string"),
       *                 @OA\Property(property="status", type="string")
       *             )
       *         )
       *     )
       * )
       * @return \Illuminate\Http\JsonResponse
       */
    public function index(Request $request)
    {
        $comptes = $this->compteService->getAllComptes();
        return $this->successResponse(CompteResource::collection($comptes), 'Comptes retrieved successfully');
    }

    /**
       * Get a specific account
       *
       * @OA\Get(
       *     path="/comptes/{numero}",
       *     summary="Get a specific account",
       *     @OA\Parameter(
       *         name="numero",
       *         in="path",
       *         required=true,
       *         @OA\Schema(type="string")
       *     ),
       *     @OA\Response(
       *         response=200,
       *         description="Account details",
       *         @OA\JsonContent(
       *             @OA\Property(property="id", type="integer"),
       *             @OA\Property(property="numero", type="string"),
       *             @OA\Property(property="solde", type="number"),
       *             @OA\Property(property="type", type="string"),
       *             @OA\Property(property="status", type="string")
       *         )
       *     )
       * )
       */
    public function show($numero)
    {
        $compte = $this->compteService->getCompteByNumero($numero);
        return $this->successResponse(new CompteResource($compte), 'Compte retrieved successfully');
    }

    /**
       * Create a new account
       *
       * @OA\Post(
       *     path="/comptes",
       *     summary="Create a new account",
       *     @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
       *             @OA\Property(property="numero", type="string"),
       *             @OA\Property(property="solde", type="number"),
       *             @OA\Property(property="type", type="string"),
       *             @OA\Property(property="status", type="string")
       *         )
       *     ),
       *     @OA\Response(
       *         response=201,
       *         description="Account created",
       *         @OA\JsonContent(
       *             @OA\Property(property="id", type="integer"),
       *             @OA\Property(property="numero", type="string"),
       *             @OA\Property(property="solde", type="number"),
       *             @OA\Property(property="type", type="string"),
       *             @OA\Property(property="status", type="string")
       *         )
       *     )
       * )
       */
    public function store(StoreCompteRequest $request)
    {
        $compte = $this->compteService->createCompte($request->validated());
        return $this->successResponse(new CompteResource($compte), 'Compte created successfully', 201);
    }

    /**
       * Update an account
       *
       * @OA\Put(
       *     path="/comptes/{numero}",
       *     summary="Update an account",
       *     @OA\Parameter(
       *         name="numero",
       *         in="path",
       *         required=true,
       *         @OA\Schema(type="string")
       *     ),
       *     @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
       *             @OA\Property(property="solde", type="number"),
       *             @OA\Property(property="type", type="string"),
       *             @OA\Property(property="status", type="string")
       *         )
       *     ),
       *     @OA\Response(
       *         response=200,
       *         description="Account updated",
       *         @OA\JsonContent(
       *             @OA\Property(property="id", type="integer"),
       *             @OA\Property(property="numero", type="string"),
       *             @OA\Property(property="solde", type="number"),
       *             @OA\Property(property="type", type="string"),
       *             @OA\Property(property="status", type="string")
       *         )
       *     )
       * )
       */
    public function update(UpdateCompteRequest $request, $numero)
    {
        $compte = $this->compteService->updateCompte($numero, $request->validated());
        return $this->successResponse(new CompteResource($compte), 'Compte updated successfully');
    }

    /**
       * Delete an account
       *
       * @OA\Delete(
       *     path="/comptes/{numero}",
       *     summary="Delete an account",
       *     @OA\Parameter(
       *         name="numero",
       *         in="path",
       *         required=true,
       *         @OA\Schema(type="string")
       *     ),
       *     @OA\Response(
       *         response=200,
       *         description="Account deleted"
       *     )
       * )
       */
    public function destroy($numero)
    {
        $this->compteService->deleteCompte($numero);
        return $this->successResponse(null, 'Compte deleted successfully');
    }
}