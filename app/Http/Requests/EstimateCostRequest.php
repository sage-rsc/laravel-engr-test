<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstimateCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'insurer_code' => [
                'required',
                'string',
                Rule::exists('insurers', 'code'),
            ],
            'encounter_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'specialty' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'priority_level' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'total_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'insurer_code.exists' => 'The selected insurer code is invalid.',
            'encounter_date.before_or_equal' => 'The encounter date cannot be in the future.',
            'total_amount.min' => 'Total amount must be greater than 0.',
        ];
    }
}
