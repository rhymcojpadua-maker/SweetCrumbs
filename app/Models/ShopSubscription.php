<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopSubscription extends Model
{
    protected $fillable = [
        'shop_id',
        'status',
        'period_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'period_ends_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ShopSubscriptionPayment::class);
    }

    public function hasAccess(): bool
    {
        return in_array($this->status, ['trialing', 'active'], true)
            && $this->period_ends_at?->isFuture() === true;
    }
}
