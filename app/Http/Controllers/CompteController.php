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
use App\Jobs\UnarchiveAccountsJob;

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

    /**
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $this->getFilters($request);

        // Le service gérera automatiquement les permissions Admin/Client
        $comptes = $this->compteService->listComptes($user, $filters);

        $formatted = $this->formatPagination($request, $comptes);

        return $this->successResponse(
            CompteResource::collection($comptes),
            MessageEnumFr::COMPTES_RETRIEVED,
            Response::HTTP_OK,
            $formatted['pagination'],
            $formatted['links']
        );
    }

    /**
     * Affiche un compte spécifique
     * - Admin : peut voir n'importe quel compte
     * - Client : peut voir uniquement ses propres comptes
     * 
     * @param Request $request
     * @param string $compteId
     * @return JsonResponse
     */
    public function show(Request $request, string $compteId): JsonResponse
    {
        $user = $request->user();
        
        $payload = $this->compteService->getComptePayload($compteId, $user);

        if ($payload === null) {
            // Vérifier si le compte existe mais que l'utilisateur n'a pas accès
            $compte = Compte::withoutGlobalScopes()->find($compteId);

            if ($compte && !$user->is_admin) {
                $client = $user->client;
                if (!$client || $compte->client_id !== $client->id) {
                    return $this->errorResponse(MessageEnumFr::ACCESS_DENIED, Response::HTTP_FORBIDDEN);
                }
            }

            return $this->errorResponse(MessageEnumFr::COMPTE_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse($payload, MessageEnumFr::COMPTE_RETRIEVED, Response::HTTP_OK);
    }

    /**
     * Liste uniquement les comptes du client connecté
     * Route dédiée pour les clients : /mes-comptes
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function mesComptes(Request $request): JsonResponse
    {
        $user = $request->user();
        $client = $user->client;

        if (!$client) {
            return $this->errorResponse(MessageEnumFr::CLIENT_NOT_FOUND_USER, Response::HTTP_NOT_FOUND);
        }

        $filters = $this->getFilters($request);
        
        // Forcer le filtre sur le client connecté
        $filters['client_id'] = $client->id;

        $comptes = $this->compteService->listComptes($user, $filters);
        $formatted = $this->formatPagination($request, $comptes);

        return $this->successResponse(
            CompteResource::collection($comptes),
            MessageEnumFr::VOS_COMPTES_RETRIEVED,
            Response::HTTP_OK,
            $formatted['pagination'],
            $formatted['links']
        );
    }

    /**
     * Créer un nouveau compte (ADMIN uniquement - protégé par middleware)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $result = $this->compteService->createCompte($request->all());
            
            return $this->successResponse(
                new CompteResource($result), 
                MessageEnumFr::COMPTE_CREATED, 
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Mettre à jour un compte (ADMIN uniquement - protégé par middleware)
     * 
     * @param UpdateCompteRequest $request
     * @param string $compteId
     * @return JsonResponse
     */
    public function update(UpdateCompteRequest $request, string $compteId): JsonResponse
    {
        try {
            $result = $this->compteService->updateCompte($compteId, $request->validated(), $request->user());
            
            return $this->successResponse(
                new CompteResource($result), 
                MessageEnumFr::COMPTE_UPDATED, 
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Compte non trouvé') {
                return $this->errorResponse(MessageEnumFr::COMPTE_NOT_FOUND, Response::HTTP_NOT_FOUND);
            }
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Supprimer un compte (ADMIN uniquement - protégé par middleware)
     * Soft delete le compte localement et l'archive sur Neon.
     * 
     * @param Request $request
     * @param string $compteId
     * @return JsonResponse
     */
    public function destroy(Request $request, string $compteId): JsonResponse
    {
        try {
            $result = $this->compteService->deleteCompte($compteId, $request);
            
            return $this->successResponse(
                $result, 
                MessageEnumFr::COMPTE_DELETED_ARCHIVED, 
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Compte non trouvé') {
                return $this->errorResponse(MessageEnumFr::COMPTE_NOT_FOUND, Response::HTTP_NOT_FOUND);
            }
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Planifier le blocage d'un compte épargne (ADMIN uniquement - protégé par middleware)
     * 
     * @param BlocageCompteRequest $request
     * @param string $compteId
     * @return JsonResponse
     */
    public function bloquer(BlocageCompteRequest $request, string $compteId): JsonResponse
    {
        try {
            $result = $this->compteService->planifierBlocage($compteId, $request->validated());
            
            return $this->successResponse(
                $result,
                MessageEnumFr::BLOQUAGE_PROGRAMME,
                Response::HTTP_CREATED
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * - Admin : statistiques globales
     * - Client : statistiques de ses comptes uniquement
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statistiques(Request $request): JsonResponse
    {
        $user = $request->user();
        
        try {
            $query = Compte::query();
            
            // Filtrer par client si ce n'est pas un admin
            if (!$user->is_admin) {
                $client = $user->client;
                if (!$client) {
                    return $this->errorResponse(MessageEnumFr::CLIENT_NOT_FOUND, Response::HTTP_NOT_FOUND);
                }
                $query->where('client_id', $client->id);
            }

            $stats = [
                'total_comptes' => $query->count(),
                'comptes_actifs' => (clone $query)->where('statut', 'actif')->count(),
                'comptes_bloques' => (clone $query)->where('statut', 'bloque')->count(),
                'comptes_fermes' => (clone $query)->where('statut', 'ferme')->count(),
                'solde_total' => (clone $query)->sum('solde'),
                'par_type' => [
                    'cheque' => (clone $query)->where('type', 'cheque')->count(),
                    'epargne' => (clone $query)->where('type', 'epargne')->count(),
                    'courant' => (clone $query)->where('type', 'courant')->count(),
                ],
            ];

            return $this->successResponse($stats, MessageEnumFr::STATISTIQUES_RETRIEVED, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * - Admin : peut voir l'historique de n'importe quel compte
     * - Client : peut voir l'historique de ses propres comptes uniquement
     * 
     * @param Request $request
     * @param string $compteId
     * @return JsonResponse
     */
    public function historique(Request $request, string $compteId): JsonResponse
    {
        $user = $request->user();
        
        try {
            $compte = Compte::with(['depots', 'retraits'])->find($compteId);
            
            if (!$compte) {
                return $this->errorResponse(MessageEnumFr::COMPTE_NOT_FOUND, Response::HTTP_NOT_FOUND);
            }

            // Vérifier les permissions
            if (!$user->is_admin) {
                $client = $user->client;
                if (!$client || $compte->client_id !== $client->id) {
                    return $this->errorResponse(MessageEnumFr::ACCESS_DENIED, Response::HTTP_FORBIDDEN);
                }
            }

            // Récupérer toutes les transactions (dépôts + retraits)
            $transactions = collect($compte->depots)
                ->merge($compte->retraits)
                ->sortByDesc('created_at')
                ->values();

            return $this->successResponse([
                'compte_id' => $compte->id,
                'numero_compte' => $compte->numero,
                'transactions' => $transactions,
                'total_transactions' => $transactions->count(),
            ], MessageEnumFr::HISTORIQUE_RETRIEVED, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}