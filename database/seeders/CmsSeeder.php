<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureCollection('pages', 'Pages', '/pages/{slug}');
        $this->ensureCollection('faqs', 'FAQs');
        $this->ensureCollection('testimonials', 'Testimonials');

        $pages = [
            [
                'slug' => 'about',
                'title' => 'A marketplace built around the cake, not the cart.',
                'intro' => 'SweetCrumbs is a place for independent Filipino bakers to tell their story — and for you to find the cake that fits yours.',
                'content' => "We started SweetCrumbs because the best cakes rarely live where the biggest audiences are. They live in home kitchens in Quezon City, small studios in Makati, and family bakeries in Cebu. SweetCrumbs brings those bakers into one considered marketplace.\n\nFor customers, that means discovering real bakeries, comparing real cakes, and reserving a pickup or delivery without guesswork. For bakers, it means a storefront, order tools, inventory, and payments that let them focus on the work they love.\n\nEvery bakery on SweetCrumbs is reviewed before it opens. Every order is tied to one bakery, so nothing is lost between storefronts. And every payment is verified by PayMongo before an order moves forward.",
                'updated_label' => 'Built in the Philippines, for celebrations everywhere.',
            ],
            [
                'slug' => 'faqs',
                'title' => 'Frequently asked questions',
                'intro' => 'Everything you need to know about ordering, delivery, and baking with SweetCrumbs.',
                'content' => '',
                'updated_label' => 'Still curious? Write to hello@cakelle.ph.',
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of service',
                'intro' => 'The simple agreement between you, SweetCrumbs, and the bakeries you order from.',
                'content' => "SweetCrumbs is a marketplace that connects customers with independent bakeries. Each bakery is responsible for the cakes it makes, its ingredients, allergen information, and fulfillment promises.\n\nOrders are confirmed only after PayMongo verifies payment. Cakes are prepared to order and require the advance notice shown at checkout. Cancellation is available before a bakery confirms an order; after confirmation, please contact the bakery directly.\n\nWe ask customers to provide accurate contact details, choose realistic fulfillment dates, and communicate allergies clearly. Bakeries agree to keep their menus, stock, and schedules current.",
                'updated_label' => 'Last updated November 2026',
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy policy',
                'intro' => 'What we collect, why we collect it, and how we keep it safe.',
                'content' => "We collect the information needed to process an order: your name, email address, phone number, delivery address, and order details. Payment card information is handled by PayMongo and is never stored on SweetCrumbs servers.\n\nBakeries receive the information required to fulfill your order. We use order data to provide support, prevent fraud, and improve the marketplace. We never sell your personal information.\n\nYou can ask us to access, correct, or delete your account information by writing to hello@cakelle.ph.",
                'updated_label' => 'Last updated November 2026',
            ],
        ];

        foreach ($pages as $page) {
            $this->saveEntry('pages', $page['slug'], [
                'title' => $page['title'],
                'intro' => $page['intro'],
                'content' => $page['content'],
                'updated_label' => $page['updated_label'],
            ]);
        }

        $faqs = [
            ['How far in advance should I order?', 'Every cake has its own preparation window, shown on the product page and again at checkout. Most celebration cakes need at least 24 hours, while tiered wedding cakes typically need 72 hours.'],
            ['Can I order from more than one bakery at a time?', 'SweetCrumbs completes one bakery per order so each cake is handled and delivered by the team that made it. You can place separate orders with different bakeries.'],
            ['How do I know my payment went through?', 'SweetCrumbs confirms payment only after PayMongo verifies it. Your order page updates automatically, and you will also receive an in-app notification if you have an account.'],
            ['Can I cancel my order?', 'You can cancel online while an order is awaiting payment or awaiting bakery confirmation. Once a bakery confirms the order, please contact them directly. Paid orders require a refund before cancellation.'],
            ['Do you deliver outside Metro Manila?', 'Delivery coverage is set by each bakery. Check the bakery storefront and the checkout page for the options available for your address.'],
            ['Can I request a custom design?', 'Yes. Many bakeries offer flavor and finish options at checkout. For fully bespoke designs, use the bakery contact details on its storefront.'],
            ['What if I have a food allergy?', 'Each cake lists known allergens. Bakeries work in kitchens that may handle nuts, dairy, eggs, and wheat, so please contact the bakery directly if you need a guaranteed allergen-free environment.'],
            ['How do I become a SweetCrumbs bakery?', 'Choose "Sell with SweetCrumbs", tell us about your bakery, and submit your application. Our team reviews every application before the storefront goes live.'],
        ];

        foreach ($faqs as $index => [$question, $answer]) {
            $this->saveEntry('faqs', 'faq-'.($index + 1), [
                'title' => $question,
                'question' => $question,
                'answer' => $answer,
                'order' => $index + 1,
            ]);
        }

        $testimonials = [
            ['The cake was even better than the photos.', 'The whole process felt considered — from choosing the size to the message on top. Our guests are still talking about the chocolate truffle.', 'Mika Reyes', 'Birthday cake · Quezon City', 5],
            ['A bakery that actually communicates.', 'We ordered a two-tier cake for an intimate wedding. The baker helped us adjust the palette and the delivery arrived exactly on time.', 'Ana Villanueva', 'Wedding cake · Makati', 5],
            ['SweetCrumbs made a difficult week feel special.', 'I ordered from Cebu for my mom\'s birthday and the mango chiffon arrived beautifully packed. It tasted like home.', 'Miguel Torres', 'Birthday cake · Cebu City', 5],
        ];

        foreach ($testimonials as $index => [$title, $quote, $name, $detail, $rating]) {
            $this->saveEntry('testimonials', 'testimonial-'.($index + 1), [
                'title' => $title,
                'quote' => $quote,
                'name' => $name,
                'detail' => $detail,
                'rating' => $rating,
                'order' => $index + 1,
            ]);
        }

        $global = GlobalSet::findByHandle('homepage')
            ?? GlobalSet::make('homepage')->title('Homepage');

        $global->save();

        $site = Site::default()->handle();

        if (! $global->existsIn($site)) {
            $global->makeLocalization($site);
        }

        $global->in($site)->data([
            'hero_eyebrow' => 'Crafted for life\'s sweetest moments',
            'hero_heading' => 'Every celebration deserves a cake with a story.',
            'hero_subheading' => 'Discover independent bakeries, design your perfect cake, and reserve a pickup or delivery across the Philippines.',
            'hero_primary_label' => 'Explore cakes',
            'hero_primary_url' => '/cakes',
            'hero_secondary_label' => 'Meet the bakers',
            'hero_secondary_url' => '/bakeries',
            'hero_image' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=1600&q=80',
            'promo_heading' => 'A cake for every kind of celebration',
            'promo_body' => 'From intimate birthdays to once-in-a-lifetime weddings, SweetCrumbs brings the Philippines\' most thoughtful home bakers into one beautiful marketplace.',
            'promo_cta_label' => 'Start exploring',
            'promo_cta_url' => '/cakes',
        ])->save();
    }

    private function ensureCollection(string $handle, string $title, ?string $route = null): void
    {
        if (Collection::findByHandle($handle)) {
            return;
        }

        $collection = Collection::make($handle)->title($title);

        if ($route) {
            $collection->routes($route);
        }

        $collection->save();
    }

    private function saveEntry(string $collection, string $slug, array $data): void
    {
        $entry = Entry::query()
            ->where('collection', $collection)
            ->where('slug', $slug)
            ->first();

        $entry ??= Entry::make()
            ->collection($collection)
            ->slug($slug);

        $entry->data($data)->published(true)->save();
    }
}
