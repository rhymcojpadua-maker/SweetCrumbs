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

1. Build the production Docker image from the repository root.
2. Set `APP_ENV=production`, `APP_DEBUG=false`, a stable `APP_KEY`, public HTTPS `APP_URL`, database credentials, and Xendit credentials in the hosting provider's environment settings. Do not copy `.env` into the image.
3. Run `php artisan migrate --force` after the production database is configured.
4. Configure persistent storage for `storage/app/public` if shops will upload images.
5. Verify `/`, `/login`, `/admin`, and one webhook test event.

### Render

Use the repository root as the service root and select the Docker runtime. The root `Dockerfile` builds Vite assets, installs production Composer dependencies, serves Laravel's `public` directory with Apache, and listens on Render's port `10000`. Set environment variables in Render rather than committing a production `.env` file. Configure a persistent disk mounted at `/var/www/html/storage/app/public` to retain uploaded files across deploys.

For a live launch, use `APP_ENV=production`, `APP_DEBUG=false`, a stable `APP_KEY`, a public HTTPS `APP_URL`, production database credentials, and the Xendit live secret key and callback token from the same live account. The application environment currently used for development is not a production configuration. Set a real mail transport if outbound email is enabled.

Run seeders only on a fresh environment:

```powershell
php artisan migrate:fresh --seed
```

`CakelleDemoSeeder` is intended for demos and staging, not production.

## Xendit

1. Create a test or live secret API key in the Xendit dashboard and set `XENDIT_SECRET_KEY`.
2. Set `XENDIT_BASE_URL=https://api.xendit.co` and `XENDIT_CURRENCY=PHP`.
3. Register `https://<host>/webhooks/xendit` for Payment Session events:
   - `payment_session.completed`
   - `payment_session.expired`
4. Copy the webhook callback verification token from the dashboard to `XENDIT_WEBHOOK_TOKEN`.
5. Optionally set `XENDIT_ALLOWED_PAYMENT_CHANNELS` to a comma-separated list of Xendit channel codes enabled on the account. Leave it empty to allow all enabled channels.

Security invariants:

- Webhooks must include the exact dashboard callback token in the `x-callback-token` header.
- Session events are idempotent by event type and payment session ID; replays are recorded and ignored.
- The completed-session amount must exactly match the expected order total.
- Paid status is never granted from a redirect, client callback, or unverified payload.

When Xendit is not configured the checkout UI shows an explicit unconfigured state. This is intentional; do not add simulated payments.

## Bakery subscriptions

Set the monthly bakery price in **Admin → Platform settings** after Xendit checkout and the `payment_session.completed` / `payment_session.expired` webhook events are configured. Leave the field blank to disable subscriptions; bakeries will not be charged or access-restricted while no price is set.

When a price is first configured, existing active bakeries receive a 30-day grace period. Newly approved bakeries receive 30 days from approval. Subscriptions renew manually by month through Xendit; the application does not create automatic recurring charges. Once a period expires, the storefront is removed from the marketplace and the seller dashboard redirects to the subscription page until a verified payment completes. A checkout redirect by itself never activates a subscription.

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
| Webhook returns 401 | Confirm `XENDIT_WEBHOOK_TOKEN` matches the dashboard callback token and that the request includes `x-callback-token` |
| Webhook returns 200 `ignored` | Reference number must equal the order number; check the payment row and logs |
