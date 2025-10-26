<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Notification Channels
    |--------------------------------------------------------------------------
    |
    | This option controls the default channels that will be used to send
    | notifications to users. The "mail" channel is used by default.
    |
    */

    'default' => env('NOTIFICATION_DEFAULT', 'mail'),

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Here you may define the channels that are available for sending
    | notifications. The "mail" channel is used by default.
    |
    */

    'channels' => [
        'mail' => [
            'class' => \Illuminate\Notifications\Channels\MailChannel::class,
        ],
        'sms' => [
            'class' => \App\Channels\SmsChannel::class,
        ],
    ],

];