<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedException extends Exception
{
    public function __construct($message = 'Accès non autorisé', $code = 401, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}