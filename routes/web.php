<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ShopRegistrationController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\CustomerController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\FulfillmentController;
use App\Http\Controllers\Dashboard\InventoryController;
use App\Http\Controllers\Dashboard\NotificationController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\Dashboard\ShopOrderController;
use App\Http\Controllers\Dashboard\ShopPaymentController;
use App\Http\Controllers\Dashboard\ShopProductController;
use App\Http\Controllers\Dashboard\StorefrontController;
use App\Http\Controllers\Dashboard\SubscriptionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/cakes', [MarketplaceController::class, 'index'])->name('marketplace');
Route::get('/cakes/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/bakeries', [ShopController::class, 'index'])->name('shops.index');
Route::get('/bakeries/{shop:slug}', [ShopController::class, 'show'])->name('shops.show');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/faqs', [PageController::class, 'faqs'])->name('faqs');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/items', [CartController::class, 'add'])->name('add');
    Route::patch('/items/{item}', [CartController::class, 'update'])->name('update');
    Route::delete('/items/{item}', [CartController::class, 'remove'])->name('remove');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
});

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');

Route::get('/track-order', [OrderController::class, 'trackForm'])->name('orders.track.form');
Route::post('/track-order', [OrderController::class, 'track'])->middleware('throttle:tracking')->name('orders.track');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
Route::post('/orders/{order}/pay', [PaymentController::class, 'resume'])->name('payments.resume');
Route::get('/payments/return/{order}', [PaymentController::class, 'return'])->name('payment.return');

Route::post('/webhooks/xendit', XenditWebhookController::class)
    ->middleware('throttle:webhooks')
    ->name('webhooks.xendit');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/sell-with-cakelle', [ShopRegistrationController::class, 'create'])->name('shop.register');
    Route::post('/sell-with-cakelle', [ShopRegistrationController::class, 'store'])->name('shop.register.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'account.active', 'role:shop_owner'])->group(function () {
    Route::get('/dashboard/pending', [DashboardController::class, 'pending'])->name('dashboard.pending');
    Route::get('/dashboard/suspended', [DashboardController::class, 'suspended'])->name('dashboard.suspended');

    Route::prefix('dashboard')->name('dashboard.')->middleware('shop.operational')->group(function () {
        Route::get('/subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
        Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])
            ->middleware('throttle:5,1')
            ->name('subscription.checkout');
    });

    Route::prefix('dashboard')->name('dashboard.')->middleware(['shop.operational', 'shop.subscription'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        Route::get('/orders', [ShopOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [ShopOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [ShopOrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('/orders/{order}/cash-payment', [ShopOrderController::class, 'markCashReceived'])->name('orders.cash-payment');

        Route::resource('products', ShopProductController::class)->except('show');
        Route::delete('/products/{product}/images/{image}', [ShopProductController::class, 'destroyImage'])->name('products.images.destroy');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/{product}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

        Route::get('/payments', [ShopPaymentController::class, 'index'])->name('payments.index');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('/storefront', [StorefrontController::class, 'edit'])->name('storefront.edit');
        Route::patch('/storefront', [StorefrontController::class, 'update'])->name('storefront.update');

        Route::get('/fulfillment', [FulfillmentController::class, 'edit'])->name('fulfillment.edit');
        Route::patch('/fulfillment', [FulfillmentController::class, 'update'])->name('fulfillment.update');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'account.active', 'role:super_admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/applications', [AdminShopController::class, 'applications'])->name('applications.index');
    Route::get('/shops', [AdminShopController::class, 'index'])->name('shops.index');
    Route::get('/shops/{shop}', [AdminShopController::class, 'show'])->name('shops.show');
    Route::patch('/shops/{shop}/approve', [AdminShopController::class, 'approve'])->name('shops.approve');
    Route::patch('/shops/{shop}/reject', [AdminShopController::class, 'reject'])->name('shops.reject');
    Route::patch('/shops/{shop}/suspend', [AdminShopController::class, 'suspend'])->name('shops.suspend');
    Route::patch('/shops/{shop}/activate', [AdminShopController::class, 'activate'])->name('shops.activate');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');

    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');

    Route::get('/settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

    Route::get('/content', [ContentController::class, 'index'])->name('content.index');
});
