# Shop

Production-oriented ecommerce monolith built with Laravel 12 + Vue 3.

## Tech stack

- PHP 8.4
- Laravel 12 (API + monolith web shell)
- Vue 3 + Vite + TypeScript + Pinia + Vue Router
- MySQL 8.4
- Redis 7

## Quick start

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Application is available at `http://127.0.0.1:8000`.

Set admin bootstrap credentials in `.env` before seed:

```bash
SHOP_ADMIN_EMAIL=admin@shop.local
SHOP_ADMIN_PASSWORD=change-this-password
```

## Useful commands

```bash
php artisan test
composer run lint
composer run analyse
npm run lint
npm run test
npm run type-check
npm run build
php artisan queue:work
php artisan app:healthcheck
```

## API scope

- `/api/v1/auth/*`
- `/api/v1/catalog/*`
- `/api/v1/cart/*`
- `/api/v1/checkout/*`
- `/api/v1/orders/me`
- `/api/v1/admin/*`
- `/api/v1/webhooks/*`

## Environments

- `.env.example` for local
- `.env.stage.example` for stage
- `.env.prod.example` for production

## Deployment

Use script:

```bash
./deploy/deploy.sh
```

It executes install/build, safe migration sequence, cache warmup, queue restart and smoke checks.
