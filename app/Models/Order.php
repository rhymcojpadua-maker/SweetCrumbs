<?php

namespace App\Models;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToShop;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use BelongsToShop, HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'public_token',
        'shop_id',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'fulfillment_type',
        'delivery_address',
        'subtotal',
        'delivery_fee',
        'total',
        'status',
        'payment_status',
        'scheduled_date',
        'scheduled_time',
        'notes',
        'payment_method',
        'inventory_reserved',
        'placed_at',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'fulfillment_type' => FulfillmentType::class,
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'scheduled_date' => 'date',
            'inventory_reserved' => 'boolean',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->order_number ??= 'CK-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            $order->public_token ??= (string) Str::uuid();
            $order->placed_at ??= now();
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            OrderStatus::Cancelled->value,
            OrderStatus::Rejected->value,
        ]);
    }

    public function scopeAwaitingAction($query)
    {
        return $query->whereIn('status', [
            OrderStatus::PendingConfirmation->value,
            OrderStatus::Confirmed->value,
            OrderStatus::Preparing->value,
            OrderStatus::ReadyForPickup->value,
            OrderStatus::OutForDelivery->value,
        ]);
    }

    public function canTransitionTo(OrderStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }

    public function isPaid(): bool
    {
        return $this->payment_status->isSuccessful();
    }

    public function isCancellableByCustomer(): bool
    {
        return in_array($this->status, [OrderStatus::PendingPayment, OrderStatus::PendingConfirmation], true);
    }

    public function publicUrl(): string
    {
        return route('orders.show', ['order' => $this->order_number, 'token' => $this->public_token]);
    }

    public function itemSummary(): string
    {
        return $this->items->map(fn (OrderItem $item) => "{$item->quantity}x {$item->product_name}")->implode(', ');
    }
}
