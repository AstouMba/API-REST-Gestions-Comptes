<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'montant' => $this->montant,
            'type' => $this->type,
            'description' => $this->description,
            'dateCreation' => $this->created_at,
            'links' => [
                'self' => route('transactions.show', $this->id),
                'compte' => route('comptes.show', $this->compte->numero),
            ],
        ];
    }
}