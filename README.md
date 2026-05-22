# DajPrezent.pl

Multi-tenant SaaS do tworzenia list wymarzonych prezentów oraz stron ślubnych.
Pełne założenia produktowe i pakiety cenowe: [PLAN.md](PLAN.md).

## Stack

- **Backend:** Laravel 11 (PHP 8.3+)
- **DB:** MySQL 8 + Redis (cache / queue / session)
- **UI:** Blade + Tailwind + Alpine.js + HTMX (SSR)
- **Master admin:** Filament v3
- **Tests:** Pest 3
- **Static analysis:** Larastan (PHPStan)
- **Style:** Laravel Pint

## Wymagania lokalne

- PHP ≥ 8.3 z `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`, `openssl`, `redis`
- Composer 2.x
- Node 20+ / npm
- MySQL 8 + Redis 7 (lokalnie lub Docker)

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
# ustaw DB w .env, potem:
php artisan migrate --seed
npm install && npm run dev
php artisan serve
```

Master admin: `/admin` (Filament). Publiczna lista: `/{slug}`.

## Skrypty

```bash
composer test       # Pest
composer lint       # Pint (PSR-12 + Laravel preset)
composer stan       # PHPStan/Larastan (level 6)
```

## Architektura

Szczegóły w [PLAN.md](PLAN.md). W skrócie: single-DB multi-tenancy z kolumną
`tenant_id` + global scope; oddzielne moduły `app/Domain/{Tenancy, Billing,
Invoicing, Wishlist, Wedding}`.

## Licencja

Proprietary. Wszystkie prawa zastrzeżone.
