<?php

return [

    /*
    | POST when a MetaMask payment is recorded. JSON:
    | {"tx_hash":"0x…","token":"USDT","amount_cents":2999}
    | Processed asynchronously via NotifyMetamaskTransactionWatcher (queue worker required).
    */
    'transaction_watcher_url' => env('METAMASK_TRANSACTION_WATCHER'),

    'ethereum_wallet' => env('PAY_WITH_METAMASK_ETHEREUM_WALLET'),

    'usdt_contract_address' => env('PAY_WITH_METAMASK_USDT_CONTRACT', '0xdAC17F958D2ee523a2206206994597C13D832831'),

    'usdc_contract_address' => env('PAY_WITH_METAMASK_USDC_CONTRACT', '0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48'),

    'chain_id' => (int) env('PAY_WITH_METAMASK_CHAIN_ID', 1),

    /*
    | Optional ETH amounts per plan (wei). When unset, ETH is not offered.
    */
    'eth_amount_wei_by_plan' => [
        'one_week' => env('PAY_WITH_METAMASK_ONE_WEEK_ETH_WEI'),
        'one_month' => env('PAY_WITH_METAMASK_ONE_MONTH_ETH_WEI'),
        'three_months' => env('PAY_WITH_METAMASK_THREE_MONTHS_ETH_WEI'),
        'one_year' => env('PAY_WITH_METAMASK_ONE_YEAR_ETH_WEI'),
    ],

];
