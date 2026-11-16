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

    public function test_asset_urls_use_the_forwarded_https_scheme(): void
    {
        $response = $this->withServerVariables(['HTTP_X_FORWARDED_PROTO' => 'https'])
            ->get('/')
            ->assertOk();

        $this->assertMatchesRegularExpression(
            '#href="https://localhost(?::\\d+)?/build/assets/app-[^"]+\\.css"#',
            $response->getContent(),
        );
    }

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
        $this->get('/login')
            ->assertOk()
            ->assertSee('Cakes with a story')
            ->assertSee('Sign in to SweetCrumbs.')
            ->assertSee('sweetcrumbs-logo-dark.png')
            ->assertSee('sweetcrumbs-logo.png')
            ->assertSee('SweetCrumbs')
            ->assertSee('Back to homepage')
            ->assertSee('Forgot password?');
        $this->get('/register')
            ->assertOk()
            ->assertSee('Join SweetCrumbs')
            ->assertSee('Create your customer account.')
            ->assertSee('sweetcrumbs-logo-dark.png')
            ->assertSee('sweetcrumbs-logo.png')
            ->assertSee('SweetCrumbs')
            ->assertSee('Back to homepage');
        $this->get('/sell-with-cakelle')->assertOk();
        $this->get('/orders/'.$order->order_number.'?token='.$order->public_token)->assertOk();
    }

    public function test_shop_cover_urls_are_unique_when_no_cover_is_uploaded(): void
    {
        $shops = Shop::query()->where('status', 'active')->limit(3)->get();

        $this->assertGreaterThanOrEqual(3, $shops->count());

        $shops->each(fn (Shop $shop) => $shop->update(['cover_path' => null]));

        $urls = $shops->map(fn (Shop $shop) => $shop->coverUrl())->unique()->values();

        $this->assertCount(3, $urls);
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

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('sweetcrumbs-logo-dark.png')
            ->assertSee('SweetCrumbs');

        foreach ($paths as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }
}
