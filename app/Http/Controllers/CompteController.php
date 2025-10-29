<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompteResource;
use App\Services\CompteService;
use App\Services\CompteBlockageService;
use App\Traits\ApiResponseTrait;
use App\Traits\PaginationTrait;
use App\Enums\MessageEnumFr;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\UpdateCompteRequest;
use App\Http\Requests\BlocageCompteRequest;
use App\Http\Requests\DeblocageCompteRequest;
use Carbon\Carbon;
use App\Models\Compte;
use Symfony\Component\HttpFoundation\Response;


class CompteController extends Controller
{
    use ApiResponseTrait;
    use PaginationTrait;

    protected $compteService;
    protected $blockageService;

    public function __construct(
        CompteService $compteService,
        CompteBlockageService $blockageService
    ) {
        $this->compteService = $compteService;
        $this->blockageService = $blockageService;
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

    /**
     * Planifier le blocage d'un compte épargne
     */
    public function bloquer(BlocageCompteRequest $request, string $compteId): JsonResponse
    {
        try {
            $result = $this->compteService->planifierBlocage($compteId, $request->validated());
            return $this->successResponse($result, 'Blocage du compte programmé', Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Débloquer un compte
     */
    public function debloquer(DeblocageCompteRequest $request, string $compteId): JsonResponse
    {
        try {
            $result = $this->compteService->debloquerCompte($compteId);
            return $this->successResponse($result, 'Compte débloqué avec succès', Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}