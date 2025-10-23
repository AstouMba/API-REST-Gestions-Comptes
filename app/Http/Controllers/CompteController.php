<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompteResource;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\StoreCompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use Illuminate\Http\Request;

use OpenApi\Annotations as OA;

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
      * @return \Illuminate\Http\JsonResponse
      */
    public function index(Request $request)
    {
        $comptes = $this->compteService->getAllComptes();
        return $this->successResponse(CompteResource::collection($comptes), 'Comptes retrieved successfully');
    }

    public function show($numero)
    {
        $compte = $this->compteService->getCompteByNumero($numero);
        return $this->successResponse(new CompteResource($compte), 'Compte retrieved successfully');
    }

    public function store(StoreCompteRequest $request)
    {
        $compte = $this->compteService->createCompte($request->validated());
        return $this->successResponse(new CompteResource($compte), 'Compte created successfully', 201);
    }

    public function update(UpdateCompteRequest $request, $numero)
    {
        $compte = $this->compteService->updateCompte($numero, $request->validated());
        return $this->successResponse(new CompteResource($compte), 'Compte updated successfully');
    }

    public function destroy($numero)
    {
        $this->compteService->deleteCompte($numero);
        return $this->successResponse(null, 'Compte deleted successfully');
    }
}