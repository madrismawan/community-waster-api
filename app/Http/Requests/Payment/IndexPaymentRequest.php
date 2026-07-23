<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'household_id' => ['sometimes', 'string', 'exists:households,_id'],
            'status' => ['sometimes', Rule::enum(PaymentStatus::class)],
            'start_date' => ['required_with:end_date', 'date'],
            'end_date' => ['required_with:start_date', 'date', 'after_or_equal:start_date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
