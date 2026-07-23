# Customized Distribution Mobile Application & Admin Portal

Billing/invoicing, stock tracking, cash & credit sales, customer credit management, and business reporting.

## Structure

```
backend/   Laravel API + Admin Portal (Blade), MySQL database
mobile/    Flutter mobile app (Android/iOS), talks to backend via REST + Sanctum tokens
```

## Backend setup

```
cd backend
composer install
cp .env.example .env   # already configured for local MySQL: distribution_app
php artisan key:generate
php artisan migrate
php artisan serve
```

Create the first admin user (there's no self-registration — admins create staff accounts from the portal's Staff page):

```
php artisan tinker --execute="App\Models\User::create(['name'=>'Admin','email'=>'admin@test.com','password'=>bcrypt('password'),'role'=>'admin']);"
```

Visit `http://localhost:8000` and log in. The portal covers:

- **Dashboard** — today's sales, cash vs credit split, total receivables, low-stock count.
- **Sales** — POS-style new sale screen (cash/credit), invoice list, printable invoice view.
- **Customers** — list with balance/limit, create/edit, per-customer statement (ledger) + aging summary + record-payment form.
- **Products** — list, create/edit, manual stock adjustments (with a reason, logged to `stock_movements`).
- **Reports** — receivables/aging, low stock, sales summary by date range.
- **Staff** (admin-only) — create/remove staff and admin accounts.

## Mobile setup

```
cd mobile
flutter pub get
flutter run
```

`lib/services/api_client.dart` has the API base URL — `http://10.0.2.2:8000/api` works for the Android emulator talking to `php artisan serve` on the host. Change it for a real device or production.

## Credit management design

- `customers.credit_limit` / `customers.current_balance` — cached balance, always derived from the ledger.
- `customer_ledger_entries` — append-only audit trail. Every credit sale writes a `+amount` entry, every payment writes a `-amount` entry, each with a `balance_after` snapshot. This is the source of truth; `current_balance` is a cache for fast reads.
- `App\Services\CreditService` — `recordSaleOnCredit()` locks the customer row, rejects the sale if it would exceed `credit_limit` (throws `CreditLimitExceededException` → HTTP 422), and atomically writes the ledger entry + balance update. `recordPayment()` does the same for payments. `agingSummary()` buckets outstanding credit sales into 0-30/31-60/61-90/90+ day buckets.
- `App\Services\SaleService` — creates a sale + line items, deducts stock via `stock_movements` (also an audit-trail table, mirroring the ledger pattern), and calls `CreditService` for credit sales. Everything runs in one DB transaction, so a rejected credit sale rolls back stock changes too.

## Notes

- API routes (`routes/api.php`, used by the mobile app) are all named with an `api.` prefix (e.g. `api.sales.show`) so they never collide with the portal's web route names (`sales.show`). Keep that prefix if you add more API routes.
- `admin` middleware (`App\Http\Middleware\EnsureUserIsAdmin`) gates admin-only actions like staff management; both `admin` and `staff` roles can otherwise use the portal for day-to-day sales/payments.
- Self-registration and email verification were removed from Breeze's default scaffolding — not needed for an internally-provisioned admin tool.

## Next steps

- Add invoice PDF generation/download.
- Add per-product stock movement history view.
- Add automated tests around `CreditService`/`SaleService` (currently verified manually via curl walkthroughs of both the API and the portal).
