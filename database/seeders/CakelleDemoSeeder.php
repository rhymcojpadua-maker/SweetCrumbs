<?php

namespace Database\Seeders;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShopStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\ShopCustomer;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CakelleDemoSeeder extends Seeder
{
    private array $images = [
        'photo-1578985545062-69928b1d9587',
        'photo-1565958011703-44f9829ba187',
        'photo-1621303837174-89787a7d4729',
        'photo-1535141192574-5d4897c12636',
        'photo-1519869325930-281384150729',
        'photo-1464349095431-e9a21285b5f3',
        'photo-1571115177098-24ec42ed204d',
        'photo-1486427944299-d1955d23e34d',
        'photo-1557925923-cd4648e211a0',
        'photo-1563729784474-d77dbb933a9e',
        'photo-1586985289688-ca3cf47d3e6e',
        'photo-1614707267537-b85aaf00c4b7',
        'photo-1606890737304-57a1ca8a5b62',
        'photo-1606313564200-e75d5e30476c',
        'photo-1627834377411-8da5f4f09de8',
        'photo-1599785209707-a456fc1337bb',
        'photo-1574085733277-851d9d856a3a',
        'photo-1558301211-0d8c8ddee6ec',
        'photo-1464305795204-6f5bbfc7fb81',
        'photo-1509440159596-0249088772ff',
        'photo-1568051243851-f9b136146e97',
        'photo-1607478900766-efe13248b125',
    ];

    private int $imageIndex = 0;

