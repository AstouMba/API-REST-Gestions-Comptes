<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="numeroCompte", type="string", example="C00123456"),
 *     @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
 *     @OA\Property(property="type", type="string", example="epargne"),
 *     @OA\Property(property="solde", type="number", example=1250000),
 *     @OA\Property(property="devise", type="string", example="FCFA"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="bloque"),
 *     @OA\Property(property="motifBlocage", type="string", nullable=true, example="Maintenance programmée"),
 *     @OA\Property(property="dateBlocage", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="metadata", ref="#/components/schemas/Metadata")
 * )
 *
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     @OA\Property(property="currentPage", type="integer", example=1),
 *     @OA\Property(property="totalPages", type="integer", example=3),
 *     @OA\Property(property="totalItems", type="integer", example=25),
 *     @OA\Property(property="itemsPerPage", type="integer", example=10),
 *     @OA\Property(property="hasNext", type="boolean", example=true),
 *     @OA\Property(property="hasPrevious", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *     schema="Links",
 *     type="object",
 *     @OA\Property(property="self", type="string", example="/api/v1/comptes?page=1&limit=10"),
 *     @OA\Property(property="next", type="string", example="/api/v1/comptes?page=2&limit=10"),
 *     @OA\Property(property="first", type="string", example="/api/v1/comptes?page=1&limit=10"),
 *     @OA\Property(property="last", type="string", example="/api/v1/comptes?page=3&limit=10")
 * )
 *
 * @OA\Schema(
 *     schema="Metadata",
 *     type="object",
 *     @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
 *     @OA\Property(property="version", type="integer", example=1)
 * )
 */
class Schemas {}
