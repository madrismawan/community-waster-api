<?php

namespace App\Http\Requests\Household;

use Illuminate\Foundation\Http\FormRequest;

class IndexHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:500'],
            'block' => ['sometimes', 'nullable', 'string', 'max:20'],
            'no' => ['sometimes', 'nullable', 'string', 'max:20'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
