# Lingkung Gunung Adventure Camp Resort Bogor

Official website and direct booking system for **Lingkung Gunung Adventure Camp Resort**, a nature resort at the foot of Mount Gede Pangrango, Bogor, Indonesia.

The application combines a public website, an online booking engine with payment gateway integration, a group package builder for corporate and school events, customer accounts, and a full back-office admin panel in a single Laravel monolith.

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-5-FDAE4B)
![Livewire](https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)

| Home page | Mobile |
|---|---|
| ![Home page](docs/panduan/gambar/p01-beranda.jpg) | <img src="docs/panduan/gambar/p02-beranda-hp.jpg" alt="Mobile home page" width="260"> |

| Group package builder | Admin dashboard |
|---|---|
| ![Group package builder](docs/panduan/gambar/p07-rancang-paket.jpg) | ![Admin dashboard](docs/panduan/gambar/a03-dasbor.jpg) |

## Features

### Public website
- Accommodation catalogue (rooms, glamping, tents, camping areas) with photo galleries and comparison table
- Activities, group packages, dining menus, gallery, articles, location and contact pages
- Mobile-first design with sticky booking and WhatsApp bar
- SEO: structured data (LodgingBusiness, FAQPage, Article) and XML sitemap
- WebP images with responsive `srcset` and lazy loading

### Direct booking (B2C)
- Real-time availability per unit type and per night
- Weekday, weekend and peak-season pricing with promo codes
- 60-minute inventory hold while the guest completes payment
- Pay a 25% deposit or in full via **Midtrans Snap**, or by manual bank transfer
- E-ticket with QR code (PDF) and email confirmation
- Automatic balance reminders before arrival

### Group package builder (B2B)
- Guests design full-board packages (outbound, leadership training, gatherings) with menus, activities and add-ons
- Live price estimate and validation of package rules (minimum participants, weekday-only variants)
- Automatically generated PDF proposal sent by email
- Sales team follows up, edits and converts quotations into bookings

### Customer accounts
- Registration, email verification, password reset
- Booking history, payment status and e-tickets
- Wishlist of saved accommodations
- Claim past bookings with booking code and phone number

### Guest reviews
- Verified reviews only, available after check-out
- Ratings for the resort, accommodations, activities and packages, with photo uploads
- Moderation workflow in the admin panel

### Admin panel
- Bookings, payments, check-in, walk-in and phone reservations
- Occupancy calendar with date blocking
- Management of unit types, rates, promos, packages, activities and website content
- Contact inquiries, quotations and review moderation
- Revenue, booking and occupancy reports with Excel export
- Role-based access (owner, admin, sales, front office) and mandatory two-factor authentication
- Activity log of staff actions

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel 13 |
| Admin panel | Filament 5, Filament Shield |
| Frontend | Blade, Livewire 4, Alpine.js, Tailwind CSS 4, Vite |
| Database | MySQL 8.4 |
| Payments | Midtrans Snap |
| PDF | barryvdh/laravel-dompdf |
| Media | spatie/laravel-medialibrary (WebP conversion) |
| Backups | spatie/laravel-backup (AES-256 encrypted) |
| Cache, session, queue | Database driver (no Redis required) |

## Architecture highlights

- **Service layer.** All business logic lives in `app/Services` (booking, pricing, payment, quotation, reviews, reports). Controllers, Livewire components and Filament resources only call these services.
- **Inventory locking.** Availability is stored per unit type per date in `daily_inventory`. Bookings lock the affected rows with `SELECT ... FOR UPDATE` inside a transaction, so two guests can never book the last unit at the same time.
- **Server-side pricing.** Prices are always recalculated on the server; client input is never trusted.
- **Secure payments.** Midtrans webhooks verify the signature, order ID and amount, re-check the status through the Midtrans API, and are processed idempotently. A scheduled reconciliation job catches payments whose webhook never arrived.
- **Non-guessable URLs.** Booking, e-ticket and proposal pages use random codes instead of sequential IDs.
- **Configurable business rules.** Rates, packages, deposit percentage, hold duration and contact details are managed from the admin panel, not hard-coded.

## Getting started

### Requirements

- PHP 8.4 with extensions: `gd`, `intl`, `zip`, `sodium`, `pdo_mysql`
- Composer 2
- Node.js 22.12 or newer
- MySQL 8.4

