<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'nom' => $this->nom,
            'email' => $this->email,
            'adresse' => $this->adresse,
            'telephone' => $this->telephone,
            'dateCreation' => $this->created_at,
            'links' => [
                'self' => route('clients.show', $this->id),
                'comptes' => route('clients.comptes', $this->id),
            ],
        ];
    }
}