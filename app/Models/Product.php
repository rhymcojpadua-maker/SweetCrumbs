<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToShop, HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'category_id',
        'name',
        'slug',
        'description',
        'ingredients',
        'allergens',
        'base_price',
        'image_path',
        'servings',
        'customization_options',
        'preparation_hours',
        'stock_quantity',
        'low_stock_threshold',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'customization_options' => 'array',
            'preparation_hours' => 'integer',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->active()->where('is_featured', true);
    }

    public function inStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function imageUrl(): string
    {
        if (! $this->image_path) {
            return 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?auto=format&fit=crop&w=1000&q=80';
        }

        return str_starts_with($this->image_path, 'http')
            ? $this->image_path
            : asset('storage/'.$this->image_path);
    }

    public function defaultVariant(): ?ProductVariant
    {
        return $this->variants->firstWhere('is_default', true) ?? $this->variants->first();
    }

    public function priceFor(?ProductVariant $variant = null): float
    {
        return (float) ($variant?->price ?? $this->base_price);
    }

    public function customizationPrice(array $selectedOptions = []): float
    {
        $total = 0.0;

        foreach ($this->customization_options ?? [] as $group) {
            $selected = $selectedOptions[$group['key'] ?? ''] ?? null;
            if ($selected === null) {
                continue;
            }

            foreach ($group['options'] ?? [] as $option) {
                if (($option['value'] ?? null) === $selected) {
                    $total += (float) ($option['price'] ?? 0);
                    break;
                }
            }
        }

        return $total;
    }

    public function formattedCustomizations(?array $selectedOptions): array
    {
        $result = [];

        foreach ($this->customization_options ?? [] as $group) {
            $key = $group['key'] ?? '';
            $selected = $selectedOptions[$key] ?? null;
            if ($selected === null || $selected === '') {
                continue;
            }

            $label = collect($group['options'] ?? [])->firstWhere('value', $selected)['label'] ?? $selected;
            $result[] = ['group' => $group['label'] ?? $key, 'label' => $label];
        }

        return $result;
    }
}
