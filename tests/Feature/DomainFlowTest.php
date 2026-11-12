<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCustomer;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_paymongo_webhook_marks_an_unpaid_order_paid_and_is_idempotent(): void
    {
        config(['services.paymongo.webhook_secret' => 'whsec_test']);

        $payment = Payment::query()->where('status', PaymentStatus::Unpaid->value)->firstOrFail();
        $order = $payment->order;

        $payload = [
            'data' => [
                'id' => 'evt_test_paid_1',
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => 'cs_test_1',
                        'attributes' => [
                            'reference_number' => $payment->reference_number,
                            'payments' => [[
                                'id' => 'pay_test_1',
                                'attributes' => [
                                    'status' => 'paid',
                                    'amount' => (int) round((float) $payment->amount * 100),
                                    'source' => ['type' => 'card'],
                                ],
                            ]],
                        ],
                    ],
                ],
            ],
        ];

        $raw = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$raw, 'whsec_test');

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature},li=",
        ];

        $this->call('POST', '/webhooks/paymongo', [], [], [], $headers, $raw)
            ->assertOk()
            ->assertJson(['result' => 'processed']);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::PendingConfirmation, $order->status);

        $this->call('POST', '/webhooks/paymongo', [], [], [], $headers, $raw)
            ->assertOk()
            ->assertJson(['result' => 'duplicate']);

        $this->assertSame(
            1,
            PaymentTransaction::query()->where('provider_event_id', 'evt_test_paid_1')->count()
        );
    }

    public function test_paymongo_webhook_rejects_an_invalid_signature(): void
    {
        config(['services.paymongo.webhook_secret' => 'whsec_test']);

        $raw = json_encode(['data' => ['id' => 'evt_bad', 'attributes' => ['type' => 'payment.paid']]]);

        $this->call('POST', '/webhooks/paymongo', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => 't='.time().',te=not-a-real-signature',
        ], $raw)->assertUnauthorized();
    }

    public function test_admin_can_approve_a_pending_shop_application(): void
    {
        $admin = User::query()->where('email', 'admin@cakelle.ph')->firstOrFail();
        $shop = Shop::query()->where('status', 'pending')->firstOrFail();

        $this->actingAs($admin)
            ->patch('/admin/shops/'.$shop->id.'/approve')
            ->assertRedirect('/admin/shops/'.$shop->id);

        $shop->refresh();
        $this->assertSame('active', $shop->status->value);
        $this->assertNotNull($shop->approved_at);
        $this->assertTrue($shop->owner->fresh()->is_active);
    }

    public function test_shop_owner_cannot_access_the_admin_area(): void
    {
        $owner = User::query()->where('email', 'owner@sweetlayers.ph')->firstOrFail();

        $this->actingAs($owner)->get('/admin')->assertForbidden();
    }
}
