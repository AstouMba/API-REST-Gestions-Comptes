<?php

namespace App\Services;

use Twilio\Rest\Client;

class SmsService
{
    protected $twilio;
    protected $disabled = false;
    protected $from = null;

    public function __construct()
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_TOKEN');
        $this->from = env('TWILIO_PHONE');

        // If credentials are missing, disable SMS sending to avoid fatal errors in non-production
        if (empty($sid) || empty($token)) {
            $this->twilio = null;
            $this->disabled = true;
            \Log::warning('Twilio credentials missing: SMS sending is disabled. Set TWILIO_SID and TWILIO_TOKEN to enable.');
            return;
        }

        try {
            $this->twilio = new Client($sid, $token);
        } catch (\Throwable $e) {
            // If Twilio SDK fails to initialize for some reason, disable SMS sending but do not break the app
            $this->twilio = null;
            $this->disabled = true;
            \Log::error('Failed to initialize Twilio client: ' . $e->getMessage());
        }
    }

    public function sendVerificationCode($telephone, $code)
    {
        if ($this->disabled || ! $this->twilio) {
            \Log::info("SMS disabled or Twilio not initialized. Skipping SMS to {$telephone}.");
            return;
        }

        try {
            $this->twilio->messages->create(
                $telephone,
                [
                    'from' => $this->from,
                    'body' => "Votre code de verification est: {$code}. Utilisez ce code lors de votre premiere connexion."
                ]
            );
        } catch (\Throwable $e) {
            // Log error or handle failure but do not throw
            \Log::error("Failed to send SMS to {$telephone}: " . $e->getMessage());
        }
    }
}