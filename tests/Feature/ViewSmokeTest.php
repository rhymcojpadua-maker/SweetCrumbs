<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_public_pages_render(): void
    {
        $shop = Shop::query()->where('status', 'active')->firstOrFail();
        $product = Product::query()->where('shop_id', $shop->id)->firstOrFail();
        $order = Order::query()->firstOrFail();

        $this->get('/')->assertOk();
        $this->get('/cakes')->assertOk();
        $this->get('/cakes/'.$product->slug)->assertOk();
        $this->get('/cakes?category='.$product->category->slug)->assertOk();
        $this->get('/bakeries')->assertOk();
        $this->get('/bakeries/'.$shop->slug)->assertOk();
        $this->get('/faqs')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/terms')->assertOk();
        $this->get('/privacy')->assertOk();
        $this->get('/cart')->assertOk();
        $this->get('/track-order')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/sell-with-cakelle')->assertOk();
        $this->get('/orders/'.$order->order_number.'?token='.$order->public_token)->assertOk();
    }

    public function test_checkout_renders_with_a_basket(): void
    {
        $customer = User::query()->where('email', 'customer@cakelle.ph')->firstOrFail();
        $product = Product::query()->where('is_active', true)->firstOrFail();

        $cart = Cart::create(['user_id' => $customer->id, 'shop_id' => $product->shop_id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $product->defaultVariant()?->id,
            'quantity' => 1,
            'unit_price' => $product->priceFor($product->defaultVariant()),
            'options_hash' => hash('sha256', 'smoke'),
            'customizations' => [],
        ]);

        $this->actingAs($customer)->get('/checkout')->assertOk();
    }

    public function test_shop_dashboard_pages_render(): void
    {
        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();
        $product = Product::query()->where('shop_id', $owner->shop->id)->firstOrFail();
        $order = Order::query()->where('shop_id', $owner->shop->id)->firstOrFail();
        $customer = $owner->shop->customers()->firstOrFail();

        $paths = [
            '/dashboard',
            '/dashboard/orders',
            '/dashboard/orders/'.$order->id,
            '/dashboard/products',
            '/dashboard/products/create',
            '/dashboard/products/'.$product->id.'/edit',
            '/dashboard/categories',
            '/dashboard/inventory',
            '/dashboard/customers',
            '/dashboard/customers/'.$customer->id,
            '/dashboard/payments',
            '/dashboard/reports',
            '/dashboard/storefront',
            '/dashboard/fulfillment',
            '/dashboard/profile',
            '/dashboard/notifications',
        ];

        foreach ($paths as $path) {
            $this->actingAs($owner)->get($path)->assertOk();
        }
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::query()->where('email', 'admin@cakelle.ph')->firstOrFail();
        $shop = Shop::query()->firstOrFail();
        $order = Order::query()->firstOrFail();

        $paths = [
            '/admin',
            '/admin/applications',
            '/admin/shops',
            '/admin/shops/'.$shop->id,
            '/admin/users',
            '/admin/orders',
            '/admin/orders/'.$order->id,
            '/admin/payments',
            '/admin/activity',
            '/admin/settings',
            '/admin/content',
        ];

        foreach ($paths as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }
}
