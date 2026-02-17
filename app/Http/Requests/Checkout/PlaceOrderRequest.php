<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Determine if user can perform this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $guestTokenRules = $this->user() === null
            ? ['required', 'string', 'max:80']
            : ['nullable', 'string', 'max:80'];

        return [
            'guest_token' => $guestTokenRules,
            'email' => ['required', 'email', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'billing_address' => ['required', 'array'],
            'billing_address.line1' => ['required', 'string', 'max:180'],
            'billing_address.city' => ['required', 'string', 'max:80'],
            'billing_address.country' => ['required', 'string', 'size:2'],
            'billing_address.postcode' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.line1' => ['required', 'string', 'max:180'],
            'shipping_address.city' => ['required', 'string', 'max:80'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_address.postcode' => ['required', 'string', 'max:20'],
        ];
    }
}
