<?php

namespace App\Http\Requests\Pickup;

use App\Enums\WasteStatus;
use App\Enums\WasteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'household_id' => ['sometimes', 'string', 'exists:households,_id'],
            'type' => ['sometimes', Rule::enum(WasteType::class)],
            'status' => ['sometimes', Rule::enum(WasteStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}