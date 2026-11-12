<?php

namespace App\Models;

use App\Enums\ShopStatus;
use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'tagline',
        'description',
        'logo_path',
        'cover_path',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'status',
        'is_featured',
        'rating',
        'approved_at',
        'suspended_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShopStatus::class,
            'is_featured' => 'boolean',
            'rating' => 'decimal:2',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Shop $shop): void {
            $shop->settings()->create([
                'pickup_days' => [1, 2, 3, 4, 5, 6, 7],
                'delivery_days' => [1, 2, 3, 4, 5, 6],
                'time_slots' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
                'max_orders_per_day' => 20,
                'advance_notice_hours' => 24,
                'delivery_fee' => 120,
            ]);
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(ShopSetting::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(ShopCustomer::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('is_active', true)->whereNull('deleted_at');
    }

    public function scopeActive($query)
    {
        return $query->where('status', ShopStatus::Active->value);
    }

    public function scopeFeatured($query)
    {
        return $query->active()->where('is_featured', true);
    }

    public function isActive(): bool
    {
        return $this->status === ShopStatus::Active;
    }

    public function isPending(): bool
    {
        return $this->status === ShopStatus::Pending;
    }

    public function isSuspended(): bool
    {
        return $this->status === ShopStatus::Suspended;
    }

    public function logoUrl(): string
    {
        return $this->assetUrl($this->logo_path, 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=F8DDE3&color=382820&bold=true');
    }

    public function coverUrl(): string
    {
        return $this->assetUrl($this->cover_path, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=1600&q=80');
    }

    public function storefrontUrl(): string
    {
        return route('shops.show', $this);
    }

    private function assetUrl(?string $path, string $fallback): string
    {
        if (! $path) {
            return $fallback;
        }

        return str_starts_with($path, 'http') ? $path : asset('storage/'.$path);
    }

    public function deliveryFee(): float
    {
        return (float) ($this->settings?->delivery_fee ?? 0);
    }
}
