<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlocageCompteRequest;
use App\Http\Requests\DeblocageCompteRequest;
use App\Models\Compte;
use App\Services\CompteBlockageService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BlocageCompteController extends Controller
{
    public function __construct(
        private readonly CompteBlockageService $blockageService
    ) {}

    /**
     * Planifier le blocage d'un compte épargne
     */
    public function bloquer(BlocageCompteRequest $request, string $compteId): JsonResponse
    {
        $compte = Compte::findOrFail($compteId);

        try {
            if (!$this->blockageService->peutEtreProgrammePourBlocage($compte)) {
                return response()->json([
                    'success' => false,
                    'message' => $compte->type === 'cheque' 
                        ? 'Les comptes chèques ne peuvent pas être bloqués'
                        : 'Ce compte ne peut pas être programmé pour blocage'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->blockageService->planifierBlocage($compte, $request->motif, $request->duree, $request->unite, $request->date_debut);

                return response()->json([
                    'success' => true,
                    'message' => 'Blocage du compte programmé. Une fois bloqué, le compte sera automatiquement archivé dans la base Neon et sera restauré automatiquement à la date de déblocage prévue.',
                    'data' => [
                        'id' => $compte->id,
                        'statut' => $compte->statut,
                        'motifBlocage' => $compte->motif_blocage,
                        'dateBlocagePrevue' => $compte->date_blocage,
                        'dateDeblocagePrevue' => $compte->date_deblocage_prevue,
                        'note' => 'Le compte sera bloqué à la date prévue et archivé dans Neon. Il sera automatiquement restauré et débloqué à la date de déblocage.'
                    ]
                ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Débloquer un compte
     */
    public function debloquer(DeblocageCompteRequest $request, string $compteId): JsonResponse
    {
        $compte = Compte::findOrFail($compteId);

        if (!$this->blockageService->peutEtreDebloque($compte)) {
            return response()->json([
                'success' => false,
                'message' => "Ce compte n'est pas bloqué"
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->blockageService->debloquerCompte($compte);

        return response()->json([
            'success' => true,
            'message' => 'Compte débloqué avec succès',
            'data' => [
                'id' => $compte->id,
                'statut' => $compte->statut,
                'dateDeblocage' => Carbon::now(),
            ]
        ], Response::HTTP_OK);
    }
}
