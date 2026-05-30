<?php

return [

    'api_key' => env('TELEGRAM_API_KEY'),

    'chat_id' => env('TELEGRAM_CHAT_ID'),

    /*
    | Queue used for NotifySimpleCryptoPaymentPaid. Null = QUEUE_CONNECTION (database/redis).
    | Run a worker: php artisan queue:work (composer dev starts queue:listen automatically).
    | Do not use ->afterResponse() for this job: Laravel runs after-response work via dispatchSync,
    | which forces the sync queue and sends Telegram in the same PHP process without a worker.
    | Optional dedicated queue name: TELEGRAM_QUEUE=notifications
    */
    'queue_connection' => env('TELEGRAM_QUEUE_CONNECTION'),

    'queue' => env('TELEGRAM_QUEUE', 'default'),

];
