<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API Gestion Comptes",
 *     version="1.0.0",
 *     description="API for managing bank accounts"
 * )
 *
 * 
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1/mbow.astou",
 *     description="Local server"
 * )
 * @OA\Server(
 *     url="https://api-rest-gestions-comptes.onrender.com/api/v1/mbow.astou",
 *     description="Production server"
 * )
 * 
 *  @OA\Tag(
 *     name="Comptes",
 *     description="Opérations sur les comptes bancaires"
 * )
 * 
 * @OA\Tag(
 *     name="Comptes - Blocage",
 *     description="Opérations de blocage et déblocage des comptes"
 * )
 */
class OpenApiInfo {}
