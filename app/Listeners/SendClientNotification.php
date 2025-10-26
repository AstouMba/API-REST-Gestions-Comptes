<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Notifications\CompteCreatedNotification;
use App\Services\MailService;
use App\Services\SmsService;

class SendClientNotification
{
    protected $mailService;
    protected $smsService;

    /**
     * Create the event listener.
     */
    public function __construct(MailService $mailService, SmsService $smsService)
    {
        $this->mailService = $mailService;
        $this->smsService = $smsService;
    }

    /**
     * Handle the event.
     */
    public function handle(CompteCreated $event): void
    {
        // Send notification via email and SMS
        $event->client->notify(new CompteCreatedNotification($event->password, $event->code, $this->mailService, $this->smsService));
    }
}
