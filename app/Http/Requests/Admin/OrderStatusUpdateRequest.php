<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Admin\Orders\Dto\UpdateAdminOrderStatusInputDto;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

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
            'status' => ['nullable', 'in:pending,paid,processing,shipped,completed,cancelled,refunded'],
            'payment_status' => ['nullable', 'in:pending,authorized,captured,failed,refunded'],
            'shipment_status' => ['nullable', 'in:pending,packed,shipped,delivered,returned'],
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
