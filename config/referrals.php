<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Referral promocode settings
    |--------------------------------------------------------------------------
    |
    | Active subscribers can share a personal code. Redeeming it grants the
    | same tips access extension as a normal promocode. The referrer receives
    | a separate bonus when someone uses their code.
    |
    */

    'code_prefix' => env('REFERRAL_CODE_PREFIX', 'REF-'),

    'redeem_days' => (int) env('REFERRAL_REDEEM_DAYS', 3),

    'referrer_bonus_days' => (int) env('REFERRAL_REFERRER_BONUS_DAYS', 3),

];
