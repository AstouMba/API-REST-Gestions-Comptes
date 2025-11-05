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
            'id' => (string) $this->id,
            'numeroCompte' => $this->numero,
            'titulaire' => $this->client->titulaire,
            'type' => $this->type,
            'solde' => $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->created_at->toISOString(),
            'statut' => $this->statut,
            'motifBlocage' => $this->motifBlocage,
            'dateBlocage' => $this->type === 'epargne' ? ($this->date_blocage ? $this->date_blocage->toISOString() : null) : null,
            'dateDeblocagePrevue' => $this->type === 'epargne' ? ($this->date_deblocage_prevue ? $this->date_deblocage_prevue->toISOString() : null) : null,
            'metadata' => [
                'derniereModification' => $this->updated_at->toISOString(),
                'version' => 1,
            ],
        ];
    }
}