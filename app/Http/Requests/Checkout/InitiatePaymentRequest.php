<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use App\Support\Data\TypedValue;
use Illuminate\Foundation\Http\FormRequest;

final class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => trim((string) $this->header('Idempotency-Key', '')),
        ]);
    }

    public function idempotencyKey(): string
    {
        return TypedValue::trimmedString($this->validated('idempotency_key'));
    }
}
