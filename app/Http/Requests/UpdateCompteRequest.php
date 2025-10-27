<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\TelephoneSenegalRule;
use App\Rules\NciRule;
use App\Models\Compte;

class UpdateCompteRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $compte = Compte::find($this->route('compteId'));
        $clientId = $compte ? $compte->client_id : null;

        return [
            'titulaire' => 'sometimes|string|max:255',
            'informationsClient' => 'sometimes|array',
            'informationsClient.telephone' => [
                'sometimes',
                'string',
                new TelephoneSenegalRule(),
                Rule::unique('clients', 'telephone')->ignore($clientId)
            ],
            'informationsClient.email' => [
                'sometimes',
                'email',
                Rule::unique('clients', 'email')->ignore($clientId)
            ],
            'informationsClient.password' => 'sometimes|string|min:8',
            'informationsClient.nci' => [
                'sometimes',
                'string',
                'regex:/^\d{13}$/',
                Rule::unique('clients', 'nci')->ignore($clientId)
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titulaire.string' => 'Le titulaire doit être une chaîne de caractères.',
            'titulaire.max' => 'Le titulaire ne peut pas dépasser 255 caractères.',
            'informationsClient.array' => 'Les informations client doivent être un tableau.',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'informationsClient.email.email' => 'L\'email doit être valide.',
            'informationsClient.email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'informationsClient.password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'informationsClient.nci.regex' => 'Le NCI doit être composé de 13 chiffres.',
            'informationsClient.nci.unique' => 'Ce NCI est déjà utilisé.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Check if at least one field is provided
            $hasTitulaire = isset($data['titulaire']) && !empty($data['titulaire']);
            $hasClientInfo = isset($data['informationsClient']) && is_array($data['informationsClient']) &&
                             (isset($data['informationsClient']['telephone']) && !empty($data['informationsClient']['telephone']) ||
                              isset($data['informationsClient']['email']) && !empty($data['informationsClient']['email']) ||
                              isset($data['informationsClient']['password']) && !empty($data['informationsClient']['password']) ||
                              isset($data['informationsClient']['nci']) && !empty($data['informationsClient']['nci']));

            if (!$hasTitulaire && !$hasClientInfo) {
                $validator->errors()->add('general', 'Vous devez renseigner au moins un champ pour effectuer une modification.');
            }
        });
    }
}