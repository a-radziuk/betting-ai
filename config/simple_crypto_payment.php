<?php

return [

    'wallets' => [
        'ethereum_usdt' => [
            'label' => 'Ethereum USDT',
            'address' => env('SIMPLE_CRYPTO_ETHEREUM_USDT_ADDRESS'),
            'visible' => filter_var(env('SIMPLE_CRYPTO_ETHEREUM_USDT_VISIBLE', false), FILTER_VALIDATE_BOOLEAN),
        ],
        'tron_usdt' => [
            'label' => 'Tron USDT',
            'address' => env('SIMPLE_CRYPTO_TRON_USDT_ADDRESS'),
            'visible' => filter_var(env('SIMPLE_CRYPTO_TRON_USDT_VISIBLE', false), FILTER_VALIDATE_BOOLEAN),
        ],
    ],

];
