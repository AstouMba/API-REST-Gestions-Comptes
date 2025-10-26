<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use App\Rules\NciRule;
use App\Rules\TelephoneSenegalRule;
use App\Enums\MessageEnumFr;
use App\Services\CustomValidationRules;

class StoreCompteRequest extends FormRequest
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

    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            CustomValidationRules::required($validator, 'type', $data['type'], MessageEnumFr::REQUIRED);
            CustomValidationRules::in($validator, 'type', $data['type'], ['cheque', 'epargne', 'courant'], MessageEnumFr::IN);
            CustomValidationRules::required($validator, 'soldeInitial', $data['soldeInitial'], MessageEnumFr::REQUIRED);
            CustomValidationRules::numeric($validator, 'soldeInitial', $data['soldeInitial'], MessageEnumFr::NUMERIC);
            CustomValidationRules::min($validator, 'soldeInitial', $data['soldeInitial'], 10000, MessageEnumFr::MIN);
            CustomValidationRules::required($validator, 'devise', $data['devise'], MessageEnumFr::REQUIRED);

            if (is_array($data['client'])) {
                CustomValidationRules::required($validator, 'client.titulaire', $data['client']['titulaire'], MessageEnumFr::REQUIRED);
                CustomValidationRules::max($validator, 'client.titulaire', strlen($data['client']['titulaire']), 255, MessageEnumFr::MAX);

                if (!empty($data['client']['id'])) {
                    CustomValidationRules::uuid($validator, 'client.id', $data['client']['id'], MessageEnumFr::UUID);
                    CustomValidationRules::exists($validator, 'client.id', $data['client']['id'], 'clients', 'id', MessageEnumFr::EXISTS);
                }

                CustomValidationRules::required($validator, 'client.nci', $data['client']['nci'], MessageEnumFr::REQUIRED);
                self::$rules['isCNI']($validator, 'client.nci', $data['client']['nci']);
                CustomValidationRules::unique($validator, 'client.nci', $data['client']['nci'], 'clients', 'nci', MessageEnumFr::UNIQUE);

                CustomValidationRules::required($validator, 'client.email', $data['client']['email'], MessageEnumFr::REQUIRED);
                CustomValidationRules::isMail($validator, 'client.email', $data['client']['email'], MessageEnumFr::ISEMAIL);
                CustomValidationRules::unique($validator, 'client.email', $data['client']['email'], 'clients', 'email', MessageEnumFr::UNIQUE);

                CustomValidationRules::required($validator, 'client.telephone', $data['client']['telephone'], MessageEnumFr::REQUIRED);
                self::$rules['isSenegalPhone']($validator, 'client.telephone', $data['client']['telephone']);
                CustomValidationRules::unique($validator, 'client.telephone', $data['client']['telephone'], 'clients', 'telephone', MessageEnumFr::UNIQUE);

                CustomValidationRules::required($validator, 'client.adresse', $data['client']['adresse'], MessageEnumFr::REQUIRED);
                CustomValidationRules::max($validator, 'client.adresse', strlen($data['client']['adresse']), 255, MessageEnumFr::MAX);
            }
        });
    }
}
