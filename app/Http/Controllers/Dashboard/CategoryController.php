<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $shop = $request->user()->shop;
        $categories = Category::query()
            ->where('shop_id', $shop->id)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('dashboard.categories.index', compact('shop', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $shop = $request->user()->shop;
        $this->authorize('create', Category::class);

        $data = $this->validated($request);

        $shop->categories()->create($data + [
            'slug' => $this->uniqueSlug($shop->id, $data['name']),
        ]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);
        $data = $this->validated($request);

        if ($category->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($category->shop_id, $data['name'], $category->id);
        }

        $category->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->products()->exists()) {
            return back()->with('error', 'Move or delete this category\'s cakes before removing it.');
        }

        $category->delete();

        return back()->with('success', 'Category removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function uniqueSlug(int $shopId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $counter = 2;

        while (Category::withTrashed()
            ->where('shop_id', $shopId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
