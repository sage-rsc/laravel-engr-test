<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClaimRequest extends FormRequest
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
            'provider_name' => [
                'required',
                'string',
                'max:255',
                'min:2',
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
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.item_name' => [
                'required',
                'string',
                'max:255',
            ],
            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],
            'items.*.subtotal' => [
                'required',
                'numeric',
                'min:0',
            ],
            'total_amount' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'insurer_code.required' => 'The insurer code field is required.',
            'insurer_code.exists' => 'The selected insurer code is invalid.',
            'provider_name.required' => 'The provider name field is required.',
            'encounter_date.required' => 'The encounter date field is required.',
            'encounter_date.before_or_equal' => 'The encounter date cannot be in the future.',
            'specialty.required' => 'The specialty field is required.',
            'priority_level.required' => 'The priority level field is required.',
            'items.required' => 'At least one claim item is required.',
            'items.min' => 'At least one claim item is required.',
            'items.*.item_name.required' => 'Item name is required.',
            'items.*.item_name.string' => 'Item name must be a valid text.',
            'items.*.unit_price.required' => 'Unit price is required.',
            'items.*.unit_price.numeric' => 'Unit price must be a valid number.',
            'items.*.unit_price.min' => 'Unit price must be at least 0.',
            'items.*.unit_price.max' => 'Unit price is too large.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.quantity.integer' => 'Quantity must be a whole number.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.quantity.max' => 'Quantity is too large.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $totalAmount = collect($this->items ?? [])->sum('subtotal');
        $this->merge(['total_amount' => $totalAmount]);
    }

    protected function passedValidation(): void
    {
        $totalAmount = collect($this->items ?? [])->sum('subtotal');
        $this->merge(['total_amount' => $totalAmount]);
    }
}
