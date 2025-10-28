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
    const USER_ID_REQUIRED = 'Le paramètre user_id est requis';
    const USER_NOT_FOUND = 'Utilisateur non trouvé';
    const COMPTE_RETRIEVED = 'Compte récupéré avec succès';
    const COMPTES_RETRIEVED = 'Comptes retrieved successfully';
    const COMPTE_CREATED = 'Compte creation request submitted successfully';
    const ERROR_VALIDATION = 'VALIDATION_ERROR';
    const ERROR_USER_NOT_FOUND = 'USER_NOT_FOUND';
    const ERROR_COMPTE_NOT_FOUND = 'COMPTE_NOT_FOUND';
    // Human-friendly messages
    const COMPTE_NOT_FOUND = 'Compte introuvable';
    const COMPTE_UPDATED = 'Compte mis à jour avec succès'; 
    const COMPTE_DELETED_ARCHIVED = 'Compte supprimé et archivé avec succès';
}