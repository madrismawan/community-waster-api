<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'household_id' => ['required', 'string', 'exists:households,_id'],
            'amount' => ['required', 'numeric', 'decimal:0,2'],
        ];
    }
}
