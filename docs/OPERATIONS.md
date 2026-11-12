# Cakelle operations

## Environments

| Concern | Local | Production |
| --- | --- | --- |
| App env | `APP_ENV=local`, `APP_DEBUG=true` | `APP_ENV=production`, `APP_DEBUG=false` |
| Database | MySQL `cakelle` | Managed MySQL 8, separate credentials |
| Cache / session | `file` | `redis` or database |
| Queue | `sync` | `redis` with a supervised worker |
| Assets | `npm run dev` | `npm run build` during deploy |
| Scheduler | `php artisan schedule:work` | cron `schedule:run` every minute |

Never enable `APP_DEBUG` in production. Rotate `APP_KEY` only with a planned session invalidation.

## Deploy checklist

1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build`
3. `php artisan migrate --force`
4. `php artisan storage:link` (first deploy only)
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
6. Restart queue workers and PHP-FPM/Octane
7. Verify `/`, `/login`, `/admin`, and one webhook test event

Run seeders only on a fresh environment:

```powershell
php artisan migrate:fresh --seed
```

`CakelleDemoSeeder` is intended for demos and staging, not production.

## PayMongo

1. Create test and live key pairs in the PayMongo dashboard.
2. Store keys in the environment as `PAYMONGO_SECRET_KEY`, `PAYMONGO_PUBLIC_KEY`, and `PAYMONGO_WEBHOOK_SECRET`.
3. Register `https://<host>/webhooks/paymongo` with events:
   - `checkout_session.payment.paid`
   - `payment.paid`
   - `payment.failed`
   - `payment.refunded`
4. Keep `PAYMONGO_PAYMENT_METHODS` aligned with methods enabled on the account (default `qrph,card,gcash,paymaya`).

Security invariants:

- Webhooks are verified with HMAC-SHA256 over `"{timestamp}.{rawBody}"` using the `te` signature for test mode and `li` for live mode. A five-minute tolerance is applied by default (`PAYMONGO_WEBHOOK_TOLERANCE`, seconds; set `0` to disable).
- Events are idempotent by event id; replays are recorded and ignored.
- Amounts below the expected order total are rejected and logged as warnings.
- Paid status is never granted from a redirect, client callback, or unverified payload.

When PayMongo is not configured the checkout UI shows an explicit unconfigured state. This is intentional; do not add simulated payments.

## Order expiry

`cakelle:expire-orders` runs every five minutes via `routes/console.php`. It cancels orders still pending payment after `cakelle.order_payment_window_minutes` (default 45) and releases reserved inventory.

```powershell
php artisan cakelle:expire-orders
```

Ensure exactly one scheduler runs per environment; the command uses `withoutOverlapping()`.

## Statamic control panel

Statamic stores CP users as YAML files in `users/` and authenticates them on the dedicated `cp` guard (`config/auth.php`, `config/statamic/users.php`). Marketplace accounts on the `web` guard are separate and cannot access `/cp`.

The included super user is `admin@cakelle.ph` / `Password123!`. Create it on a fresh checkout (delete `users/admin@cakelle.ph.yaml` first to reset it):

```powershell
php artisan statamic:make:user admin@cakelle.ph --super --password=Password123!
```

Statamic's free plan permits a single CP user. Creating a second file user returns a Statamic Pro requirement, so keep one account unless Pro is licensed. Because the repository is `file`, the Statamic auth migrations are not required for CP access; the app `users` table is unaffected.

Content collections and globals are defined in `resources/blueprints`; content lives in `content/`. The `/admin` area is the marketplace back office and is unrelated to `/cp`.

## Database and tenant safety

- Every shop-scoped table carries `shop_id`; models use the `BelongsToShop` concern where appropriate.
- Controllers scope queries by the authenticated owner's shop and abort with 404/403 on cross-shop access.
- Policies (`OrderPolicy`, `ProductPolicy`, `ShopPolicy`) enforce ownership for model-level authorization.
- Admin routes require the `super_admin` role in addition to authentication and account status checks.

## Assets

Tailwind CSS 4 is configured in `resources/css/app.css`. Custom component classes cannot be used with `@apply` in this setup; use raw utilities inside the component definition. Build failures mentioning an unknown class almost always mean a custom class was `@apply`-ed.

## Tests

```powershell
php artisan test
php artisan test --filter=DomainFlowTest
```

Tests read `.env.testing` and use the `cakelle_test` database, `array` session/cache, and `array` mailer. They reseed demo data per test class.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| 500 on dashboard notification page | View composer must bind `unreadNotificationCount` for `dashboard.notifications.index` |
| `Call to undefined method ...::authorize()` | Base `App\Http\Controllers\Controller` must use `AuthorizesRequests` |
| Marketplace shows duplicate categories | Categories are per shop; use `Category::marketplace()` to aggregate by slug |
| Images missing after deploy | Run `php artisan storage:link` and confirm `public/storage` |
| Stale views after deploy | `php artisan view:clear && php artisan view:cache` |
| Webhook returns 401 | Confirm `PAYMONGO_WEBHOOK_SECRET`, signature header name, timestamp tolerance, and that the raw body is unmodified |
| Webhook returns 200 `ignored` | Reference number must equal the order number; check the payment row and logs |
