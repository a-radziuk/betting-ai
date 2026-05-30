<?php

namespace App\PayWithMetamask\Http\Requests;

use App\Models\MetamaskPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tx_hash' => [
                'required',
                'string',
                'regex:/^0x[a-fA-F0-9]{64}$/',
                Rule::unique('metamask_payments', 'tx_hash'),
            ],
            'token' => ['required', 'string', Rule::in([
                MetamaskPayment::TOKEN_ETH,
                MetamaskPayment::TOKEN_USDT,
                MetamaskPayment::TOKEN_USDC,
            ])],
        ];
    }
}
