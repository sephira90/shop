<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'provider',
        'tracking_number',
        'cost',
        'payload',
        'shipped_at',
        'delivered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'cost' => 'decimal:2',
            'payload' => 'array',
            'shipped_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
        ];
    }

    /**
     * Parent order relation.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
