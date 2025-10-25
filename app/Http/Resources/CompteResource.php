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
        // Calculate balance from transactions
        $depots = $this->transactions->where('type', 'depot')->sum('montant');
        $retraits = $this->transactions->where('type', 'retrait')->sum('montant');
        $virements = $this->transactions->where('type', 'virement')->sum('montant');
        $solde = $depots - $retraits - $virements;

        return [
            'id' => $this->id,
            'numeroCompte' => $this->numero,
            'titulaire' => $this->titulaire,
            'type' => $this->type,
            'solde' => $solde,
            'devise' => $this->devise,
            'dateCreation' => $this->created_at,
            'statut' => $this->statut,
            'motifBlocage' => $this->motifBlocage,
            'metadata' => $this->metadata,
            'links' => [
                'self' => url('/api/v1/mbow.astou/comptes/' . $this->numero),
            ],
        ];
    }
}