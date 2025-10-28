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

        // Appel du service en passant l'utilisateur (le scope ForUser gérera l'accès)
        $comptes = $this->compteService->listComptes($request->user(), $filters);

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
        $payload = $this->compteService->getComptePayload($compteId);

        if ($payload === null) {
            return $this->errorResponse(MessageEnumFr::COMPTE_NOT_FOUND, 404);
        }

        return $this->successResponse($payload, MessageEnumFr::COMPTE_RETRIEVED, 200);
    }

    public function store(Request $request)
    {
        $result = $this->compteService->createCompte($request->all());
        return $this->successResponse($result, MessageEnumFr::COMPTE_CREATED, 202);
    }

    public function update(UpdateCompteRequest $request, $compteId)
    {
        try {
            $result = $this->compteService->updateCompte($compteId, $request->validated(), $request->user());
            return $this->successResponse(new CompteResource($result), MessageEnumFr::COMPTE_UPDATED, 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Soft delete the compte locally and archive it to Neon.
     */
    public function destroy(Request $request, $compteId)
    {
        try {
            $result = $this->compteService->deleteCompte($compteId, $request);
            return $this->successResponse($result, MessageEnumFr::COMPTE_DELETED_ARCHIVED, 200);
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Compte non trouvé') {
                return $this->errorResponse(MessageEnumFr::COMPTE_NOT_FOUND, 404);
            }
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}