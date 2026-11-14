<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\OrderStatusService;
use Illuminate\Console\Command;

class ExpirePendingOrders extends Command
{
    protected $signature = 'cakelle:expire-orders';

    protected $description = 'Cancel orders whose Xendit payment window has expired and release stock.';

    public function handle(OrderStatusService $statusService): int
    {
        $window = (int) config('cakelle.order_payment_window_minutes', 45);
        $cutoff = now()->subMinutes($window);

        $orders = Order::query()
            ->with('payment')
            ->where('status', OrderStatus::PendingPayment->value)
            ->where('payment_status', '!=', PaymentStatus::Paid->value)
            ->where('placed_at', '<=', $cutoff)
            ->get();

        $expired = 0;

        foreach ($orders as $order) {
            if ($order->isPaid()) {
                continue;
            }

            try {
                $statusService->transition($order, OrderStatus::Cancelled, null, 'Payment window expired');
            } catch (\Throwable $exception) {
                $this->warn("Could not expire {$order->order_number}: {$exception->getMessage()}");

                continue;
            }

            $payment = $order->payment;
            if ($payment && $payment->status !== PaymentStatus::Paid) {
                $payment->update([
                    'status' => PaymentStatus::Expired,
                    'failed_at' => now(),
                    'failure_reason' => 'Payment window expired.',
                ]);

                PaymentTransaction::create([
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'provider' => 'xendit',
                    'type' => 'checkout_expired',
                    'status' => 'expired',
                    'amount' => $payment->amount,
                ]);
            }

            $expired++;
        }

        $this->info("Expired {$expired} unpaid order(s).");

        return self::SUCCESS;
    }
}
