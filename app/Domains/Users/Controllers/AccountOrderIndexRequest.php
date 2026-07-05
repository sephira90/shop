<?php

declare(strict_types=1);

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Application\Dto\AccountOrderListFilterDto;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class AccountOrderIndexRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', new Enum(OrderStatus::class)],
        ];
    }

    /**
     * Build typed filter object for account orders list.
     */
    public function filter(): AccountOrderListFilterDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return AccountOrderListFilterDto::fromValidated($validated);
    }
}
