<?php

namespace App\Http\Requests\Pickup;

use Illuminate\Foundation\Http\FormRequest;

class SchedulePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'pickup_date' => ['required', 'date', 'after_or_equal:now'],
            'safety_check' => ['sometimes', 'boolean'],
        ];
    }
}
