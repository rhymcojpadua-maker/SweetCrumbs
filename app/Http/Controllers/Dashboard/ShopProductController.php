<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShopProductController extends Controller
{
    public function index(Request $request): View
    {
        $shop = $request->user()->shop;

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,inactive,low_stock,out_of_stock'],
        ]);

        $products = Product::query()
            ->with(['category', 'images', 'variants'])
            ->where('shop_id', $shop->id)
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', '%'.trim($term).'%'))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when(($filters['status'] ?? null) === 'low_stock', fn ($query) => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->where('stock_quantity', '>', 0))
            ->when(($filters['status'] ?? null) === 'out_of_stock', fn ($query) => $query->where('stock_quantity', 0))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->where('shop_id', $shop->id)->orderBy('sort_order')->get();

        return view('dashboard.products.index', compact('shop', 'products', 'categories', 'filters'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Product::class);

        $shop = $request->user()->shop;
        $categories = Category::query()->where('shop_id', $shop->id)->orderBy('sort_order')->get();

        return view('dashboard.products.form', [
            'shop' => $shop,
            'product' => new Product(['is_active' => true, 'stock_quantity' => 0, 'low_stock_threshold' => 3, 'preparation_hours' => 24]),
            'categories' => $categories,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);
        $shop = $request->user()->shop;
        $data = $this->validated($request, $shop->id);

        DB::transaction(function () use ($request, $shop, $data) {
            $product = $shop->products()->create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'],
                'ingredients' => $data['ingredients'] ?? null,
                'allergens' => $data['allergens'] ?? null,
                'base_price' => $data['base_price'],
                'servings' => $data['servings'] ?? null,
                'preparation_hours' => $data['preparation_hours'],
                'stock_quantity' => $data['stock_quantity'],
                'low_stock_threshold' => $data['low_stock_threshold'],
                'is_active' => $request->boolean('is_active'),
                'is_featured' => $request->boolean('is_featured'),
                'customization_options' => $this->customizationOptions($request),
            ]);

            $this->storeImages($request, $product, $shop->id);
            $this->syncVariants($product, $request->input('variants', []), $data['base_price']);
        });

        return redirect()->route('dashboard.products.index')->with('success', 'Cake created and added to your storefront.');
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        $shop = $request->user()->shop;
        $product->load(['images', 'variants']);
        $categories = Category::query()->where('shop_id', $shop->id)->orderBy('sort_order')->get();

        return view('dashboard.products.form', [
            'shop' => $shop,
            'product' => $product,
            'categories' => $categories,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $shop = $request->user()->shop;
        $data = $this->validated($request, $shop->id);

        DB::transaction(function () use ($request, $shop, $product, $data) {
            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'ingredients' => $data['ingredients'] ?? null,
                'allergens' => $data['allergens'] ?? null,
                'base_price' => $data['base_price'],
                'servings' => $data['servings'] ?? null,
                'preparation_hours' => $data['preparation_hours'],
                'stock_quantity' => $data['stock_quantity'],
                'low_stock_threshold' => $data['low_stock_threshold'],
                'is_active' => $request->boolean('is_active'),
                'is_featured' => $request->boolean('is_featured'),
                'customization_options' => $this->customizationOptions($request),
            ]);

            $this->storeImages($request, $product, $shop->id);
            $this->syncVariants($product, $request->input('variants', []), $data['base_price']);
        });

        return redirect()->route('dashboard.products.index')->with('success', 'Cake updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        $product->delete();

        return redirect()->route('dashboard.products.index')->with('success', 'Cake removed from your storefront.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless((int) $image->product_id === (int) $product->id, 404);

        if (! str_starts_with($image->path, 'http')) {
            Storage::disk('public')->delete($image->path);
        }

        $image->delete();

        if ($product->image_path === $image->path) {
            $product->update(['image_path' => $product->images()->value('path')]);
        }

        return back()->with('success', 'Image removed.');
    }

    private function validated(Request $request, int $shopId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('shop_id', $shopId)],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'ingredients' => ['nullable', 'string', 'max:1500'],
            'allergens' => ['nullable', 'string', 'max:1000'],
            'base_price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'servings' => ['nullable', 'string', 'max:80'],
            'preparation_hours' => ['required', 'integer', 'min:1', 'max:336'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery' => ['nullable', 'array', 'max:6'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'variants' => ['nullable', 'array', 'max:10'],
            'variants.*.name' => ['nullable', 'string', 'max:80'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'variants.*.sku' => ['nullable', 'string', 'max:80'],
            'customization_groups' => ['nullable', 'array', 'max:6'],
            'customization_groups.*.label' => ['nullable', 'string', 'max:80'],
            'customization_groups.*.required' => ['nullable'],
            'customization_groups.*.options' => ['nullable', 'array', 'max:10'],
            'customization_groups.*.options.*.label' => ['nullable', 'string', 'max:80'],
            'customization_groups.*.options.*.price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);
    }

    private function storeImages(Request $request, Product $product, int $shopId): void
    {
        $sort = (int) $product->images()->max('sort_order');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('shops/'.$shopId.'/products', 'public');
            $product->images()->create(['path' => $path, 'alt' => $product->name, 'sort_order' => ++$sort]);
            $product->update(['image_path' => $path]);
        }

        foreach ($request->file('gallery') ?? [] as $file) {
            $path = $file->store('shops/'.$shopId.'/products', 'public');
            $product->images()->create(['path' => $path, 'alt' => $product->name, 'sort_order' => ++$sort]);
            if (! $product->image_path) {
                $product->update(['image_path' => $path]);
            }
        }
    }

    private function syncVariants(Product $product, array $variants, float $basePrice): void
    {
        $clean = collect($variants)
            ->filter(fn ($variant) => filled($variant['name'] ?? null) && isset($variant['price']))
            ->values();

        $product->variants()->delete();

        if ($clean->isEmpty()) {
            return;
        }

        $hasDefault = $clean->contains(fn ($variant) => ! empty($variant['is_default']));

        $clean->each(function ($variant, $index) use ($product, $hasDefault) {
            $product->variants()->create([
                'name' => $variant['name'],
                'sku' => $variant['sku'] ?? null,
                'price' => $variant['price'],
                'is_default' => $hasDefault ? ! empty($variant['is_default']) : $index === 0,
                'sort_order' => $index,
            ]);
        });
    }

    private function customizationOptions(Request $request): array
    {
        $groups = [];

        foreach ($request->input('customization_groups', []) as $group) {
            $label = trim((string) ($group['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $options = [];
            foreach ($group['options'] ?? [] as $option) {
                $optionLabel = trim((string) ($option['label'] ?? ''));
                if ($optionLabel === '') {
                    continue;
                }

                $value = Str::slug($optionLabel) ?: Str::lower($optionLabel);
                $options[] = [
                    'value' => $value,
                    'label' => $optionLabel,
                    'price' => round((float) ($option['price'] ?? 0), 2),
                ];
            }

            if ($options === []) {
                continue;
            }

            $key = Str::slug($label) ?: 'option';
            $groups[] = [
                'key' => $key,
                'label' => $label,
                'required' => ! empty($group['required']),
                'options' => $options,
            ];
        }

        return $groups;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'cake';
        $slug = $base;
        $counter = 2;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
