; ─────────────────────────────────────────────────────────────────────────────
; Supervisor configuration for Kinaya POS — Horizon + Redis health check
;
; OPTIONAL: Use this file if you also want Supervisor to restart Redis when it
; crashes.  On most production servers Redis is managed separately; this file
; is provided only as a convenience reference.
;
; ─── Deploy workflow ──────────────────────────────────────────────────────────
; After every `git pull` / deployment:
;
;   1. php artisan config:cache
;   2. php artisan route:cache
;   3. php artisan view:cache
;   4. php artisan migrate --force
;   5. php artisan horizon:terminate   ← graceful shutdown; Supervisor restarts it
;
; supervisorctl will restart the [kinaya-horizon] program automatically after
; `horizon:terminate` causes it to exit.
;
; ─── Horizon dashboard access ─────────────────────────────────────────────────
; Gate is defined in app/Providers/HorizonServiceProvider.php (published by
; `php artisan horizon:install`).  Restrict to admin emails:
;
;   Gate::define('viewHorizon', function ($user) {
;       return in_array($user->email, [
;           'admin@kinaya.id',
;       ]);
;   });
;
; ─── Queue priority reference ─────────────────────────────────────────────────
; Queue       | Supervisor in horizon.php  | Purpose
; ------------|---------------------------|----------------------------
; webhooks    | webhooks (min 2, max 6)   | Midtrans webhook notifications
; default     | worker   (min 1, max 4)   | QRIS charge creation jobs
;
; ─────────────────────────────────────────────────────────────────────────────
