@extends('layouts.dashboard')

@section('title', $mode === 'create' ? 'New cake' : 'Edit '.$product->name)
@section('eyebrow', 'Catalogue')
@section('heading', $mode === 'create' ? 'New cake' : $product->name)

@php
    $initialVariants = old('variants', $product->variants->map(fn ($variant) => [
        'name' => $variant->name,
        'price' => (float) $variant->price,
        'sku' => $variant->sku,
        'is_default' => (bool) $variant->is_default,
    ])->values()->all());

    $initialGroups = old('customization_groups', collect($product->customization_options ?? [])->map(fn ($group) => [
        'label' => $group['label'] ?? '',
        'required' => (bool) ($group['required'] ?? false),
        'options' => collect($group['options'] ?? [])->map(fn ($option) => [
            'label' => $option['label'] ?? '',
            'price' => (float) ($option['price'] ?? 0),
        ])->values()->all(),
    ])->values()->all());
@endphp

@section('actions')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('dashboard.products.index') }}" class="text-sm font-semibold text-cocoa hover:text-ink">← Back to cakes</a>
        @if ($mode === 'edit')
            <a href="{{ route('products.show', [$shop, $product]) }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">View on storefront</a>
        @endif
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ $mode === 'create' ? route('dashboard.products.store') : route('dashboard.products.update', $product) }}" enctype="multipart/form-data" class="grid gap-5 xl:grid-cols-[1fr_21rem]" x-data="productForm(@js($initialVariants), @js($initialGroups))">
        @csrf
        @if ($mode === 'edit')
            @method('PATCH')
        @endif

        <div class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">The essentials</h2>
                <div class="mt-5 space-y-4">
                    <div>
                        <label for="name" class="label">Cake name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required maxlength="120" class="input">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="category_id" class="label">Category</label>
                            <select name="category_id" id="category_id" required class="input">
                                <option value="">Choose a category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @if ($categories->isEmpty())
                                <p class="mt-1 text-xs text-rose">Create a category first from the Categories page.</p>
                            @endif
                        </div>
                        <div>
                            <label for="servings" class="label">Servings</label>
                            <input type="text" name="servings" id="servings" value="{{ old('servings', $product->servings) }}" maxlength="80" class="input" placeholder="8–10 people">
                        </div>
                    </div>
                    <div>
                        <label for="description" class="label">Description</label>
                        <textarea name="description" id="description" rows="5" required minlength="20" maxlength="3000" class="input">{{ old('description', $product->description) }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="ingredients" class="label">Ingredients (optional)</label>
                            <textarea name="ingredients" id="ingredients" rows="3" maxlength="1500" class="input">{{ old('ingredients', $product->ingredients) }}</textarea>
                        </div>
                        <div>
                            <label for="allergens" class="label">Allergens (optional)</label>
                            <textarea name="allergens" id="allergens" rows="3" maxlength="1000" class="input">{{ old('allergens', $product->allergens) }}</textarea>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-2xl font-semibold">Sizes &amp; pricing</h2>
                        <p class="mt-1 text-xs text-cocoa">Optional. The base price is used when no sizes are set.</p>
                    </div>
                    <button type="button" class="btn-secondary btn-sm" @click="addVariant()">Add size</button>
                </div>

                <div class="mt-5 space-y-3">
                    <template x-for="(variant, index) in variants" :key="index">
                        <div class="grid gap-3 rounded-xl border border-ink/10 bg-parchment/30 p-3 sm:grid-cols-[1.4fr_1fr_1fr_auto]">
                            <div>
                                <label class="label text-[10px]">Size name</label>
                                <input type="text" :name="`variants[${index}][name]`" x-model="variant.name" class="input py-2 text-sm" placeholder="6 inch">
                            </div>
                            <div>
                                <label class="label text-[10px]">Price (₱)</label>
                                <input type="number" step="0.01" min="0" :name="`variants[${index}][price]`" x-model="variant.price" class="input py-2 text-sm">
                            </div>
                            <div>
                                <label class="label text-[10px]">SKU</label>
                                <input type="text" :name="`variants[${index}][sku]`" x-model="variant.sku" class="input py-2 text-sm" placeholder="Optional">
                            </div>
                            <div class="flex items-end">
                                <button type="button" class="btn-ghost btn-sm text-rose" @click="variants.splice(index, 1)">Remove</button>
                            </div>
                            <label class="flex items-center gap-2 text-xs text-cocoa sm:col-span-4">
                                <input type="radio" name="default_variant_marker" :checked="variant.is_default" @change="setDefault(index)" class="border-ink/20 text-rose focus:ring-rose">
                                Default size
                                <input type="hidden" :name="`variants[${index}][is_default]`" :value="variant.is_default ? 1 : 0">
                            </label>
                        </div>
                    </template>
                    <p x-show="variants.length === 0" class="rounded-xl bg-parchment/50 px-4 py-3 text-xs text-cocoa">No sizes yet. The base price below is what customers will pay.</p>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="base_price" class="label">Base price (₱)</label>
                        <input type="number" step="0.01" min="1" max="1000000" name="base_price" id="base_price" value="{{ old('base_price', (float) $product->base_price) }}" required class="input">
                    </div>
                    <div>
                        <label for="preparation_hours" class="label">Prep time (hours)</label>
                        <input type="number" min="1" max="336" name="preparation_hours" id="preparation_hours" value="{{ old('preparation_hours', $product->preparation_hours) }}" required class="input">
                    </div>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-2xl font-semibold">Customisation</h2>
                        <p class="mt-1 text-xs text-cocoa">Let customers choose a message, colour, or finishing touch.</p>
                    </div>
                    <button type="button" class="btn-secondary btn-sm" @click="addGroup()">Add group</button>
                </div>

                <div class="mt-5 space-y-4">
                    <template x-for="(group, groupIndex) in groups" :key="groupIndex">
                        <div class="rounded-xl border border-ink/10 bg-parchment/30 p-4">
                            <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto]">
                                <div>
                                    <label class="label text-[10px]">Group label</label>
                                    <input type="text" :name="`customization_groups[${groupIndex}][label]`" x-model="group.label" class="input py-2 text-sm" placeholder="Cake message">
                                </div>
                                <label class="flex items-end gap-2 pb-2 text-xs text-cocoa">
                                    <input type="checkbox" :name="`customization_groups[${groupIndex}][required]`" value="1" x-model="group.required" class="rounded border-ink/20 text-rose focus:ring-rose">
                                    Required
                                </label>
                                <div class="flex items-end">
                                    <button type="button" class="btn-ghost btn-sm text-rose" @click="groups.splice(groupIndex, 1)">Remove group</button>
                                </div>
                            </div>

                            <div class="mt-3 space-y-2">
                                <template x-for="(option, optionIndex) in group.options" :key="optionIndex">
                                    <div class="grid gap-2 sm:grid-cols-[1fr_8rem_auto]">
                                        <input type="text" :name="`customization_groups[${groupIndex}][options][${optionIndex}][label]`" x-model="option.label" class="input py-2 text-sm" placeholder="Option label">
                                        <input type="number" step="0.01" min="0" :name="`customization_groups[${groupIndex}][options][${optionIndex}][price]`" x-model="option.price" class="input py-2 text-sm" placeholder="Extra ₱">
                                        <button type="button" class="btn-ghost btn-sm text-rose" @click="group.options.splice(optionIndex, 1)">Remove</button>
                                    </div>
                                </template>
                                <button type="button" class="text-xs font-semibold text-rose hover:underline" @click="group.options.push({ label: '', price: 0 })">+ Add option</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="groups.length === 0" class="rounded-xl bg-parchment/50 px-4 py-3 text-xs text-cocoa">No customisation groups. Customers can still leave a note at checkout.</p>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-2xl font-semibold">Photography</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="image" class="label">Main image</label>
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp" class="input py-2 text-sm">
                        <p class="mt-1 text-xs text-cocoa">JPG, PNG, or WebP up to 5 MB.</p>
                    </div>
                    <div>
                        <label for="gallery" class="label">Gallery (up to 6)</label>
                        <input type="file" name="gallery[]" id="gallery" accept="image/jpeg,image/png,image/webp" multiple class="input py-2 text-sm">
                    </div>
                </div>

                @if ($product->images->isNotEmpty())
                    <div class="mt-5 grid grid-cols-3 gap-3 sm:grid-cols-4">
                        @foreach ($product->images as $image)
                            <div class="group relative overflow-hidden rounded-xl">
                                <img src="{{ $image->url() }}" alt="{{ $image->alt }}" class="aspect-square w-full object-cover">
                                <button type="submit" form="delete-image-{{ $image->id }}" class="absolute right-1.5 top-1.5 grid h-7 w-7 place-items-center rounded-full bg-ink/80 text-cream opacity-0 transition group-hover:opacity-100" aria-label="Remove image">
                                    @include('partials.icon', ['name' => 'close', 'class' => 'h-3.5 w-3.5'])
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-5">
            <section class="card p-5 sm:p-6">
                <h2 class="font-display text-xl font-semibold">Publishing</h2>
                <div class="mt-4 space-y-3">
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="mt-0.5 rounded border-ink/20 text-rose focus:ring-rose">
                        <span><span class="font-semibold">Live on storefront</span><span class="mt-0.5 block text-xs text-cocoa">Customers can find and order this cake.</span></span>
                    </label>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="mt-0.5 rounded border-ink/20 text-rose focus:ring-rose">
                        <span><span class="font-semibold">Feature this cake</span><span class="mt-0.5 block text-xs text-cocoa">Highlighted on your storefront and the marketplace.</span></span>
                    </label>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="stock_quantity" class="label">Stock</label>
                        <input type="number" min="0" max="100000" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required class="input">
                    </div>
                    <div>
                        <label for="low_stock_threshold" class="label">Low stock at</label>
                        <input type="number" min="0" max="100000" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" required class="input">
                    </div>
                </div>

                <button type="submit" class="mt-5 btn-primary w-full">{{ $mode === 'create' ? 'Publish cake' : 'Save changes' }}</button>
                <a href="{{ route('dashboard.products.index') }}" class="mt-2 btn-ghost w-full">Cancel</a>
            </section>

            <section class="card-flat p-5 text-sm leading-6 text-cocoa">
                <p class="font-semibold text-ink">A note on stock</p>
                <p class="mt-1">Stock is reduced when a paid order is confirmed. Each cancellation or expiry releases the reserved cakes automatically.</p>
            </section>
        </aside>
    </form>

    @if ($mode === 'edit')
        @foreach ($product->images as $image)
            <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('dashboard.products.images.destroy', [$product, $image]) }}" class="hidden" onsubmit="return confirm('Remove this image?');">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
@endsection

@push('scripts')
    <script>
        function productForm(initialVariants, initialGroups) {
            return {
                variants: initialVariants.map((variant) => ({ name: variant.name || '', price: variant.price ?? 0, sku: variant.sku || '', is_default: !!variant.is_default })),
                groups: initialGroups.map((group) => ({
                    label: group.label || '',
                    required: !!group.required,
                    options: (group.options || []).map((option) => ({ label: option.label || '', price: option.price ?? 0 })),
                })),
                addVariant() {
                    this.variants.push({ name: '', price: 0, sku: '', is_default: this.variants.length === 0 });
                },
                setDefault(index) {
                    this.variants.forEach((variant, i) => { variant.is_default = i === index; });
                },
                addGroup() {
                    this.groups.push({ label: '', required: false, options: [{ label: '', price: 0 }] });
                },
            };
        }
    </script>
@endpush
