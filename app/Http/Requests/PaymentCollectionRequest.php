<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['sometimes', 'integer', 'exists:payment_items,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:65535'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.type' => ['required', 'string', 'in:service,product,fee'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The collection name is required.',
            'name.max' => 'The collection name must not exceed 255 characters.',
            'items.required' => 'At least one payment item is required.',
            'items.min' => 'At least one payment item is required.',
            'items.*.name.required' => 'Each item must have a name.',
            'items.*.price.required' => 'Each item must have an amount.',
            'items.*.price.min' => 'Item amount must be a positive number.',
            'items.*.type.required' => 'Each item must have a type.',
            'items.*.type.in' => 'Item type must be either service, product, or fee.',
        ];
    }
}
