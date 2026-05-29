<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public page main content cache (Redis)
    |--------------------------------------------------------------------------
    |
    | Caches rendered HTML for everything inside <main> on /, /tournaments/{id},
    | /events/{id}, /players, and /players/{id}. Header, subbar, and footer are
    | always fresh. Keys include entity id, locale, page (where relevant), and
    | viewer capabilities on event pages (guest vs tips/place-bets privileges).
    |
    */

    'cache_enabled' => filter_var(env('PAGES_CACHE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'cache_ttl' => (int) env('PAGES_CACHE_TTL', 300),

    'cache_store' => env('PAGES_CACHE_STORE', 'redis'),

];
