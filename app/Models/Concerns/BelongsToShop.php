<?php

namespace App\Models\Concerns;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToShop
{
    public function scopeForShop(Builder $query, Shop|int $shop): Builder
    {
        return $query->where($this->getTable().'.shop_id', $shop instanceof Shop ? $shop->id : $shop);
    }

    public function belongsToShop(Shop|int $shop): bool
    {
        return (int) $this->shop_id === ($shop instanceof Shop ? $shop->id : $shop);
    }
}
