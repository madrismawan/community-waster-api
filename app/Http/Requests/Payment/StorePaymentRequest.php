<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'household_id' => [
                'required',
                'string',
                Rule::exists('households', '_id')->whereNull('deleted_at'),
            ],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
        ];
    }
}
