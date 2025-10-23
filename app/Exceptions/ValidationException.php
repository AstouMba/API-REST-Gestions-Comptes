<?php

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    public function __construct($message = 'Données invalides', $code = 422, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}