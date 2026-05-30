<?php

namespace App\Support;

use App\Models\SimpleCryptoPayment;
use Illuminate\Support\Str;

final class SimpleCryptoPaymentCode
{
    public static function generate(): string
    {
        do {
            $code = 'BETAI-'.Str::upper(Str::random(8));
        } while (SimpleCryptoPayment::query()->where('payment_code', $code)->exists());

        return $code;
    }
}
