<?php

namespace App\Enums;

class MessageEnumFr
{
    const REQUIRED = 'Champ obligatoire';
    const REQUIREDLOGIN = 'Le login est obligatoire';
    const REQUIREDPASSWORD = 'Le password est obligatoire';
    const MINLENGTH = 'Trop court';
    const ISEMAIL = 'Email invalide';
    const ISPASSWORD = 'Mot de passe invalide';
    const ISSENEGALPHONE = 'Numéro de téléphone invalide';
    const ISCNI = 'Numéro de CNI invalide';
    const NUMERIC = 'Doit être un nombre';
    const MIN = 'Valeur trop petite';
    const MAX = 'Valeur trop grande';
    const IN = 'Valeur invalide';
    const UUID = 'UUID invalide';
    const EXISTS = 'N\'existe pas';
    const UNIQUE = 'Déjà utilisé';
}