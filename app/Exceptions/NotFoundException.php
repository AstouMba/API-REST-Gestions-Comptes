<?php

namespace App\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    public function __construct($message = 'Ressource non trouvée', $code = 404, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}