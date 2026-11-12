<?php

namespace App\Http\Controllers;

use App\Enums\FulfillmentType;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use App\Services\PayMongoGateway;
use App\Services\ScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ScheduleService $scheduleService,
        private readonly CheckoutService $checkoutService,
        private readonly PaymentService $paymentService,
        private readonly PayMongoGateway $gateway,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->cartService->currentCart($request, false);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Add a cake to your basket before checking out.');
        }

        $cart->loadMissing(['items.product.shop.settings', 'items.variant', 'shop.settings']);
        $shop = $cart->shop;
        $settings = $shop?->settings;
        $leadHours = (int) $cart->items->max(fn ($item) => (int) $item->product->preparation_hours);

        $datesByType = [];
        foreach (FulfillmentType::cases() as $type) {
            $datesByType[$type->value] = collect($this->scheduleService->availableDates($shop, $type, $leadHours))
                ->take(21)
                ->values()
                ->all();
        }

        $defaultType = $settings?->pickup_enabled ? FulfillmentType::Pickup->value : FulfillmentType::Delivery->value;

        return view('public.checkout', [
            'cart' => $cart,
            'shop' => $shop,
            'settings' => $settings,
            'datesByType' => $datesByType,
            'defaultType' => $defaultType,
            'paymongoConfigured' => $this->gateway->isConfigured(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:190'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'fulfillment_type' => ['required', 'in:pickup,delivery'],
            'delivery_address' => ['nullable', 'required_if:fulfillment_type,delivery', 'string', 'max:500'],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
        ]);

        $order = $this->checkoutService->placeOrder($request, $data);

        if (! $this->gateway->isConfigured()) {
            return redirect()->route('orders.show', ['order' => $order->order_number, 'token' => $order->public_token])
                ->with('warning', 'Your order has been saved. Online payment is not configured yet, so it is awaiting payment confirmation.');
        }

        try {
            $this->paymentService->startCheckout($order, $order->payment);

            return redirect()->away($order->payment->fresh()->checkout_url);
        } catch (RuntimeException $exception) {
            return redirect()->route('orders.show', ['order' => $order->order_number, 'token' => $order->public_token])
                ->with('error', 'We could not open PayMongo checkout: '.$exception->getMessage().' You can retry payment from this page.');
        }
    }
}
