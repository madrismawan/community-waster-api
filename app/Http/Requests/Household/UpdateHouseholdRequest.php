<?php

namespace App\Http\Requests\Household;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'owner_name' => ['sometimes', 'required', 'string', 'max:120'],
            'address' => ['sometimes', 'required', 'string', 'max:500'],
            'block' => ['sometimes', 'nullable', 'string', 'max:20'],
            'no' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
