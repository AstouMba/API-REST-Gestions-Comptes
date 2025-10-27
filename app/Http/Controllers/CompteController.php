<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompteResource;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\StoreCompteRequest;
use App\Models\Compte;
use App\Models\User;
use App\Exceptions\CompteNotFoundException;
use Illuminate\Http\Request;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API Gestion Comptes",
 *     version="1.0.0",
 *     description="API for managing bank accounts"
 * )
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1/mbow.astou",
 *     description="Local server"
 * )
 * @OA\Server(
 *     url="https://api-rest-gestions-comptes.onrender.com/mbow.astou/api/documentation",
 *     description="Production server"
 * )
 * @OA\Schema(
 *     schema="Compte",
 *     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="numeroCompte", type="string", example="C00123456"),
 *     @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
 *     @OA\Property(property="type", type="string", example="epargne"),
 *     @OA\Property(property="solde", type="number", example=1250000),
 *     @OA\Property(property="devise", type="string", example="FCFA"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
 *     @OA\Property(property="statut", type="string", example="bloque"),
 *     @OA\Property(property="motifBlocage", type="string", example="Inactivité de 30+ jours"),
 *     @OA\Property(property="metadata", ref="#/components/schemas/Metadata")
 * )
 * @OA\Schema(
 *     schema="Pagination",
 *     @OA\Property(property="currentPage", type="integer", example=1),
 *     @OA\Property(property="totalPages", type="integer", example=3),
 *     @OA\Property(property="totalItems", type="integer", example=25),
 *     @OA\Property(property="itemsPerPage", type="integer", example=10),
 *     @OA\Property(property="hasNext", type="boolean", example=true),
 *     @OA\Property(property="hasPrevious", type="boolean", example=false)
 * )
 * @OA\Schema(
 *     schema="Links",
 *     @OA\Property(property="self", type="string", example="/api/v1/comptes?page=1&limit=10"),
 *     @OA\Property(property="next", type="string", example="/api/v1/comptes?page=2&limit=10"),
 *     @OA\Property(property="first", type="string", example="/api/v1/comptes?page=1&limit=10"),
 *     @OA\Property(property="last", type="string", example="/api/v1/comptes?page=3&limit=10")
 * )
 * @OA\Schema(
 *     schema="Metadata",
 *     @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
 *     @OA\Property(property="version", type="integer", example=1)
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
        * Lister tous les comptes non archivés
        *
        * @OA\Get(
        *     path="/comptes",
        *     summary="Lister tous les comptes",
        *     description="Admin peut récupérer la liste de tous les comptes actifs. Client peut récupérer la liste de ses comptes actifs. Liste des comptes non supprimés de type cheque ou epargne actif.",
        *     @OA\Parameter(
        *         name="page",
        *         in="query",
        *         description="Numéro de page",
        *         required=false,
        *         @OA\Schema(type="integer", default=1)
        *     ),
        *     @OA\Parameter(
        *         name="limit",
        *         in="query",
        *         description="Nombre d'éléments par page",
        *         required=false,
        *         @OA\Schema(type="integer", default=10, maximum=100)
        *     ),
        *     @OA\Parameter(
        *         name="search",
        *         in="query",
        *         description="Recherche par titulaire ou numéro",
        *         required=false,
        *         @OA\Schema(type="string")
        *     ),
        *     @OA\Parameter(
        *         name="sort",
        *         in="query",
        *         description="Tri",
        *         required=false,
        *         @OA\Schema(type="string", enum={"created_at", "solde", "titulaire"}, default="created_at")
        *     ),
        *     @OA\Parameter(
        *         name="order",
        *         in="query",
        *         description="Ordre",
        *         required=false,
        *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
        *     ),
        *     @OA\Response(
        *         response=200,
        *         description="Liste des comptes",
        *         @OA\JsonContent(
        *             @OA\Property(property="success", type="boolean", example=true),
        *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
        *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination"),
        *             @OA\Property(property="links", ref="#/components/schemas/Links")
        *         )
        *     ),
        *     security={}
        * )
        * @return \Illuminate\Http\JsonResponse
        */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'sort', 'order', 'limit', 'page']);
        $comptes = $this->compteService->listComptes($request->user(), $filters);

        $pagination = [
            'currentPage' => $comptes->currentPage(),
            'totalPages' => $comptes->lastPage(),
            'totalItems' => $comptes->total(),
            'itemsPerPage' => $comptes->perPage(),
            'hasNext' => $comptes->hasMorePages(),
            'hasPrevious' => $comptes->currentPage() > 1,
        ];

        $links = [
            'self' => $request->fullUrl(),
            'first' => $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => 1])),
            'last' => $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => $comptes->lastPage()])),
        ];
        if ($comptes->hasMorePages()) {
            $links['next'] = $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => $comptes->currentPage() + 1]));
        }
        if ($comptes->currentPage() > 1) {
            $links['previous'] = $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => $comptes->currentPage() - 1]));
        }

        return $this->successResponse(CompteResource::collection($comptes), 'Comptes retrieved successfully', 200, $pagination, $links);
    }


    /**
     * Créer un nouveau compte
     *
     * @OA\Post(
     *     path="/comptes",
     *     summary="Créer un compte",
     *     description="Créer un nouveau compte bancaire",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="cheque"),
     *             @OA\Property(property="soldeInitial", type="number", example=500000),
     *             @OA\Property(property="devise", type="string", example="FCFA"),
     *             @OA\Property(property="client", type="object",
     *                 @OA\Property(property="id", type="string", example=null),
     *                 @OA\Property(property="titulaire", type="string", example="Hawa BB Wane"),
     *                 @OA\Property(property="nci", type="string", example="1234567890123"),
     *                 @OA\Property(property="email", type="string", example="cheikh.sy@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
     *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *                 @OA\Property(property="details", type="object")
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCompteRequest $request)
    {
        $result = $this->compteService->createCompte($request->all());

        return $this->successResponse($result, 'Compte creation request submitted successfully', 202);
    }

    /**
     * Récupérer un compte par ID
     *
     * @OA\Get(
     *     path="/comptes/{compteId}",
     *     summary="Récupération d’un compte via ID",
     *     description="Admin peut récupérer n’importe quel compte via son ID. Client ne peut récupérer qu’un de ses propres comptes via son ID. Recherche d'abord en local, puis sur base serverless si introuvable.",
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID de l'utilisateur",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Compte")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètre manquant",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
     *                 @OA\Property(property="message", type="string", example="Le paramètre user_id est requis")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte ou utilisateur non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *                 @OA\Property(property="details", type="object",
     *                     @OA\Property(property="compteId", type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             )
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $compteId)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            throw new \App\Exceptions\ValidationException('Le paramètre user_id est requis', 400, null, 'VALIDATION_ERROR');
        }
        $user = User::find($userId);
        if (!$user) {
            throw new \App\Exceptions\NotFoundException('Utilisateur non trouvé', 404, null, 'USER_NOT_FOUND');
        }

        try {
            $compte = $this->compteService->getCompteById($user, $compteId);
            return $this->successResponse(new CompteResource($compte), 'Compte récupéré avec succès', 200);
        } catch (CompteNotFoundException $e) {
            throw $e;
        }
    }

}