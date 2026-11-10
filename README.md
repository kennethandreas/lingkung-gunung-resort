# Lingkung Gunung Resort — Website & Direct Booking System

A production-grade web platform for **Lingkung Gunung Adventure Camp Resort (Bogor, Indonesia)** that
combines a marketing website, a direct-booking engine (B2C), a group package builder (B2B) and a
role-based back office in a single Laravel application. The goal is to move reservations away from
OTA commissions and manual WhatsApp bookkeeping toward a system where availability, pricing and
payments are managed in one place.

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-5-FDAE4B)
![Livewire](https://img.shields.io/badge/Livewire-4-4E56A6)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)

---

## Highlights

- **Concurrency-safe inventory** — availability is tracked per unit type per night in a `daily_inventory`
  table. Holds lock the affected rows with `SELECT … FOR UPDATE` inside a transaction, verified by a
  test that races five parallel PHP processes for the last available unit.
- **Payment integration (Midtrans Snap)** — webhook handling verifies the signature, order ID and amount,
  re-checks status against the Midtrans API and applies the result idempotently. A scheduled
  reconciliation job catches payments whose webhook never arrived.
- **Server-side pricing** — weekday/weekend/peak rates, promo codes, 25% down payment and settlement
  deadlines are always recalculated on the server; every rule is editable from the admin panel.
- **B2B Package Builder** — corporate and school groups compose multi-day packages (meals, activities,
  add-ons), receive a numbered PDF proposal and can be converted to a booking by sales staff.
- **Security by default** — mandatory 2FA for staff, granular permissions (Filament Shield), unguessable
  public codes instead of sequential IDs, strict security headers, per-route rate limiting, content-sniffed
  uploads and private storage for transfer receipts.
- **Tested** — 215 automated tests across 22 feature suites (booking, pricing, payments, security, UX).

## Features

### Public website & guest experience
- Mobile-first marketing site (accommodation, activities, group events, culinary, gallery, articles,
  location) with WebP `srcset` images, structured data (JSON-LD) and an XML sitemap.
- Four-step booking wizard (dates → units → guest details → payment) with a 60-minute stock hold
  and countdown.
- E-ticket with QR code (PDF, A5) and transactional emails for payment receipts and settlement reminders.
- Customer accounts: booking history, wishlist, claiming offline bookings, email verification and
  password reset.
- Verified guest reviews (only after check-out) with photo uploads and moderation.

### Back office (Filament admin panel)
- Operational dashboard: items needing attention, revenue and booking KPIs, upcoming arrivals.
- Bookings, payments (manual transfer & gateway), check-in, cancellation and walk-in entry —
  all routed through domain services instead of direct record edits.
- Occupancy calendar with date blocking, unit types and rates, promo codes, group packages,
  activities, add-ons and menu sets.
- B2B quotations with status pipeline, PDF regeneration and one-click WhatsApp follow-up.
- Content management: pages, banners, FAQs, gallery, programs and articles.
- Inquiry inbox, review moderation, reports with XLSX export, activity log and webhook log.
- Four roles: `owner`, `admin`, `sales` and `front_office`, each with its own permissions.

## Architecture

```
app/
├── Services/        Business logic (single source of truth)
│   ├── BookingService       availability, holds, confirmation, expiry, check-in
│   ├── PricingService       nightly rates, promos, down payment
│   ├── PaymentService       Midtrans Snap, webhooks, reconciliation, manual transfers
│   ├── QuotationService     B2B estimates, proposals, conversion to booking
│   └── Review / Report / Dashboard / Inquiry / CustomerAccount services
├── Livewire/        BookingWizard, PackageBuilder, ReviewForm
├── Filament/        Admin resources, pages (calendar, reports, settings) and widgets
├── Enums/           Booking, payment and review states with Indonesian labels
├── Console/         Scheduled jobs (expire holds, reconcile payments, reminders, inventory)
└── Http/            Thin controllers, form requests, security middleware
```

Controllers, Livewire components and Filament resources only orchestrate; every state change goes
through a service. Money is stored as integer rupiah. Cache, sessions and queues use the `database`
driver so the system runs on low-cost hosting without Redis.

**Booking lifecycle:** `Pending → DP Paid → Fully Paid → Checked-in`, with `Expired` and `Cancelled`
as terminal states.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel 13 |
| Admin panel | Filament 5, Filament Shield (RBAC) |
| Frontend | Blade, Livewire 4, Alpine.js, Tailwind CSS 4, Vite 8 |
| Database | MySQL 8.4 |
| Payments | Midtrans Snap (sandbox & production) |
| Documents | DomPDF (e-tickets, proposals), Simple QrCode, OpenSpout (XLSX) |
| Media | Spatie Media Library with WebP conversions |
| Operations | Spatie Backup (AES-256 encrypted), Activity Log, Sitemap |
| Testing | PHPUnit 12 |

## Getting Started

**Requirements:** PHP 8.4 (extensions: gd, intl, zip, sodium, pdo_mysql), Composer 2, Node.js 22.12+, MySQL 8.4.

```bash
git clone https://github.com/kennethandreas/lingkung-gunung-resort.git
cd lingkung-gunung-resort

composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create the databases `lingkung_gunung` and `lingkung_gunung_test`, set the credentials in `.env`, then:

```bash
php artisan migrate --seed                 # schema + official resort data
php artisan inventory:generate             # daily stock for the next 365 days
php artisan make:filament-user             # first staff account
php artisan shield:super-admin --panel=admin

composer run dev                           # app, queue, logs and Vite → http://localhost:8000
```

The admin panel is served under `ADMIN_PATH` (default `/kelola`). Midtrans sandbox keys are optional
for local development — see [`docs/MIDTRANS-SANDBOX.md`](docs/MIDTRANS-SANDBOX.md).

### Useful commands

```bash
php artisan test                    # full test suite (requires MySQL)
php artisan schedule:work           # run scheduled jobs locally
php artisan payments:reconcile      # re-check pending gateway payments
php artisan resort:cek-produksi     # production readiness check
vendor/bin/pint                     # code style
```

## Documentation

| Document | Contents |
|---|---|
| [`docs/Perencanaan Teknis Website Lingkung Gunung.pdf`](docs/Perencanaan%20Teknis%20Website%20Lingkung%20Gunung.pdf) | Technical plan: architecture, modules, data model, payment flow, roadmap |
| [`docs/DEPLOY.md`](docs/DEPLOY.md) | Production deployment (PHP settings, cron, queue) |
| [`docs/DEMO-ORACLE-CLOUD.md`](docs/DEMO-ORACLE-CLOUD.md) | Demo server on Oracle Cloud Free Tier |
| [`docs/SECURITY-CHECKLIST.md`](docs/SECURITY-CHECKLIST.md) | Security controls and go-live checklist |
| [`docs/LAPORAN-AUDIT.md`](docs/LAPORAN-AUDIT.md) | Security & QA audit report |
| [`docs/UAT-CHECKLIST.md`](docs/UAT-CHECKLIST.md) | User acceptance test scenarios |
| [`docs/AKSES-HALAMAN.md`](docs/AKSES-HALAMAN.md) | Route-level access matrix |
| [`docs/AUDIT-UX.md`](docs/AUDIT-UX.md), [`docs/DESIGN-DIRECTION.md`](docs/DESIGN-DIRECTION.md) | UX audit and visual design system |
| [`docs/panduan/`](docs/panduan/) | Illustrated user manual for the resort team (HTML → PDF) |

## Author

**Kenneth Andreas** — Software Engineer  
Responsible for requirements analysis, system design, full-stack implementation, testing and deployment.

GitHub: [@kennethandreas](https://github.com/kennethandreas)

---

© 2026 Kenneth Andreas. Built for Lingkung Gunung Adventure Camp Resort. Resort photos, logo and brand
assets belong to their respective owner.
