<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Filters\Admin\AdminOrderListFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class OrderIndexRequest extends FormRequest
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
            'payment_status' => ['nullable', new Enum(PaymentStatus::class)],
            'shipment_status' => ['nullable', new Enum(ShipmentStatus::class)],
        ];
    }

    /**
     * Build typed filter object for order list query.
     */
    public function filter(): AdminOrderListFilter
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return AdminOrderListFilter::fromValidated($validated);
    }
}
