<?php

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\Resources\CompteResource;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\StoreCompteRequest;
use App\Models\User;
use App\Exceptions\CompteNotFoundException;
use App\Traits\PaginationTrait;
use App\Enums\MessageEnumFr;
use Illuminate\Http\Request;


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
        $comptes = $this->compteService->listComptes($request->user(), $filters);

        $formatted = $this->formatPagination($request, $comptes);

        return $this->successResponse(CompteResource::collection($comptes), MessageEnumFr::COMPTES_RETRIEVED, 200, $formatted['pagination'], $formatted['links']);
    }

    public function store(StoreCompteRequest $request)
    {
        $result = $this->compteService->createCompte($request->all());
        return $this->successResponse($result, MessageEnumFr::COMPTE_CREATED, 202);
    }

    public function show(Request $request, $compteId)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            throw new ValidationException(MessageEnumFr::USER_ID_REQUIRED, 400, null);
        }
        $user = User::find($userId);
        if (!$user) {
            throw new NotFoundException(MessageEnumFr::USER_NOT_FOUND, 404, null);
        }

        try {
            $compte = $this->compteService->getCompteById($user, $compteId);
            return $this->successResponse(new CompteResource($compte), MessageEnumFr::COMPTE_RETRIEVED, 200);
        } catch (CompteNotFoundException $e) {
            throw $e;
        }
    }
}
