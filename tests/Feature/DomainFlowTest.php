<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCustomer;
use App\Models\ShopSubscription;
use App\Models\ShopSubscriptionPayment;
use App\Models\User;
use App\Services\OrderStatusService;
use App\Services\PaymentService;
use App\Services\ShopSubscriptionService;
use App\Services\XenditGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DomainFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_shop_owner_cannot_access_another_shops_resources(): void
    {
        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();
        $otherShop = Shop::query()->where('id', '!=', $owner->shop->id)->where('status', 'active')->firstOrFail();

        $otherOrder = Order::query()->where('shop_id', $otherShop->id)->firstOrFail();
        $otherProduct = Product::query()->where('shop_id', $otherShop->id)->firstOrFail();
        $otherCustomer = ShopCustomer::query()->where('shop_id', $otherShop->id)->firstOrFail();

        $this->actingAs($owner)->get('/dashboard/orders/'.$otherOrder->id)->assertNotFound();
        $this->actingAs($owner)->get('/dashboard/products/'.$otherProduct->id.'/edit')->assertForbidden();
        $this->actingAs($owner)->get('/dashboard/customers/'.$otherCustomer->id)->assertNotFound();
    }

    public function test_owner_can_advance_a_paid_order(): void
    {
        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();
        $order = Order::query()
            ->where('shop_id', $owner->shop->id)
            ->where('status', OrderStatus::PendingConfirmation->value)
            ->firstOrFail();

        $this->actingAs($owner)
            ->patch('/dashboard/orders/'.$order->id.'/status', ['status' => OrderStatus::Confirmed->value])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);
    }

    public function test_paid_order_cannot_be_cancelled_from_the_dashboard(): void
    {
        $order = Order::query()
            ->where('status', OrderStatus::PendingConfirmation->value)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->firstOrFail();

        try {
            app(OrderStatusService::class)->transition($order, OrderStatus::Cancelled);
            $this->fail('Expected a validation exception for cancelling a paid order.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame(OrderStatus::PendingConfirmation, $order->fresh()->status);
    }

    public function test_xendit_checkout_creates_and_stores_a_payment_session(): void
    {
        config([
            'services.xendit.secret_key' => 'xnd_test_key',
            'services.xendit.webhook_token' => 'xendit_callback_test',
            'services.xendit.base_url' => 'https://api.xendit.co',
            'app.url' => 'https://sweetcrumbs.test',
        ]);
        Http::fake([
            'api.xendit.co/sessions' => Http::response([
                'payment_session_id' => 'ps_test_1',
                'payment_link_url' => 'https://checkout.xendit.co/web/ps_test_1',
            ]),
        ]);

        $payment = Payment::query()->where('status', PaymentStatus::Unpaid->value)->firstOrFail();
        $order = $payment->order;
        $result = app(PaymentService::class)->startCheckout($order, $payment);

        $this->assertSame('xendit', $result->provider);
        $this->assertSame('ps_test_1', $result->provider_checkout_id);
        $this->assertSame('https://checkout.xendit.co/web/ps_test_1', $result->checkout_url);
        $this->assertSame(PaymentStatus::Pending, $result->status);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.xendit.co/sessions'
            && $request['reference_id'] === $payment->reference_number
            && $request['session_type'] === 'PAY'
            && $request['mode'] === 'PAYMENT_LINK'
            && $request['currency'] === 'PHP'
            && str_starts_with($request['success_return_url'], 'https://sweetcrumbs.test/')
            && str_starts_with($request['cancel_return_url'], 'https://sweetcrumbs.test/')
            && collect($request['items'])->every(fn (array $item) => filled($item['category'])
                && in_array($item['type'], ['PHYSICAL_PRODUCT', 'FEE'], true))
            && collect($request['items'])->contains(fn (array $item) => $item['type'] === 'PHYSICAL_PRODUCT')
            && (float) $request['amount'] === (float) $payment->amount);
    }

    public function test_xendit_is_not_ready_without_both_credentials(): void
    {
        config([
            'services.xendit.secret_key' => 'xnd_test_key',
            'services.xendit.webhook_token' => '',
        ]);

        $this->assertFalse(app(XenditGateway::class)->isConfigured());

        config(['services.xendit.webhook_token' => 'xendit_callback_test']);

        $this->assertTrue(app(XenditGateway::class)->isConfigured());
    }

    public function test_checkout_connection_failure_returns_to_the_order_instead_of_a_server_error(): void
    {
        config([
            'services.xendit.secret_key' => 'xnd_test_key',
            'services.xendit.webhook_token' => 'xendit_callback_test',
            'services.xendit.base_url' => 'https://api.xendit.co',
            'app.url' => 'https://sweetcrumbs.test',
        ]);
        Http::fake(['api.xendit.co/sessions' => Http::failedConnection()]);

        $payment = Payment::query()->where('status', PaymentStatus::Unpaid->value)->firstOrFail();
        $order = $payment->order;

        $this->post('/orders/'.$order->id.'/pay?token='.$order->public_token)
            ->assertRedirect($order->publicUrl())
            ->assertSessionHas('error');

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    public function test_provider_status_sync_handles_a_network_failure(): void
    {
        config([
            'services.xendit.secret_key' => 'xnd_test_key',
            'services.xendit.webhook_token' => 'xendit_callback_test',
            'services.xendit.base_url' => 'https://api.xendit.co',
        ]);
        Http::fake(['api.xendit.co/sessions/ps_test_sync' => Http::failedConnection()]);

        $payment = Payment::query()->where('status', PaymentStatus::Unpaid->value)->firstOrFail();
        $payment->update([
            'provider' => 'xendit',
            'provider_checkout_id' => 'ps_test_sync',
            'status' => PaymentStatus::Pending,
        ]);

        $result = app(PaymentService::class)->syncFromProvider($payment);

        $this->assertSame(PaymentStatus::Pending, $result->status);
    }

    public function test_xendit_webhook_marks_an_unpaid_order_paid_and_is_idempotent(): void
    {
        config(['services.xendit.webhook_token' => 'xendit_callback_test']);

        $payment = Payment::query()->where('status', PaymentStatus::Unpaid->value)->firstOrFail();
        $order = $payment->order;
        $payment->update([
            'provider' => 'xendit',
            'provider_checkout_id' => 'ps_test_paid',
            'status' => PaymentStatus::Pending,
        ]);

        $payload = [
            'id' => 'evt_test_paid_1',
            'event' => 'payment_session.completed',
            'data' => [
                'payment_session_id' => 'ps_test_paid',
                'reference_id' => $payment->reference_number,
                'status' => 'COMPLETED',
                'amount' => (float) $payment->amount,
                'payment_id' => 'pay_test_1',
            ],
        ];

        $raw = json_encode($payload);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_TOKEN' => 'xendit_callback_test',
        ];

        $this->call('POST', '/webhooks/xendit', [], [], [], $headers, $raw)
            ->assertOk()
            ->assertJson(['result' => 'processed']);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::PendingConfirmation, $order->status);

        $this->call('POST', '/webhooks/xendit', [], [], [], $headers, $raw)
            ->assertOk()
            ->assertJson(['result' => 'duplicate']);

        $this->assertSame(
            1,
            PaymentTransaction::query()->where('provider_event_id', 'evt_test_paid_1')->count()
        );
    }

    public function test_xendit_webhook_rejects_an_invalid_callback_token(): void
    {
        config(['services.xendit.webhook_token' => 'xendit_callback_test']);

        $raw = json_encode(['event' => 'payment_session.completed', 'data' => []]);

        $this->call('POST', '/webhooks/xendit', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_TOKEN' => 'not-a-real-token',
        ], $raw)->assertUnauthorized();
    }

    public function test_xendit_webhook_does_not_mark_a_payment_paid_when_amount_mismatches(): void
    {
        config(['services.xendit.webhook_token' => 'xendit_callback_test']);

        $payment = Payment::query()->where('status', PaymentStatus::Unpaid->value)->firstOrFail();
        $order = $payment->order;
        $payment->update([
            'provider' => 'xendit',
            'provider_checkout_id' => 'ps_test_mismatch',
            'status' => PaymentStatus::Pending,
        ]);

        $payload = [
            'event' => 'payment_session.completed',
            'data' => [
                'payment_session_id' => 'ps_test_mismatch',
                'reference_id' => $payment->reference_number,
                'status' => 'COMPLETED',
                'amount' => (float) $payment->amount - 1,
            ],
        ];

        $this->call('POST', '/webhooks/xendit', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_TOKEN' => 'xendit_callback_test',
        ], json_encode($payload))
            ->assertOk()
            ->assertJson(['result' => 'ignored', 'reason' => 'Amount mismatch.']);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->fresh()->payment_status);
    }

    public function test_xendit_expired_session_cancels_the_unpaid_order(): void
    {
        $payment = Payment::query()->where('status', PaymentStatus::Unpaid->value)->firstOrFail();
        $order = $payment->order;
        $payment->update([
            'provider' => 'xendit',
            'provider_checkout_id' => 'ps_test_expired',
            'status' => PaymentStatus::Pending,
        ]);

        $result = app(PaymentService::class)->processWebhook([
            'id' => 'evt_test_expired',
            'event' => 'payment_session.expired',
            'data' => [
                'payment_session_id' => 'ps_test_expired',
                'reference_id' => $payment->reference_number,
                'status' => 'EXPIRED',
            ],
        ]);

        $this->assertSame('processed', $result['result']);
        $this->assertSame(PaymentStatus::Expired, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Expired, $order->fresh()->payment_status);
    }

    public function test_subscription_is_unpaid_and_accessible_until_admin_configures_a_price(): void
    {
        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();

        $this->actingAs($owner)->get('/dashboard')->assertOk();
        $this->actingAs($owner)->get('/dashboard/subscription')
            ->assertOk()
            ->assertSee('Subscriptions are not enabled yet')
            ->assertSee('You will not be charged');
        $this->actingAs($owner)->post('/dashboard/subscription/checkout')
            ->assertRedirect(route('dashboard.subscription.show'))
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('shop_subscription_payments', 0);
    }

    public function test_admin_configuring_a_price_grants_existing_active_bakeries_thirty_days(): void
    {
        $admin = User::query()->where('email', 'admin@cakelle.ph')->firstOrFail();
        $shop = Shop::query()->where('status', 'active')->firstOrFail();

        $this->actingAs($admin)->patch('/admin/settings', [
            'platform_name' => 'SweetCrumbs',
            'support_email' => 'hello@sweetcrumbs.ph',
            'support_phone' => '+63 917 000 0000',
            'bakery_subscription_monthly_price' => '299.00',
        ])->assertRedirect();

        $subscription = $shop->subscription()->firstOrFail();
        $this->assertSame('trialing', $subscription->status);
        $this->assertTrue($subscription->period_ends_at->between(now()->addDays(29), now()->addDays(31)));
        $this->assertSame(299.0, app(ShopSubscriptionService::class)->monthlyPrice());
    }

    public function test_subscription_checkout_creates_one_xendit_session_and_reuses_pending_checkout(): void
    {
        config([
            'services.xendit.secret_key' => 'xnd_test_key',
            'services.xendit.webhook_token' => 'xendit_callback_test',
            'services.xendit.base_url' => 'https://api.xendit.co',
            'app.url' => 'https://sweetcrumbs.test',
        ]);
        PlatformSetting::set('bakery_subscription_monthly_price', 299);
        Http::fake([
            'api.xendit.co/sessions' => Http::response([
                'payment_session_id' => 'ps_subscription_1',
                'payment_link_url' => 'https://checkout.xendit.co/web/ps_subscription_1',
            ]),
        ]);

        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();
        $shop = $owner->shop;
        $subscription = ShopSubscription::create([
            'shop_id' => $shop->id,
            'status' => 'expired',
        ]);

        $this->actingAs($owner)->post('/dashboard/subscription/checkout')
            ->assertRedirect('https://checkout.xendit.co/web/ps_subscription_1');
        PlatformSetting::set('bakery_subscription_monthly_price', 399);
        $this->actingAs($owner)->post('/dashboard/subscription/checkout')
            ->assertRedirect('https://checkout.xendit.co/web/ps_subscription_1');

        $payment = ShopSubscriptionPayment::query()->where('shop_subscription_id', $subscription->id)->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame(299.0, (float) $payment->amount);
        $this->assertSame('ps_subscription_1', $payment->provider_checkout_id);
        $this->assertSame(1, ShopSubscriptionPayment::query()->count());
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.xendit.co/sessions'
            && $request['reference_id'] === $payment->reference_number
            && $request['amount'] === 299.0
            && $request['currency'] === 'PHP'
            && data_get($request->data(), 'metadata.purpose') === 'shop_subscription'
            && str_starts_with($request['success_return_url'], 'https://sweetcrumbs.test/'));
        Http::assertSentCount(1);
    }

    public function test_subscription_checkout_failure_does_not_grant_access(): void
    {
        config([
            'services.xendit.secret_key' => 'xnd_test_key',
            'services.xendit.webhook_token' => 'xendit_callback_test',
            'services.xendit.base_url' => 'https://api.xendit.co',
            'app.url' => 'https://sweetcrumbs.test',
        ]);
        PlatformSetting::set('bakery_subscription_monthly_price', 299);
        Http::fake(['api.xendit.co/sessions' => Http::failedConnection()]);

        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();
        $subscription = ShopSubscription::create([
            'shop_id' => $owner->shop->id,
            'status' => 'expired',
        ]);

        $this->actingAs($owner)->post('/dashboard/subscription/checkout')
            ->assertRedirect(route('dashboard.subscription.show'))
            ->assertSessionHas('error');

        $payment = ShopSubscriptionPayment::query()->where('shop_subscription_id', $subscription->id)->firstOrFail();
        $this->assertSame('failed', $payment->status);
        $this->assertFalse($subscription->fresh()->hasAccess());
    }

    public function test_subscription_webhook_activates_a_month_and_is_idempotent(): void
    {
        config(['services.xendit.webhook_token' => 'xendit_callback_test']);
        PlatformSetting::set('bakery_subscription_monthly_price', 299);

        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();
        $subscription = ShopSubscription::create([
            'shop_id' => $owner->shop->id,
            'status' => 'expired',
        ]);
        $payment = ShopSubscriptionPayment::create([
            'shop_subscription_id' => $subscription->id,
            'provider' => 'xendit',
            'provider_checkout_id' => 'ps_subscription_paid',
            'reference_number' => 'SC-SUB-TEST-PAID',
            'amount' => 299,
            'currency' => 'PHP',
            'status' => 'pending',
        ]);
        $payload = [
            'id' => 'evt_subscription_paid',
            'event' => 'payment_session.completed',
            'data' => [
                'payment_session_id' => 'ps_subscription_paid',
                'reference_id' => $payment->reference_number,
                'status' => 'COMPLETED',
                'amount' => 299,
                'metadata' => ['purpose' => 'shop_subscription'],
            ],
        ];
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_TOKEN' => 'xendit_callback_test',
        ];

        $this->call('POST', '/webhooks/xendit', [], [], [], $headers, json_encode($payload))
            ->assertOk()
            ->assertJson(['result' => 'processed']);

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->period_ends_at->between(now()->addDays(29), now()->addDays(32)));
        $this->assertSame('paid', $payment->fresh()->status);

        $this->call('POST', '/webhooks/xendit', [], [], [], $headers, json_encode($payload))
            ->assertOk()
            ->assertJson(['result' => 'duplicate']);
        $this->assertSame(1, ShopSubscriptionPayment::query()->where('provider_event_id', 'evt_subscription_paid')->count());
    }

    public function test_subscription_webhook_does_not_activate_when_amount_mismatches(): void
    {
        config(['services.xendit.webhook_token' => 'xendit_callback_test']);
        PlatformSetting::set('bakery_subscription_monthly_price', 299);

        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();
        $subscription = ShopSubscription::create([
            'shop_id' => $owner->shop->id,
            'status' => 'expired',
        ]);
        $payment = ShopSubscriptionPayment::create([
            'shop_subscription_id' => $subscription->id,
            'provider' => 'xendit',
            'provider_checkout_id' => 'ps_subscription_mismatch',
            'reference_number' => 'SC-SUB-TEST-MISMATCH',
            'amount' => 299,
            'currency' => 'PHP',
            'status' => 'pending',
        ]);

        $this->call('POST', '/webhooks/xendit', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_TOKEN' => 'xendit_callback_test',
        ], json_encode([
            'event' => 'payment_session.completed',
            'data' => [
                'payment_session_id' => 'ps_subscription_mismatch',
                'reference_id' => $payment->reference_number,
                'status' => 'COMPLETED',
                'amount' => 298,
                'metadata' => ['purpose' => 'shop_subscription'],
            ],
        ]))->assertOk()->assertJson(['result' => 'ignored', 'reason' => 'Subscription amount mismatch.']);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('expired', $subscription->fresh()->status);
    }

    public function test_expired_renewal_checkout_does_not_end_current_access(): void
    {
        config(['services.xendit.webhook_token' => 'xendit_callback_test']);
        PlatformSetting::set('bakery_subscription_monthly_price', 299);

        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();
        $periodEnd = now()->addDays(10);
        $subscription = ShopSubscription::create([
            'shop_id' => $owner->shop->id,
            'status' => 'active',
            'period_ends_at' => $periodEnd,
        ]);
        $payment = ShopSubscriptionPayment::create([
            'shop_subscription_id' => $subscription->id,
            'provider' => 'xendit',
            'provider_checkout_id' => 'ps_subscription_expired',
            'reference_number' => 'SC-SUB-TEST-EXPIRED',
            'amount' => 299,
            'currency' => 'PHP',
            'status' => 'pending',
        ]);

        $this->call('POST', '/webhooks/xendit', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_TOKEN' => 'xendit_callback_test',
        ], json_encode([
            'event' => 'payment_session.expired',
            'data' => [
                'payment_session_id' => 'ps_subscription_expired',
                'reference_id' => $payment->reference_number,
                'status' => 'EXPIRED',
                'metadata' => ['purpose' => 'shop_subscription'],
            ],
        ]))->assertOk()->assertJson(['result' => 'processed']);

        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertTrue($subscription->fresh()->period_ends_at->isFuture());
    }

    public function test_expired_subscription_blocks_seller_and_storefront_until_paid(): void
    {
        PlatformSetting::set('bakery_subscription_monthly_price', 299);

        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();
        $shop = $owner->shop;
        ShopSubscription::create([
            'shop_id' => $shop->id,
            'status' => 'active',
            'period_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)->get('/dashboard')
            ->assertRedirect(route('dashboard.subscription.show'));
        $this->actingAs($owner)->get('/dashboard/subscription')->assertOk();
        $this->get('/bakeries/'.$shop->slug)->assertNotFound();
        $this->assertSame(0, Shop::query()->active()->whereKey($shop->id)->count());
    }

    public function test_admin_can_approve_a_pending_shop_application(): void
    {
        $admin = User::query()->where('email', 'admin@cakelle.ph')->firstOrFail();
        $shop = Shop::query()->where('status', 'pending')->firstOrFail();
        PlatformSetting::set('bakery_subscription_monthly_price', 299);

        $this->actingAs($admin)
            ->patch('/admin/shops/'.$shop->id.'/approve')
            ->assertRedirect('/admin/shops/'.$shop->id);

        $shop->refresh();
        $this->assertSame('active', $shop->status->value);
        $this->assertNotNull($shop->approved_at);
        $this->assertTrue($shop->owner->fresh()->is_active);
        $this->assertSame('trialing', $shop->subscription()->firstOrFail()->status);
    }

    public function test_shop_owner_cannot_access_the_admin_area(): void
    {
        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();

        $this->actingAs($owner)->get('/admin')->assertForbidden();
    }
}
