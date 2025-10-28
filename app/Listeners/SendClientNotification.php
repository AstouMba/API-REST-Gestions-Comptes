<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Notifications\CompteCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendClientNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // No services injected here to keep the listener serializable if queued.
    }

    /**
     * Handle the event.
     */
    public function handle(CompteCreated $event): void
    {
    // Send notification via email and SMS. The notification is queueable and contains only primitives.
    $event->client->notify(new CompteCreatedNotification($event->password, $event->code));
    }
}
