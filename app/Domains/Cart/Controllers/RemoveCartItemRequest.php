<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Application\Cart\Dto\RemoveCartItemInputDto;
use App\Support\Data\TypedValue;
use Illuminate\Foundation\Http\FormRequest;

class RemoveCartItemRequest extends FormRequest
{
    /**
     * Determine if user can perform this request.
     */
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
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'guest_token' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * Normalize route and query inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'variant_id' => $this->route('variantId'),
            'guest_token' => $this->query('guest_token', $this->header('X-Cart-Token')),
        ]);
    }

    public function toDto(): RemoveCartItemInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return RemoveCartItemInputDto::fromRaw(
            guestToken: $validated['guest_token'] ?? null,
            variantId: TypedValue::int($validated['variant_id']),
        );
    }
}