### Installation

```bash
# 1. Install dependencies
composer install
npm install

# 2. Configure the environment
cp .env.example .env
php artisan key:generate
# Edit .env: database credentials, Midtrans keys, mail settings, ADMIN_PATH

# 3. Set up the database with initial data
php artisan migrate --seed
php artisan storage:link

# 4. Create the first admin account and grant the owner role
php artisan make:filament-user
php artisan shield:super-admin --user=1 --panel=admin

# 5. Start the development server (web server, queue worker, logs and Vite)
composer run dev
```

The site is available at `http://localhost:8000`. The admin panel lives at `/{ADMIN_PATH}` (default `/kelola`).

### Key environment variables

| Variable | Description |
|---|---|
| `ADMIN_PATH` | URL path of the admin panel |
| `ADMIN_REQUIRE_MFA` | Require two-factor authentication for staff |
| `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY` | Midtrans credentials |
| `MIDTRANS_IS_PRODUCTION` | `false` for sandbox, `true` for live payments |
| `CUSTOMER_EMAIL_VERIFICATION` | Require email verification for customer accounts (must be `true` in production) |
| `BACKUP_ARCHIVE_PASSWORD` | Encryption password for backups |
| `QUEUE_VIA_SCHEDULER` | Process the email queue from cron (for shared hosting) |

See `.env.example` for the full list and `.env.production.example` for production defaults.

### Scheduled tasks

In production, add a single cron entry:

```cron
* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
```

This runs expiry of unpaid holds (every minute), payment reconciliation (every 5 minutes), balance reminders, daily inventory generation and encrypted backups. During development, use `php artisan schedule:work`.

## Testing

The test suite runs against a dedicated MySQL database (not SQLite) so that row locking behaves as in production.

```bash
# Create the test database once
mysql -u root -p -e "CREATE DATABASE lingkung_gunung_test"

php artisan test
```

Coverage includes weekday/weekend pricing, concurrent bookings competing for the last unit (run in parallel processes), Midtrans webhook signature validation and idempotency, customer accounts, reviews and security regressions.

## Useful commands

| Command | Description |
|---|---|
| `php artisan inventory:generate` | Prepare daily inventory for the next 365 days |
| `php artisan bookings:expire-holds` | Release unpaid holds older than 60 minutes |
| `php artisan payments:reconcile` | Re-check pending transactions with Midtrans |
| `php artisan bookings:send-reminders` | Send balance payment reminders |
| `php artisan backup:run` | Create an encrypted backup |
| `php artisan resort:cek-produksi` | Check production readiness of the environment |
| `vendor/bin/pint` | Format PHP code |

## Project structure

```
app/
├── Console/Commands/   Scheduled and maintenance commands
├── Enums/              Booking, payment and review statuses
├── Filament/           Admin panel resources, pages and widgets
├── Http/               Controllers, middleware and form requests
├── Livewire/           Booking wizard, package builder, review form
├── Models/
├── Services/           Business logic
└── Support/            Helpers (e-ticket, images, contact info)
resources/views/
├── public/             Public website pages
├── akun/               Customer account pages
├── components/         Reusable Blade components
└── pdf/                E-ticket and proposal templates
docs/                   Deployment, security, testing and user guides
tests/Feature/          Feature and regression tests
```

## Documentation

| Document | Contents |
|---|---|
| [`docs/DEPLOY.md`](docs/DEPLOY.md) | Server deployment guide |
| [`docs/MIDTRANS-SANDBOX.md`](docs/MIDTRANS-SANDBOX.md) | Midtrans sandbox setup |
| [`docs/SECURITY-CHECKLIST.md`](docs/SECURITY-CHECKLIST.md) | Security checklist before go-live |
| [`docs/UAT-CHECKLIST.md`](docs/UAT-CHECKLIST.md) | User acceptance testing checklist |
| [`docs/AKSES-HALAMAN.md`](docs/AKSES-HALAMAN.md) | Route and access control inventory |
| [`docs/lingkung-gunung-website-user-manual-id.pdf`](docs/lingkung-gunung-website-user-manual-id.pdf) | User manual for resort staff (Indonesian) |

Most project documentation is written in Indonesian. The public website interface is in Indonesian.
