# KINAYA POS Backend (Laravel 10)

## Quick start
1. Copy `.env.example` to `.env` and set DB/Redis/Midtrans keys (`MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY` optional for QRIS).
2. Install deps (already in vendor). If vendor missing: `composer install`.
3. Run migrations & seed admin: `php artisan migrate --seed`.
4. Serve API: `php artisan serve` or set up Nginx/Apache/FPM.
5. Queue worker: `php artisan queue:work` (or Horizon + Supervisor for prod).
6. Default admin from seeder: email `admin@kinaya.test`, password `password`.

## Key migrations
- `2026_03_23_100000_create_pos_core_tables.php`: branches, products, stocks, sales, payments, purchases, receivables/payables, sync_outbox, multi-branch pivot.

## Next steps
- Set env `APP_URL`, `SANCTUM_STATEFUL_DOMAINS` if using cookie auth (token flow works without).
- Plug real Midtrans/Xendit integration in `PaymentController::createQris` and signature verification in `PaymentCallbackController`.
- Harden with rate limiting, logging, and add exports (PDF/XLSX) if needed.

## Notes
- All transactional tables include `branch_id` to enforce multi-cabang separation.
- UUID columns (`uuid`) provided for offline sync/idempotency.
- Auth uses Sanctum personal access tokens (see `AuthController@login`).
