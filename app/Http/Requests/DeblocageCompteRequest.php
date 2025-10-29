<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeblocageCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow deblocage requests — authentication/authorization is handled elsewhere
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // No body expected for debloquer, keep empty rules but allow the request
        return [
            // if in future we accept a reason, add rules here
        ];
    }
}
