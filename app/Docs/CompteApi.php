<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/comptes",
 *     summary="Lister tous les comptes",
 *     description="Admin peut récupérer tous les comptes actifs. Client peut récupérer ses comptes actifs.",
 *     tags={"Comptes"},
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
 *         @OA\Schema(type="string", default="created_at", enum={"created_at", "solde", "titulaire"})
 *     ),
 *     @OA\Parameter(
 *         name="order",
 *         in="query",
 *         description="Ordre",
 *         required=false,
 *         @OA\Schema(type="string", default="desc", enum={"asc","desc"})
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
 *     security={{"bearerAuth":{}}}
 * )
 *
 * @OA\Get(
 *     path="/comptes/{compteId}",
 *     summary="Récupérer un compte spécifique",
 *     description="Récupère les détails d'un compte bancaire par son ID",
 *     tags={"Comptes"},
 *     @OA\Parameter(
 *         name="compteId",
 *         in="path",
 *         description="ID du compte à récupérer",
 *         required=true,
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Détails du compte",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Compte"),
 *             @OA\Property(property="message", type="string", example="Compte récupéré avec succès")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Compte non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Compte introuvable")
 *         )
 *     ),
 *     security={{"bearerAuth":{}}}
 * )
 *
 * @OA\Post(
 *     path="/comptes",
 *     summary="Créer un compte",
 *     description="Créer un nouveau compte bancaire. Tous les champs sont obligatoires.",
 *     tags={"Comptes"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"type", "soldeInitial", "devise"},
 *             @OA\Property(
 *                 property="type",
 *                 type="string",
 *                 enum={"cheque", "epargne"},
 *                 example="cheque",
 *                 description="Type de compte: cheque ou epargne uniquement"
 *             ),
 *             @OA\Property(
 *                 property="soldeInitial",
 *                 type="number",
 *                 minimum=10000,
 *                 example=500000,
 *                 description="Solde initial du compte. Doit être supérieur ou égal à 10000"
 *             ),
 *             @OA\Property(
 *                 property="devise",
 *                 type="string",
 *                 example="FCFA",
 *                 description="Devise du compte"
 *             ),
 *             @OA\Property(
 *                 property="client",
 *                 type="object",
 *                 required={"titulaire", "email", "telephone", "nci", "adresse"},
 *                 description="Informations du client. Obligatoire si client_id n'est pas fourni",
 *                 @OA\Property(
 *                     property="titulaire", 
 *                     type="string",
 *                     maxLength=255,
 *                     example="Hawa BB Wane",
 *                     description="Nom complet du titulaire"
 *                 ),
 *                 @OA\Property(
 *                     property="email",
 *                     type="string",
 *                     format="email",
 *                     example="cheikh.sy@example.com",
 *                     description="Email unique du client"
 *                 ),
 *                 @OA\Property(
 *                     property="telephone",
 *                     type="string",
 *                     pattern="^\+221(70|75|76|77|78)\d{7}$",
 *                     example="+221771234567",
 *                     description="Numéro de téléphone sénégalais unique. Format: +221 suivi de 9 chiffres. Préfixes autorisés: 70,75,76,77,78"
 *                 ),
 *                 @OA\Property(
 *                     property="nci",
 *                     type="string",
 *                     pattern="^\d{13}$",
 *                     example="1234567890123",
 *                     description="Numéro CNI unique. Exactement 13 chiffres, pas de séquence répétitive"
 *                 ),
 *                 @OA\Property(
 *                     property="adresse",
 *                     type="string",
 *                     maxLength=255,
 *                     example="Dakar, Sénégal",
 *                     description="Adresse du client"
 *                 ),
 *                 @OA\Property(
 *                     property="id",
 *                     type="string",
 *                     format="uuid",
 *                     nullable=true,
 *                     description="UUID du client si mise à jour d'un client existant"
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="client_id",
 *                 type="string",
 *                 format="uuid",
 *                 description="UUID d'un client existant (alternative à l'objet client)",
 *                 nullable=true
 *             )
 *         )
 *     ),
 *     @OA\Response(
         response=201,
 *         description="Compte créé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="string", format="uuid", example="660f9511-f30c-52e5-b827-557766551111"),
 *                 @OA\Property(property="numeroCompte", type="string", example="C00123460"),
 *                 @OA\Property(property="titulaire", type="string", example="Hawa BB Wane"),
 *                 @OA\Property(property="type", type="string", example="cheque"),
 *                 @OA\Property(property="solde", type="number", example=500000),
 *                 @OA\Property(property="devise", type="string", example="FCFA"),
 *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-10-19T10:30:00Z"),
 *                 @OA\Property(property="statut", type="string", example="actif"),
 *                 @OA\Property(
 *                     property="metadata",
 *                     type="object",
 *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-10-19T10:30:00Z"),
 *                     @OA\Property(property="version", type="integer", example=1)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(
 *                 property="error",
 *                 type="object",
 *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
 *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
 *                 @OA\Property(
 *                     property="details",
 *                     type="object",
 *                     example={
 *                         "client.titulaire": "Champ obligatoire",
 *                         "client.telephone": "Numéro de téléphone invalide",
 *                         "client.nci": "Numéro de CNI invalide",
 *                         "soldeInitial": "Valeur trop petite"
 *                     }
 *                 )
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
 * @OA\Patch(
 *     path="/comptes/{compteId}",
 *     summary="Mettre à jour partiellement un compte et ses informations client",
 *     description="Met à jour partiellement le titulaire et/ou les informations du client associées au compte.",
 *     tags={"Comptes"},
 *     @OA\Parameter(
 *         name="compteId",
 *         in="path",
 *         description="ID du compte",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="titulaire", type="string", example="Amadou Diallo Junior"),
 *             @OA\Property(property="informationsClient", type="object",
 *                 @OA\Property(property="telephone", type="string", example="+221771234568"),
 *                 @OA\Property(property="email", type="string", example="amadou.jr@example.com"),
 *                 @OA\Property(property="password", type="string", example="newsecret123"),
 *                 @OA\Property(property="nci", type="string", example="1234567890123")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Compte mis à jour avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
 *             @OA\Property(property="data", ref="#/components/schemas/Compte")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Données invalides",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="error", type="object")
 *         )
 *     ),
 *     security={{"bearerAuth":{}}}
 * )

 * @OA\Delete(
 *     path="/comptes/{compteId}",
 *     summary="Supprimer un compte (Soft Delete) ",
 *     description="Supprime (soft-delete) un compte localement et archive ses données dans la base Neon.",
 *     tags={"Comptes"},
 *     @OA\Parameter(
 *         name="compteId",
 *         in="path",
 *         description="ID du compte à supprimer",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Compte supprimé et archivé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Compte supprimé et archivé avec succès"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
 *                 @OA\Property(property="numero", type="string", example="C00123456"),
 *                 @OA\Property(property="statut", type="string", example="ferme"),
 *                 @OA\Property(property="dateFermeture", type="string", format="date-time", example="2025-10-19T11:15:00Z"),
 *                 @OA\Property(property="archive", type="boolean", example=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Compte introuvable",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Compte introuvable")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur lors de la suppression du compte",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Erreur lors de la suppression du compte")
 *         )
 *     ),
 *     security={{"bearerAuth":{}}}
 * )
 * 
 * @OA\Post(
 *     path="/comptes/{compteId}/bloquer",
 *     summary="Planifier le blocage d'un compte épargne",
 *     description="Planifie le blocage futur d'un compte épargne avec une date de déblocage automatique",
 *     tags={"Comptes - Blocage"},
 *     @OA\Parameter(
 *         name="compteId",
 *         in="path",
 *         required=true,
 *         description="ID du compte à bloquer",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
             required={"motif", "duree", "unite"},
             @OA\Property(property="motif", type="string", example="Maintenance programmée"),
             @OA\Property(property="duree", type="integer", example=3),
             @OA\Property(property="unite", type="string", enum={"jours", "mois", "annees"}, example="mois"),
             @OA\Property(
                 property="date_debut",
                 type="string",
                 format="date-time",
                 example="2025-11-01T08:00:00Z",
                 description="Date de début du blocage (optionnel, par défaut: maintenant)"
             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Blocage programmé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Blocage du compte programmé"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="string"),
 *                 @OA\Property(property="statut", type="string"),
 *                 @OA\Property(property="motifBlocage", type="string"),
 *                 @OA\Property(property="dateBlocagePrevue", type="string", format="date-time"),
 *                 @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time"),
 *                 @OA\Property(property="note", type="string")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erreur de validation ou compte non éligible au blocage",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Seuls les comptes épargne peuvent être bloqués")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Compte non trouvé"
 *     ),
 *     security={{"bearerAuth":{}}}
 * )
 */
class CompteApi {}
