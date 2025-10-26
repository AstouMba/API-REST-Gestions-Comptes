<?php

namespace App\Exceptions;

use Exception;

class CompteNotFoundException extends Exception
{
    protected $compteId;

    public function __construct($compteId, $message = 'Le compte avec l\'ID spécifié n\'existe pas', $code = 404, \Throwable $previous = null)
    {
        $this->compteId = $compteId;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode()
    {
        return 'COMPTE_NOT_FOUND';
    }

    public function getCompteId()
    {
        return $this->compteId;
    }
}