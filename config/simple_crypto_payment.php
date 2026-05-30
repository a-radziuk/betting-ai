<?php

return [

    /*
    | URL notified when a user clicks "I have paid". POST JSON:
    | {"network":"ethereum","wallet":"0x…","mark":"BETAI-…"}
    | Processed asynchronously via NotifyCryptoWatcherOfPayment (queue worker required).
    */
    'crypto_watcher_url' => env('CRYPTO_WATCHER'),

    'wallets' => [
        'ethereum_usdt' => [
            'label' => 'Ethereum USDT',
            'network' => 'ethereum',
            'address' => env('SIMPLE_CRYPTO_ETHEREUM_USDT_ADDRESS'),
            'visible' => filter_var(env('SIMPLE_CRYPTO_ETHEREUM_USDT_VISIBLE', false), FILTER_VALIDATE_BOOLEAN),
        ],
        'tron_usdt' => [
            'label' => 'Tron USDT',
            'network' => 'tron',
            'address' => env('SIMPLE_CRYPTO_TRON_USDT_ADDRESS'),
            'visible' => filter_var(env('SIMPLE_CRYPTO_TRON_USDT_VISIBLE', false), FILTER_VALIDATE_BOOLEAN),
        ],
    ],

];
