<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Category extends Model
{
    use BelongsToShop, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'name',
        'slug',
        'description',
        'image_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function imageUrl(): string
    {
        if (! $this->image_path) {
            return 'https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?auto=format&fit=crop&w=800&q=80';
        }

        return str_starts_with($this->image_path, 'http')
            ? $this->image_path
            : asset('storage/'.$this->image_path);
    }

    public static function marketplace(?int $limit = null): Collection
    {
        $categories = static::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)->whereHas('shop', fn ($shop) => $shop->active())])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $unique = $categories->groupBy('slug')->map(function (Collection $group) {
            $category = clone $group->first();
            $category->products_count = (int) $group->sum('products_count');

            return $category;
        })->values();

        return $limit ? $unique->take($limit)->values() : $unique;
    }
}
