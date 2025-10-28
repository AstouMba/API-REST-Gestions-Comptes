<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Enums\MessageEnumFr;

class NciRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Vérifier si c'est un nombre et exactement 13 chiffres
        if (!is_numeric($value) || strlen((string)$value) !== 13) {
            $fail(MessageEnumFr::ISCNI);
            return;
        }
        
        // Vérifier que ce n'est pas une séquence répétitive (ex: 1111111111111)
        $isRepetitive = true;
        for ($i = 1; $i < 13; $i++) {
            if ($value[$i] !== $value[0]) {
                $isRepetitive = false;
                break;
            }
        }
        if ($isRepetitive) {
            $fail(MessageEnumFr::ISCNI);
            return;
        }
        
        // Vérifier que ce n'est pas une séquence séquentielle simple (ex: 1234567890123)
        $sequential = (string)$value;
        $isSequential = true;
        for ($i = 0; $i < 12; $i++) {
            if ((int)$sequential[$i] + 1 !== (int)$sequential[$i + 1]) {
                $isSequential = false;
                break;
            }
        }
        if ($isSequential) {
            $fail(MessageEnumFr::ISCNI);
        }
    }
}
