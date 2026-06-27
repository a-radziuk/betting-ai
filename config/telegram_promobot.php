<?php

return [

    'days' => (int) env('TELEGRAM_PROMOBOT_DAYS', 3),

    'api_secret' => env('TELEGRAM_PROMOBOT_API_SECRET'),

    'token' => env('TELEGRAM_PROMOBOT_TOKEN'),

    'partner_codes' => ['55502', '55501', '55503', '55504'],

];
