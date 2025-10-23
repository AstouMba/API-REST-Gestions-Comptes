<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'login' => 'nullable|string|max:255|unique:users,login',
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string|max:20',
        ];
    }
}
