<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Compte;
use App\Rules\TelephoneSenegalRule;
use App\Rules\NciRule;
use App\Enums\MessageEnumFr;

class UpdateCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorisation désactivée temporairement jusqu'à l'implémentation de l'authentification
        return true;
    }    public function rules(): array
    {
        $compteId = $this->route('compteId');
        $clientId = null;
        if ($compteId) {
            $compte = Compte::with('client')->find($compteId);
            $clientId = $compte && $compte->client ? $compte->client->id : null;
        }

        return [
            'titulaire' => ['sometimes', 'string'],
            'informationsClient' => ['sometimes', 'array'],
            'informationsClient.telephone' => [
                'sometimes',
                'nullable',
                'string',
                new TelephoneSenegalRule(),
                $clientId ? Rule::unique('clients', 'telephone')->ignore($clientId) : 'unique:clients,telephone'
            ],
            'informationsClient.email' => [
                'sometimes',
                'nullable',
                'email',
                $clientId ? Rule::unique('clients', 'email')->ignore($clientId) : 'unique:clients,email'
            ],
            'informationsClient.password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'informationsClient.nci' => [
                'sometimes',
                'nullable',
                'string',
                new NciRule(),
                $clientId ? Rule::unique('clients', 'nci')->ignore($clientId) : 'unique:clients,nci'
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Vérifie si le champ titulaire existe et n'est pas vide
            $hasTitulaire = isset($data['titulaire']) && !empty(trim($data['titulaire']));
            
            // Vérifie les informations client
            $hasInfoField = false;
            if (isset($data['informationsClient']) && is_array($data['informationsClient'])) {
                foreach (['telephone', 'email', 'password', 'nci'] as $field) {
                    if (isset($data['informationsClient'][$field]) && !empty(trim($data['informationsClient'][$field]))) {
                        $hasInfoField = true;
                        break;
                    }
                }
            }

            // Si aucun champ n'est fourni ou tous les champs sont vides
            if (!$hasTitulaire && !$hasInfoField) {
                $msg = 'Vous devez renseigner au moins un champ pour effectuer une modification.';
                $validator->errors()->add('general', $msg);
            }
        });
    }

    public function messages(): array
    {
        return [
            'informationsClient.telephone' => MessageEnumFr::ISSENEGALPHONE,
            'informationsClient.telephone.unique' => MessageEnumFr::UNIQUE,
            'informationsClient.email.email' => MessageEnumFr::ISEMAIL,
            'informationsClient.email.unique' => MessageEnumFr::UNIQUE,
            'informationsClient.password.min' => MessageEnumFr::MINLENGTH,
            'informationsClient.nci' => MessageEnumFr::ISCNI,
        ];
    }
}
