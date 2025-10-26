<?php

namespace App\Services;

use Twilio\Rest\Client;

class SmsService
{
    protected $twilio;

    public function __construct()
    {
        $this->twilio = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
    }

    public function sendVerificationCode($telephone, $code)
    {
        try {
            $this->twilio->messages->create(
                $telephone,
                [
                    'from' => env('TWILIO_PHONE'),
                    'body' => "Votre code de verification est: {$code}. Utilisez ce code lors de votre premiere connexion."
                ]
            );
        } catch (\Exception $e) {
            // Log error or handle failure
            \Log::error("Failed to send SMS to {$telephone}: " . $e->getMessage());
        }
    }
}