    public function run(): void
    {
        $password = 'Password123!';

        User::updateOrCreate(
            ['email' => 'admin@cakelle.ph'],
            [
                'name' => 'SweetCrumbs Administrator',
                'phone' => '09170000001',
                'role' => UserRole::SuperAdmin,
                'password' => $password,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $customerSeeds = [
            ['name' => 'Mika Reyes', 'email' => 'customer@cakelle.ph', 'phone' => '09171000001'],
            ['name' => 'Ana Villanueva', 'email' => 'ana@example.com', 'phone' => '09171000002'],
            ['name' => 'Miguel Torres', 'email' => 'miguel@example.com', 'phone' => '09171000003'],
            ['name' => 'Sofia Lim', 'email' => 'sofia@example.com', 'phone' => '09171000004'],
        ];

        $customers = collect($customerSeeds)->map(function ($data) use ($password) {
            return User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'role' => UserRole::Customer,
                    'password' => $password,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        });

        $shops = [
            [
                'owner' => ['name' => 'Maria Santos', 'email' => 'owner@sweetlayers.ph', 'phone' => '09172000001'],
                'shop' => [
                    'name' => 'Sweet Layers Manila',
                    'slug' => 'sweet-layers-manila',
                    'tagline' => 'Heirloom cakes, layered with intention.',
                    'description' => 'Sweet Layers Manila is a small family studio in Quezon City creating celebration cakes with French buttercream, single-origin chocolate, and seasonal Philippine fruit. Every cake is baked to order and finished by hand.',
                    'email' => 'hello@sweetlayers.ph',
                    'phone' => '09172000001',
                    'address' => '18 Sct. Borromeo Street, Diliman',
                    'city' => 'Quezon City',
                    'province' => 'Metro Manila',
                    'postal_code' => '1103',
                    'status' => ShopStatus::Active,
                    'is_featured' => true,
                    'approved_at' => now()->subMonths(8),
                ],
                'products' => [
                    ['name' => 'Midnight Chocolate Truffle', 'category' => 'birthday', 'price' => 1850, 'hours' => 24, 'stock' => 18, 'servings' => '8–10 guests', 'featured' => true, 'description' => 'Dark chocolate buttermilk layers, silky 70% cacao ganache, and a whisper of sea salt. Our most-loved cake for birthdays that deserve a little drama.', 'ingredients' => 'Belgian dark chocolate, cultured butter, free-range eggs, buttermilk, muscovado sugar, sea salt', 'allergens' => 'Contains dairy, eggs, wheat. May contain traces of nuts.'],
                    ['name' => 'Strawberry Cloud Cake', 'category' => 'birthday', 'price' => 1650, 'hours' => 24, 'stock' => 14, 'servings' => '8–10 guests', 'featured' => true, 'description' => 'Airy chiffon layers, strawberry compote, and mascarpone cream folded with vanilla bean. Finished with fresh strawberries and edible flowers.', 'ingredients' => 'Fresh strawberries, mascarpone, cake flour, eggs, vanilla bean, cream', 'allergens' => 'Contains dairy, eggs, wheat.'],
                    ['name' => 'Ivory Rose Wedding Cake', 'category' => 'wedding', 'price' => 6500, 'hours' => 72, 'stock' => 5, 'servings' => '40–50 guests', 'featured' => true, 'description' => 'A three-tier ivory buttercream cake with hand-piped roses and a delicate almond-vanilla crumb. Designed for intimate garden weddings.', 'ingredients' => 'Almond flour, vanilla bean, Swiss buttercream, eggs, butter', 'allergens' => 'Contains dairy, eggs, wheat, almonds.'],
                    ['name' => 'Ube Macapuno Celebration Cake', 'category' => 'custom-cakes', 'price' => 1750, 'hours' => 24, 'stock' => 12, 'servings' => '8–10 guests', 'featured' => false, 'description' => 'Purple yam sponge with macapuno coconut ribbons and cream cheese frosting. A modern love letter to a Filipino classic.', 'ingredients' => 'Ube halaya, macapuno, cream cheese, cake flour, eggs, butter', 'allergens' => 'Contains dairy, eggs, wheat, coconut.'],
                    ['name' => 'Vanilla Bean Cupcake Box', 'category' => 'cupcakes', 'price' => 890, 'hours' => 12, 'stock' => 30, 'servings' => 'Box of 6', 'featured' => false, 'description' => 'Six soft vanilla bean cupcakes with vanilla mascarpone frosting and a tiny sugar pearl. Perfect for office celebrations.', 'ingredients' => 'Vanilla bean, cake flour, butter, eggs, mascarpone', 'allergens' => 'Contains dairy, eggs, wheat.'],
                    ['name' => 'Salted Caramel Drip Cake', 'category' => 'birthday', 'price' => 1950, 'hours' => 24, 'stock' => 10, 'servings' => '10–12 guests', 'featured' => true, 'description' => 'Brown butter sponge, burnt caramel buttercream, and a slow drip of salted caramel. Finished with chocolate shards and flaky salt.', 'ingredients' => 'Brown butter, muscovado caramel, cream, dark chocolate, eggs, flour', 'allergens' => 'Contains dairy, eggs, wheat.'],
                ],
            ],
            [
                'owner' => ['name' => 'Bianca Cruz', 'email' => 'owner@butterandbloom.ph', 'phone' => '09172000002'],
                'shop' => [
                    'name' => 'Butter & Bloom',
                    'slug' => 'butter-and-bloom',
                    'tagline' => 'Modern buttercream, garden-inspired.',
                    'description' => 'Butter & Bloom is a Makati cake atelier known for sculptural buttercream, edible florals, and balanced flavours. We work closely with couples and families to design cakes that feel like them.',
                    'email' => 'studio@butterandbloom.ph',
                    'phone' => '09172000002',
                    'address' => '27 Chino Roces Avenue, Poblacion',
                    'city' => 'Makati',
                    'province' => 'Metro Manila',
                    'postal_code' => '1210',
                    'status' => ShopStatus::Active,
                    'is_featured' => true,
                    'approved_at' => now()->subMonths(5),
                ],
                'products' => [
                    ['name' => 'Pistachio Raspberry Bloom', 'category' => 'custom-cakes', 'price' => 2850, 'hours' => 36, 'stock' => 8, 'servings' => '10–12 guests', 'featured' => true, 'description' => 'Pistachio sponge, raspberry compote, and mascarpone buttercream with a crown of edible blooms. Nutty, bright, and quietly romantic.', 'ingredients' => 'Pistachio, raspberries, mascarpone, butter, eggs, flour', 'allergens' => 'Contains dairy, eggs, wheat, pistachio.'],
                    ['name' => 'Buttercream Garden Cake', 'category' => 'custom-cakes', 'price' => 2450, 'hours' => 36, 'stock' => 9, 'servings' => '10–12 guests', 'featured' => true, 'description' => 'A garden of hand-piped buttercream blooms over vanilla bean cake and strawberry preserve. Choose your palette at checkout.', 'ingredients' => 'Vanilla bean, strawberries, Swiss buttercream, eggs, flour', 'allergens' => 'Contains dairy, eggs, wheat.'],
                    ['name' => 'Lemon Elderflower Layer', 'category' => 'birthday', 'price' => 1850, 'hours' => 24, 'stock' => 11, 'servings' => '8–10 guests', 'featured' => false, 'description' => 'Sunny lemon curd, elderflower syrup, and whipped crème fraîche between tender lemon layers.', 'ingredients' => 'Lemons, elderflower, crème fraîche, butter, eggs, flour', 'allergens' => 'Contains dairy, eggs, wheat.'],
                    ['name' => 'Mini Celebration Cupcakes', 'category' => 'cupcakes', 'price' => 950, 'hours' => 12, 'stock' => 26, 'servings' => 'Box of 6', 'featured' => false, 'description' => 'Six mini cupcakes in vanilla, chocolate, and raspberry, each finished with a different buttercream swirl.', 'ingredients' => 'Butter, eggs, flour, cocoa, raspberry, vanilla', 'allergens' => 'Contains dairy, eggs, wheat.'],
                    ['name' => 'Chocolate Hazelnut Crunch', 'category' => 'birthday', 'price' => 2150, 'hours' => 24, 'stock' => 12, 'servings' => '10–12 guests', 'featured' => true, 'description' => 'Hazelnut praline, chocolate sponge, and silky milk chocolate ganache with a crunchy feuilletine layer.', 'ingredients' => 'Hazelnut praline, milk chocolate, feuilletine, butter, eggs, flour', 'allergens' => 'Contains dairy, eggs, wheat, hazelnut.'],
                    ['name' => 'Tiered Buttercream Wedding Cake', 'category' => 'wedding', 'price' => 7800, 'hours' => 72, 'stock' => 4, 'servings' => '60–80 guests', 'featured' => false, 'description' => 'Three tiers of almond cake, champagne syrup, and vanilla buttercream, finished with a minimal floral arrangement.', 'ingredients' => 'Almond flour, champagne syrup, vanilla buttercream, eggs, butter', 'allergens' => 'Contains dairy, eggs, wheat, almonds.'],
                ],
            ],
            [
                'owner' => ['name' => 'Paolo Reyes', 'email' => 'owner@littleoven.ph', 'phone' => '09172000003'],
                'shop' => [
                    'name' => 'The Little Oven',
                    'slug' => 'the-little-oven',
                    'tagline' => 'Cebu comfort, baked slow.',
                    'description' => 'The Little Oven is a neighbourhood bakery in Cebu City baking familiar flavours with care. From mango chiffon to red velvet, our cakes are made for sharing around the table.',
                    'email' => 'hello@littleoven.ph',
                    'phone' => '09172000003',
                    'address' => '12 Escario Street, Kamputhaw',
                    'city' => 'Cebu City',
                    'province' => 'Cebu',
                    'postal_code' => '6000',
                    'status' => ShopStatus::Active,
                    'is_featured' => true,
                    'approved_at' => now()->subMonths(11),
                ],
                'products' => [
                    ['name' => 'Cebu Mango Chiffon', 'category' => 'birthday', 'price' => 1450, 'hours' => 24, 'stock' => 16, 'servings' => '8–10 guests', 'featured' => true, 'description' => 'Cloud-soft chiffon, whipped cream, and ripe Cebu mangoes layered generously. A sunny cake for any day.', 'ingredients' => 'Cebu mangoes, cake flour, eggs, cream, sugar', 'allergens' => 'Contains dairy, eggs, wheat.'],
                    ['name' => 'Classic Red Velvet', 'category' => 'birthday', 'price' => 1550, 'hours' => 24, 'stock' => 15, 'servings' => '8–10 guests', 'featured' => false, 'description' => 'Deep red cocoa layers with tangy cream cheese frosting and a fine velvet crumb.', 'ingredients' => 'Cocoa, buttermilk, cream cheese, butter, eggs, flour', 'allergens' => 'Contains dairy, eggs, wheat.'],
                    ['name' => 'Calamansi Drizzle Loaf', 'category' => 'custom-cakes', 'price' => 780, 'hours' => 12, 'stock' => 24, 'servings' => 'Serves 6', 'featured' => false, 'description' => 'A tender calamansi loaf soaked in citrus syrup and finished with a crackly sugar glaze.', 'ingredients' => 'Calamansi, butter, eggs, flour, sugar', 'allergens' => 'Contains dairy, eggs, wheat.'],
                    ['name' => 'Chocolate Fudge Birthday Cake', 'category' => 'birthday', 'price' => 1690, 'hours' => 24, 'stock' => 13, 'servings' => '8–10 guests', 'featured' => true, 'description' => 'Moist chocolate cake with fudge frosting and colourful sprinkles. The birthday classic, done properly.', 'ingredients' => 'Cocoa, butter, eggs, buttermilk, sugar, flour', 'allergens' => 'Contains dairy, eggs, wheat.'],
                    ['name' => 'Assorted Party Cupcakes', 'category' => 'cupcakes', 'price' => 850, 'hours' => 12, 'stock' => 32, 'servings' => 'Box of 6', 'featured' => false, 'description' => 'Six party cupcakes with vanilla and chocolate buttercream, sprinkles, and a cherry on top.', 'ingredients' => 'Butter, eggs, flour, cocoa, vanilla, sugar', 'allergens' => 'Contains dairy, eggs, wheat.'],
                    ['name' => 'Seaside Wedding Cake', 'category' => 'wedding', 'price' => 5900, 'hours' => 72, 'stock' => 3, 'servings' => '40–50 guests', 'featured' => false, 'description' => 'A two-tier vanilla and calamansi cake with textured buttercream inspired by Cebu\'s shoreline.', 'ingredients' => 'Vanilla, calamansi, butter, eggs, flour, cream', 'allergens' => 'Contains dairy, eggs, wheat.'],
                ],
            ],
        ];

        $sort = 0;
        foreach ($shops as $entry) {
            $owner = User::updateOrCreate(
                ['email' => $entry['owner']['email']],
                [
                    'name' => $entry['owner']['name'],
                    'phone' => $entry['owner']['phone'],
                    'role' => UserRole::ShopOwner,
                    'password' => $password,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $shop = Shop::updateOrCreate(
                ['slug' => $entry['shop']['slug']],
                $entry['shop'] + ['owner_id' => $owner->id, 'rating' => 0]
            );

            $shop->settings()->updateOrCreate([], [
                'pickup_enabled' => true,
                'delivery_enabled' => true,
                'pickup_days' => [1, 2, 3, 4, 5, 6, 7],
                'delivery_days' => [1, 2, 3, 4, 5, 6, 7],
                'time_slots' => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
                'unavailable_dates' => [],
                'max_orders_per_day' => 24,
                'advance_notice_hours' => 24,
                'delivery_fee' => 150,
                'free_delivery_threshold' => 5000,
                'pickup_instructions' => 'Ring the studio bell and ask for your SweetCrumbs order number.',
                'delivery_notes' => 'We deliver within Metro Manila. The rider will call before arrival.',
            ]);

            foreach (['birthday' => 'Birthday Cakes', 'wedding' => 'Wedding Cakes', 'cupcakes' => 'Cupcakes', 'custom-cakes' => 'Custom Creations'] as $slug => $name) {
                Category::updateOrCreate(
                    ['shop_id' => $shop->id, 'slug' => $slug],
                    [
                        'name' => $name,
                        'description' => 'Thoughtfully made '.strtolower($name).' for your celebration.',
                        'sort_order' => $sort++,
                        'is_active' => true,
                    ]
                );
            }

            $categories = $shop->categories()->get()->keyBy('slug');

            foreach ($entry['products'] as $item) {
                $product = Product::updateOrCreate(
                    ['shop_id' => $shop->id, 'slug' => Str::slug($item['name'])],
                    [
                        'category_id' => $categories[$item['category']]->id,
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'ingredients' => $item['ingredients'],
                        'allergens' => $item['allergens'],
                        'base_price' => $item['price'],
                        'servings' => $item['servings'],
                        'preparation_hours' => $item['hours'],
                        'stock_quantity' => $item['stock'],
                        'low_stock_threshold' => 4,
                        'is_active' => true,
                        'is_featured' => $item['featured'],
                        'customization_options' => $this->customizations(),
                    ]
                );

                $image = $this->nextImage();
                $product->update(['image_path' => $image]);
                $product->images()->updateOrCreate(['sort_order' => 0], ['path' => $image, 'alt' => $item['name']]);

                if ($product->variants()->count() === 0) {
                    $sizes = $item['category'] === 'cupcakes'
                        ? [['name' => 'Box of 6', 'price' => $item['price'], 'default' => true], ['name' => 'Box of 12', 'price' => round($item['price'] * 1.85, 2), 'default' => false]]
                        : [['name' => '6-inch', 'price' => $item['price'], 'default' => true], ['name' => '8-inch', 'price' => round($item['price'] * 1.35, 2), 'default' => false], ['name' => '10-inch', 'price' => round($item['price'] * 1.7, 2), 'default' => false]];

                    foreach ($sizes as $index => $size) {
                        $product->variants()->create([
                            'name' => $size['name'],
                            'price' => $size['price'],
                            'is_default' => $size['default'],
                            'sort_order' => $index,
                        ]);
                    }
                }
            }
        }

        $activeShops = Shop::query()->where('status', ShopStatus::Active->value)->with('products.variants')->get();

        foreach ($activeShops as $shop) {
            $this->seedOrders($shop, $customers);
        }

        $pendingOwner = User::updateOrCreate(
            ['email' => 'owner@sugaratelier.ph'],
            [
                'name' => 'Camille Dela Cruz',
                'phone' => '09172000004',
                'role' => UserRole::ShopOwner,
                'password' => $password,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        Shop::updateOrCreate(
            ['slug' => 'sugar-atelier'],
            [
                'owner_id' => $pendingOwner->id,
                'name' => 'Sugar Atelier',
                'tagline' => 'Sculpted sugar, quiet luxury.',
                'description' => 'Sugar Atelier is a new Davao-based studio focused on sculpted sugar flowers and minimalist wedding cakes. We are excited to join the SweetCrumbs community and share our work with couples across Mindanao.',
                'email' => 'hello@sugaratelier.ph',
                'phone' => '09172000004',
                'address' => '5 Juna Avenue, Matina',
                'city' => 'Davao City',
                'province' => 'Davao del Sur',
                'postal_code' => '8000',
                'status' => ShopStatus::Pending,
                'is_featured' => false,
                'rating' => 0,
                'approved_at' => null,
            ]
        );

        PlatformSetting::set('platform_name', 'SweetCrumbs');
        PlatformSetting::set('support_email', 'hello@cakelle.ph');
        PlatformSetting::set('support_phone', '+63 917 000 0000');
        PlatformSetting::set('announcement', '');
        PlatformSetting::set('announcement_enabled', false);
    }

    private function seedOrders(Shop $shop, $customers): void
    {
        if ($shop->orders()->exists()) {
            return;
        }

        $products = $shop->products()->with('variants')->where('is_active', true)->get()->values();
        if ($products->isEmpty()) {
            return;
        }

        $definitions = [
            ['status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'placed' => 18, 'schedule_offset' => -14, 'fulfillment' => FulfillmentType::Pickup, 'customer' => 0, 'product' => 0, 'review' => 5, 'comment' => 'The chocolate truffle cake was the star of the evening. Beautiful finish and not too sweet.'],
            ['status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'placed' => 8, 'schedule_offset' => -6, 'fulfillment' => FulfillmentType::Delivery, 'customer' => 1, 'product' => 1, 'review' => 5, 'comment' => 'Arrived on time and looked exactly like the photos. Will order again for our next family celebration.'],
            ['status' => OrderStatus::Preparing, 'payment' => PaymentStatus::Paid, 'placed' => 1, 'schedule_offset' => 2, 'fulfillment' => FulfillmentType::Delivery, 'customer' => 2, 'product' => 2, 'review' => null, 'comment' => null],
            ['status' => OrderStatus::PendingConfirmation, 'payment' => PaymentStatus::Paid, 'placed' => 0, 'schedule_offset' => 3, 'fulfillment' => FulfillmentType::Pickup, 'customer' => 0, 'product' => 3, 'review' => null, 'comment' => null],
            ['status' => OrderStatus::PendingPayment, 'payment' => PaymentStatus::Unpaid, 'placed' => 0, 'schedule_offset' => 4, 'fulfillment' => FulfillmentType::Pickup, 'customer' => 1, 'product' => 4, 'review' => null, 'comment' => null],
        ];

        foreach ($definitions as $index => $definition) {
            $customer = $customers[$definition['customer']];
            $product = $products[$definition['product'] % $products->count()];
            $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
            $unitPrice = (float) ($variant?->price ?? $product->base_price);
            $placedAt = now()->subDays($definition['placed'])->setTime(10 + $index, 15);
            $scheduledDate = now()->addDays($definition['schedule_offset']);
            $deliveryFee = $definition['fulfillment'] === FulfillmentType::Delivery ? 150.0 : 0.0;
            $total = round($unitPrice + $deliveryFee, 2);

            $order = Order::create([
                'shop_id' => $shop->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'fulfillment_type' => $definition['fulfillment'],
                'delivery_address' => $definition['fulfillment'] === FulfillmentType::Delivery
                    ? 'Unit '.($index + 2).'B, 21 Mabini Street, '.$shop->city
                    : null,
                'subtotal' => $unitPrice,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'status' => $definition['status'],
                'payment_status' => $definition['payment'],
                'scheduled_date' => $scheduledDate->toDateString(),
                'scheduled_time' => '14:00',
                'notes' => $definition['review'] ? 'Please write "Happy celebration" on the cake.' : null,
                'payment_method' => 'paymongo',
                'inventory_reserved' => false,
                'placed_at' => $placedAt,
                'confirmed_at' => in_array($definition['status'], [OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Completed], true) ? $placedAt->copy()->addHours(2) : null,
                'completed_at' => $definition['status'] === OrderStatus::Completed ? $scheduledDate->copy()->setTime(16, 0) : null,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name' => $product->name,
                'variant_name' => $variant?->name,
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice,
                'customizations' => [
                    'flavor' => ['group' => 'Flavor', 'value' => 'belgian-chocolate', 'label' => 'Belgian chocolate', 'price' => 0],
                ],
                'special_instructions' => $definition['review'] ? 'Please add a simple message on the cake.' : null,
            ]);

            $payment = Payment::create([
                'order_id' => $order->id,
                'shop_id' => $shop->id,
                'provider' => 'paymongo',
                'provider_payment_id' => $definition['payment'] === PaymentStatus::Paid ? 'pay_demo_'.$order->id : null,
                'reference_number' => $order->order_number,
                'amount' => $total,
                'currency' => 'PHP',
                'status' => $definition['payment'],
                'payment_method' => $definition['payment'] === PaymentStatus::Paid ? ($index % 2 === 0 ? 'card' : 'qrph') : null,
                'paid_at' => $definition['payment'] === PaymentStatus::Paid ? $placedAt->copy()->addMinutes(6) : null,
                'expires_at' => $definition['payment'] === PaymentStatus::Unpaid ? $placedAt->copy()->addMinutes(45) : null,
            ]);

            if ($definition['payment'] === PaymentStatus::Paid) {
                PaymentTransaction::create([
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'provider' => 'paymongo',
                    'provider_event_id' => 'evt_demo_'.$order->id,
                    'type' => 'webhook',
                    'status' => 'paid',
                    'amount' => $total,
                    'payload' => ['source' => 'demo_seed', 'order' => $order->order_number],
                ]);
            }

            if (in_array($definition['status'], [OrderStatus::Preparing, OrderStatus::PendingConfirmation, OrderStatus::PendingPayment], true)) {
                app(InventoryService::class)->reserve($order);
            }

            $shopCustomer = ShopCustomer::updateOrCreate(
                ['shop_id' => $shop->id, 'email' => $customer->email],
                [
                    'user_id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'last_order_at' => $placedAt,
                ]
            );
            $shopCustomer->increment('order_count');
            $shopCustomer->increment('total_spent', $total);

            if ($definition['review']) {
                Review::updateOrCreate(
                    ['shop_id' => $shop->id, 'order_id' => $order->id],
                    [
                        'product_id' => $product->id,
                        'customer_id' => $customer->id,
                        'rating' => $definition['review'],
                        'comment' => $definition['comment'],
                        'is_visible' => true,
                    ]
                );
            }
        }

        $shop->update([
            'rating' => round((float) $shop->reviews()->where('is_visible', true)->avg('rating'), 2),
        ]);
    }

    private function customizations(): array
    {
        return [
            [
                'key' => 'flavor',
                'label' => 'Flavor',
                'required' => true,
                'options' => [
                    ['value' => 'classic-vanilla', 'label' => 'Classic vanilla', 'price' => 0],
                    ['value' => 'belgian-chocolate', 'label' => 'Belgian chocolate', 'price' => 80],
                    ['value' => 'ube-halaya', 'label' => 'Ube halaya', 'price' => 120],
                ],
            ],
            [
                'key' => 'finish',
                'label' => 'Finish',
                'required' => false,
                'options' => [
                    ['value' => 'minimal', 'label' => 'Minimal buttercream', 'price' => 0],
                    ['value' => 'floral', 'label' => 'Floral piping', 'price' => 350],
                    ['value' => 'gold-leaf', 'label' => 'Gold leaf accents', 'price' => 650],
                ],
            ],
        ];
    }

    private function nextImage(): string
    {
        $id = $this->images[$this->imageIndex % count($this->images)];
        $this->imageIndex++;

        return 'https://images.unsplash.com/'.$id.'?auto=format&fit=crop&w=1400&q=80';
    }
}
