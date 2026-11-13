# SweetCrumbs

SweetCrumbs is a multi-tenant cake marketplace for the Philippines. Customers discover bakeries and order celebration cakes; shop owners manage their storefront, orders, products, inventory, customers, and payments; platform admins review applications and oversee the marketplace. Payments run through PayMongo. Editorial content is managed in Statamic.

## Stack

- Laravel 12 (PHP 8.4) with Blade, Alpine.js, Tailwind CSS 4, Vite, Chart.js
- MySQL 8
- Statamic 6 (CMS control panel at `/cp`)
- PayMongo Checkout Sessions + signed webhooks

## Features

- Public marketplace: home, cake catalogue, bakery directory, product pages, cart, checkout, public order tracking and receipts
- Shop onboarding: apply to sell, pending/suspended states, owner dashboard
- Shop dashboard: orders and status transitions, products and variants, categories, inventory movements, customers, payments, reports, storefront and fulfillment settings, profile, notifications
- Super-admin dashboard: shop applications approve/reject/suspend/activate, users, orders, payments, activity log, platform settings, CMS links
- Server-side pricing, stock reservation/release, preparation lead time and schedule validation
- PayMongo Checkout, webhook signature verification, idempotent payment processing, refund and failure handling
- Tenant isolation enforced with `shop_id` scoping, middleware, and policies
- Scheduled command `cakelle:expire-orders` cancels unpaid orders after the payment window and releases stock

## Getting started

Requirements: PHP 8.4+, Composer, Node 20+, MySQL 8.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Create the databases and configure `.env`:

```sql
CREATE DATABASE cakelle;
CREATE DATABASE cakelle_test;
```

Set at minimum:

```dotenv
APP_NAME=SweetCrumbs
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cakelle
DB_USERNAME=root
DB_PASSWORD=
```

Then migrate, seed demo data, link storage, and build assets:

```powershell
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Open `http://localhost:8000`.

## Demo accounts

All seeded accounts use the password `Password123!`.

| Role | Email |
| --- | --- |
| Super admin | `admin@cakelle.ph` |
| Customer | `customer@cakelle.ph` (also `ana@example.com`, `miguel@example.com`, `sofia@example.com`) |
| Shop owner | `owner@sweetlayers.ph` |
| Shop owner | `owner@butterandbloom.ph` |
| Shop owner | `owner@littleoven.ph` |
| Shop owner (pending shop) | `owner@sugaratelier.ph` |

Seeded shops: Sweet Layers Manila, Butter & Bloom, The Little Oven (active), and Sugar Atelier (pending application).

## Payments (PayMongo)

Set the keys in `.env`:

```dotenv
PAYMONGO_SECRET_KEY=sk_test_...
PAYMONGO_PUBLIC_KEY=pk_test_...
PAYMONGO_WEBHOOK_SECRET=whsec_...
PAYMONGO_PAYMENT_METHODS=qrph,card,gcash,paymaya
PAYMONGO_CURRENCY=PHP
```

Register a webhook in the PayMongo dashboard pointing to:

```text
https://your-domain.test/webhooks/paymongo
```

Subscribe to `checkout_session.payment.paid`, `payment.paid`, `payment.failed`, and `payment.refunded`. When the keys are empty the UI shows an unconfigured payment state instead of simulating a payment. An order is marked paid only after a verified webhook.

## Statamic CMS

The CMS uses Statamic's file user repository (`users/*.yaml`) on a dedicated `cp` guard, separate from the Laravel app accounts on the `web` guard. This keeps the free single-user control panel working while the marketplace keeps its own `users` table. A super user is already included.

Sign in at `/cp` with `admin@cakelle.ph` / `Password123!`.

Create the file user (delete `users/admin@cakelle.ph.yaml` first to reset it):

```powershell
php artisan statamic:make:user admin@cakelle.ph --super --password=Password123!
```

The free plan allows one CP user; adding a second one requires Statamic Pro. App users such as customers and shop owners cannot sign in to `/cp` and do not need Statamic accounts. CMS content lives in `content/` (pages, FAQs, testimonials) and is edited from the control panel. The app's own admin area remains at `/admin`.

## Testing

Tests use a dedicated database. `.env.testing` sets `DB_DATABASE=cakelle_test`, `SESSION_DRIVER=array`, and `CACHE_STORE=array`.

```powershell
php artisan test
```

Coverage:

- `tests/Feature/ViewSmokeTest.php` renders every public, shop dashboard, and admin screen
- `tests/Feature/DomainFlowTest.php` covers tenant isolation, order transitions, paid-order cancellation guard, PayMongo webhook signature/idempotency, and admin shop approval

## Scheduled work

Run the scheduler locally while developing:

```powershell
php artisan schedule:work
```

In production add the standard cron entry:

```cron
* * * * * cd /path/to/cakelle && php artisan schedule:run >> /dev/null 2>&1
```

## Project layout

```text
app/Http/Controllers            Public, Auth, Dashboard, Admin controllers
app/Http/Middleware             Role, account status, shop operational guards
app/Models                      Eloquent models and tenant scoping trait
app/Policies                    Order, product, shop authorization
app/Services                    Cart, checkout, inventory, payments, schedules, CMS, activity log
app/Enums                       Order, payment, shop, fulfillment, role enums
resources/views                 Blade views (public, auth, dashboard, admin, partials)
resources/css/app.css           Tailwind theme and components
database/seeders                Demo marketplace and CMS seeders
content/ + resources/blueprints Statamic content and field definitions
users/                          Statamic CP users (file repository)
tests/Feature                   Smoke and domain flow tests
```

## Business rules

- A cart contains products from one shop; checkout validates shop status, product availability, stock, variants, fulfillment capability, and preparation lead time server-side.
- Prices are always recomputed on the server; client-submitted totals are ignored.
- Stock is reserved when an order is placed and released on cancellation, rejection, payment failure, or expiry.
- Payment status changes come only from verified PayMongo webhooks or server-side session sync.
- Paid orders cannot be cancelled from the dashboard until a refund is completed.
- Fulfillment transitions are validated (pickup uses ready-for-pickup, delivery uses out-for-delivery).
- Every dashboard and admin query is scoped by `shop_id`; cross-shop access returns 404/403.
