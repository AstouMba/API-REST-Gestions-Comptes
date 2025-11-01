<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API Gestion Comptes",
 *     version="1.2.1",
 *     description="API de gestion des comptes bancaires avec authentification JWT et contrôle d'accès basé sur les rôles (Admin/Client)",
 *     @OA\Contact(
 *         email="support@api-comptes.com",
 *         name="Support API"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1/mbow.astou",
 *     description="Serveur local"
 * )
 * @OA\Server(
 *     url="https://api-rest-gestions-comptes.onrender.com/api/v1/mbow.astou",
 *     description="Serveur de production"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Utilisez le token JWT obtenu lors de la connexion"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints d'authentification (login, refresh, logout)"
 * )
 * 
 * @OA\Tag(
 *     name="Comptes",
 *     description="Opérations sur les comptes bancaires (Admin: tous les comptes, Client: ses propres comptes)"
 * )
 * 
 * @OA\Tag(
 *     name="Comptes - Blocage",
 *     description="Opérations de blocage des comptes (Admin uniquement)"
 * )
 * 
 * @OA\Schema(
 *     schema="Error",
 *     @OA\Property(property="message", type="string", example="Message d'erreur"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         example={"field": {"Le champ est invalide"}}
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="ApiResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(property="data", type="object")
 * )
 */
class OpenApiInfo {}
