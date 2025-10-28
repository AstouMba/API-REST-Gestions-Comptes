<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompteResource;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use App\Traits\PaginationTrait;
use App\Enums\MessageEnumFr;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateCompteRequest;

class CompteController extends Controller
{
    use ApiResponseTrait;
    use PaginationTrait;

    protected $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }

    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        // Appel du service sans utilisateur
        $comptes = $this->compteService->listComptes(null, $filters);

        $formatted = $this->formatPagination($request, $comptes);

        return $this->successResponse(
            CompteResource::collection($comptes),
            MessageEnumFr::COMPTES_RETRIEVED,
            200,
            $formatted['pagination'],
            $formatted['links']
        );
    }

    public function show(Request $request, $compteId)
    {
        try {
            $compte = $this->compteService->getCompteById(null, $compteId);
            return $this->successResponse(new CompteResource($compte), MessageEnumFr::COMPTE_RETRIEVED, 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function store(Request $request)
    {
        $result = $this->compteService->createCompte($request->all());
        return $this->successResponse($result, MessageEnumFr::COMPTE_CREATED, 202);
    }

    public function update(UpdateCompteRequest $request, $compteId)
    {
        try {
            $result = $this->compteService->updateCompte($request->user(), $compteId, $request->validated());
            return $this->successResponse(new CompteResource($result), 'Compte mis à jour avec succès', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
