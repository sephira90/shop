<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Application\Cart\Dto\CartUpsertItemInputDto;
use Illuminate\Foundation\Http\FormRequest;

class UpsertCartItemRequest extends FormRequest
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
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'guest_token' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * Build typed DTO for cart upsert.
     */
    public function toDto(): CartUpsertItemInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return CartUpsertItemInputDto::fromValidated($validated);
    }
}
