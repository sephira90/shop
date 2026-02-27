<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Orders\Dto\UpdateAdminOrderStatusInputDto;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStatusUpdateRequest extends FormRequest
{
    /**
     * Determine if user can perform this request.
     */
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && ($this->user()?->can('update', $order) ?? false);
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'shipment_status' => ['nullable', Rule::enum(ShipmentStatus::class)],
        ];
    }

    /**
     * Build typed DTO for order status update flow.
     */
    public function toDto(): UpdateAdminOrderStatusInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return UpdateAdminOrderStatusInputDto::fromValidated($validated);
    }
}
