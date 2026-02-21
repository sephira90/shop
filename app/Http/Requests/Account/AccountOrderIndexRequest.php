<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\OrderStatus;
use App\Filters\Account\AccountOrderListFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AccountOrderIndexRequest extends FormRequest
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
    public function filter(): AccountOrderListFilter
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return AccountOrderListFilter::fromValidated($validated);
    }
}
