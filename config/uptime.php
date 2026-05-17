<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Email
    |--------------------------------------------------------------------------
    | The address that receives up/down alerts.
    | Override with UPTIME_NOTIFICATION_EMAIL in your .env file.
    */
    'notification_email' => env('UPTIME_NOTIFICATION_EMAIL', 'admin@example.com'),
];
