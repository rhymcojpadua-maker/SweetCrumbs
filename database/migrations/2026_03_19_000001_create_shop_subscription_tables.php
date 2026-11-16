<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('trialing')->index();
            $table->timestamp('period_ends_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('shop_subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_subscription_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('xendit');
            $table->string('provider_checkout_id')->nullable()->unique();
            $table->string('reference_number', 50)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('PHP');
            $table->string('status', 30)->default('pending')->index();
            $table->text('checkout_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('provider_event_id')->nullable()->unique();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['shop_subscription_id', 'status', 'created_at'], 'subscription_payment_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_subscription_payments');
        Schema::dropIfExists('shop_subscriptions');
    }
};
