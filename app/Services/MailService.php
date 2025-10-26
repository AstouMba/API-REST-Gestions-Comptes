<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class MailService
{
    public function sendAccountCredentials($email, $password)
    {
        Mail::raw("Votre mot de passe est: {$password}", function ($message) use ($email) {
            $message->to($email)
                    ->subject('Informations de connexion');
        });
    }
}