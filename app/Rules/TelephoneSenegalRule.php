<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelephoneSenegalRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Vérifier que le numéro commence par +221
        if (!str_starts_with($value, '+221')) {
            $fail('Le :attribute doit commencer par +221.');
            return;
        }
        
        // Vérifier la longueur totale (13 caractères)
        if (strlen($value) !== 13) {
            $fail('Le :attribute doit contenir exactement 13 caractères.');
            return;
        }
        
        // Vérifier que les 9 derniers caractères sont des chiffres
        $digits = substr($value, 4);
        if (!is_numeric($digits)) {
            $fail('Le :attribute doit contenir uniquement des chiffres après +221.');
            return;
        }
        
        // Vérifier que ce n'est pas une séquence répétitive
        $isRepetitive = true;
        for ($i = 1; $i < 9; $i++) {
            if ($digits[$i] !== $digits[0]) {
                $isRepetitive = false;
                break;
            }
        }
        if ($isRepetitive) {
            $fail('Le :attribute semble invalide (séquence répétitive).');
            return;
        }
        
        // Liste des préfixes valides pour les opérateurs mobiles au Sénégal
        $validPrefixes = ['70', '75', '76', '77', '78'];
        $prefix = substr($digits, 0, 2);
        
        if (!in_array($prefix, $validPrefixes)) {
            $fail('Le :attribute doit avoir un préfixe mobile valide (70, 75, 76, 77, 78).');
        }
    }
}
