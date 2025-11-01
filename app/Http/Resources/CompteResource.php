<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Forcer l'ID en string pour éviter de retourner un objet UUID
            'id' => (string) $this->id,
            'numeroCompte' => $this->numero,
            'titulaire' => $this->client->titulaire,
            'type' => $this->type,
            // Use the model accessor 'solde' (computed, not stored in DB)
            'solde' => $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->created_at->toISOString(),
            'statut' => $this->statut,
            'motifBlocage' => $this->motifBlocage,
            // Afficher les dates de blocage uniquement pour les comptes épargne
            'dateBlocage' => $this->type === 'epargne' ? ($this->date_blocage ? $this->date_blocage->toISOString() : null) : null,
            'dateDeblocagePrevue' => $this->type === 'epargne' ? ($this->date_deblocage_prevue ? $this->date_deblocage_prevue->toISOString() : null) : null,
            'metadata' => [
                'derniereModification' => $this->updated_at->toISOString(),
                'version' => 1,
            ],
        ];
    }
}