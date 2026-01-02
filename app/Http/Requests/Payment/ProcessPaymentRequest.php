<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by PaymentCollectionPolicy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_item_id' => ['required', 'integer', 'exists:payment_items,id'],
            'payment_intent_id' => ['sometimes', 'string'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_item_id.required' => 'Payment item is required.',
            'payment_item_id.exists' => 'The selected payment item does not exist.',
            'payment_intent_id.string' => 'Invalid payment intent ID.',
        ];
    }
}
