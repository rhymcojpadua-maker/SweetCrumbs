<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopSetting extends Model
{
    protected $fillable = [
        'shop_id',
        'pickup_enabled',
        'delivery_enabled',
        'pickup_days',
        'delivery_days',
        'time_slots',
        'unavailable_dates',
        'max_orders_per_day',
        'advance_notice_hours',
        'delivery_fee',
        'free_delivery_threshold',
        'pickup_instructions',
        'delivery_notes',
    ];

    protected function casts(): array
    {
        return [
            'pickup_enabled' => 'boolean',
            'delivery_enabled' => 'boolean',
            'pickup_days' => 'array',
            'delivery_days' => 'array',
            'time_slots' => 'array',
            'unavailable_dates' => 'array',
            'max_orders_per_day' => 'integer',
            'advance_notice_hours' => 'integer',
            'delivery_fee' => 'decimal:2',
            'free_delivery_threshold' => 'decimal:2',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
