<?php

namespace App\Http\Requests\Pickup;

use App\Enums\WasteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'household_id' => ['required', 'string', 'exists:households,_id'],
            'type' => ['required', Rule::enum(WasteType::class)],
            'safety_check' => ['sometimes', 'boolean'],
        ];
    }
}
