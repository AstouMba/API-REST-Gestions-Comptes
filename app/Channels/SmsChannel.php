<?php

namespace App\Channels;

use App\Services\SmsService;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toSms($notifiable);

        $this->smsService->sendVerificationCode($notifiable->telephone, $notification->code);
    }
}