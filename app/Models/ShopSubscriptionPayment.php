<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopSubscriptionPayment extends Model
{
    protected $fillable = [
        'shop_subscription_id',
        'provider',
        'provider_checkout_id',
        'reference_number',
        'amount',
        'currency',
        'status',
        'checkout_url',
        'expires_at',
        'provider_event_id',
        'payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ShopSubscription::class, 'shop_subscription_id');
    }
}
