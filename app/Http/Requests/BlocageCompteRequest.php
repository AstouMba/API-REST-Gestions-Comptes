<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlocageCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow the request in this application; authorization (policies) are handled elsewhere if needed
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'max:1000'],
            'duree' => ['required', 'integer', 'min:1'],
            // unité de temps: jours, mois ou annees
            'unite' => ['required', 'string', 'in:jours,mois,annees'],
            // date_debut is the planned date for blocking — must be provided and not in the past
            'date_debut' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
