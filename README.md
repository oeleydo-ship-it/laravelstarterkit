# Laravel SaaS Starter Kit

A production-ready Laravel 12 SaaS starter kit with multi-tenancy, Stripe billing, RBAC, modular architecture, and a polished Bootstrap 5 admin dashboard.

## Features

| Feature | Details |
|---------|---------|
| **Multi-Tenant** | Single-database tenancy via `tenant_id` scoping with automatic global scope |
| **Stripe Billing** | Subscriptions via Laravel Cashier — checkout, portal, webhooks, plan management |
| **RBAC** | Owner / Admin / Member roles with middleware + policies |
| **Module System** | Enable/disable feature modules per tenant (Clients, Tickets, etc.) |
| **Team Management** | Invite members via token-based email links, manage roles & activation |
| **Settings** | Tenant-scoped settings with logo upload, timezone, notification email |
| **Activity Logging** | Automatic audit trail via `LogsActivity` trait |
| **Dashboard** | Analytics widgets with stat cards and recent activity table |
| **Plan Limits** | Enforce max users, max modules, storage per plan tier |

## Tech Stack

- **Laravel 12** (PHP 8.2+)
- **Bootstrap 5** (via `laravel/ui`, local CSS)
- **Laravel Cashier** (Stripe subscriptions)
- **MySQL** (single-database multi-tenancy)
- **Vite** (frontend build)

## Quick Start

```bash
# 1. Clone and install
git clone <repo-url> saas-kit
cd saas-kit
composer install
npm install && npm run build

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Configure .env
# Set DB_DATABASE, DB_USERNAME, DB_PASSWORD
# Set STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET

# 4. Database
php artisan migrate --seed

# 5. Storage link (for logo uploads)
php artisan storage:link

# 6. Run
php artisan serve
```

## Default Demo Accounts

After seeding, the following accounts are available:

| Email | Password | Role |
|-------|----------|------|
| owner@demo.com | password | Owner |
| admin@demo.com | password | Admin |
| member@demo.com | password | Member |

## Stripe Setup

1. Create products and prices in your [Stripe Dashboard](https://dashboard.stripe.com)
2. Update `database/seeders/PlanSeeder.php` with your real Stripe Price IDs
3. Set your keys in `.env`:
   ```
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```
4. For local development, use the Stripe CLI:
   ```bash
   stripe listen --forward-to localhost:8000/stripe/webhook
   ```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/       # All controllers (Dashboard, Billing, Team, etc.)
│   ├── Middleware/         # SetTenant, CheckRole, EnsureModuleEnabled, EnforcePlanLimits
│   └── Requests/          # Form request validation (ClientRequest, TicketRequest, etc.)
├── Models/                # Eloquent models with relationships
├── Policies/              # Authorization policies (ClientPolicy, TicketPolicy)
├── Scopes/                # TenantScope for global query scoping
├── Traits/                # BelongsToTenant, LogsActivity
├── Providers/             # AppServiceProvider (policies, gates)
└── helpers.php            # currentTenant(), setting()

resources/views/
├── layouts/               # app.blade.php (admin), auth.blade.php (login/register)
├── partials/              # sidebar, topbar, alerts
├── auth/                  # login, register, accept-invite
├── billing/               # plans, status
├── modules/               # clients/ and tickets/ CRUD views
│   ├── clients/           # index, create, edit, show
│   └── tickets/           # index, create, edit, show
├── settings/              # index
├── team/                  # index
├── dashboard.blade.php
├── onboarding.blade.php
├── welcome.blade.php      # Landing page
└── pricing.blade.php      # Public pricing page
```

## Adding a New Module

1. Create migration, model, controller, and policy
2. Add to `ModuleSeeder` with a unique key
3. Create views in `resources/views/modules/your-module/`
4. Add routes in `web.php` wrapped with `EnsureModuleEnabled` middleware:
   ```php
   Route::middleware([EnsureModuleEnabled::class . ':your-module'])->group(function () {
       Route::resource('your-module', YourModuleController::class);
   });
   ```
5. The sidebar will auto-show navigation if the module is enabled

## License

MIT
