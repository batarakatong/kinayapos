<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMidtransWebhook;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives webhook notifications from Midtrans.
 *
 * Endpoint: POST /payments/callback/midtrans  (unauthenticated – no Sanctum)
 *
 * Security:
 *   1. Validates Midtrans SHA-512 signature before doing anything.
 *   2. Immediately returns HTTP 200 to Midtrans (prevents retries).
 *   3. Dispatches ProcessMidtransWebhook job to "webhooks" queue for async processing.
 */
class PaymentCallbackController extends Controller
{
    public function __construct(private readonly MidtransService $midtrans) {}

    public function midtrans(Request $request)
    {
        $payload = $request->all();

        // ── 1. Signature validation ──────────────────────────────────────────
        if (! $this->midtrans->validateSignature($payload)) {
            Log::warning('Midtrans webhook: invalid signature', [
                'ip'       => $request->ip(),
                'order_id' => $payload['order_id'] ?? null,
            ]);

            // Return 200 so Midtrans stops retrying, but don't process
            return response()->json(['message' => 'invalid signature'], 200);
        }

        // ── 2. Log receipt ───────────────────────────────────────────────────
        Log::info('Midtrans webhook received', [
            'order_id'           => $payload['order_id'] ?? null,
            'transaction_status' => $payload['transaction_status'] ?? null,
            'fraud_status'       => $payload['fraud_status'] ?? null,
        ]);

        // ── 3. Dispatch to queue and return 200 immediately ──────────────────
        ProcessMidtransWebhook::dispatch($payload)->onQueue('webhooks');

        return response()->json(['message' => 'ok'], 200);
    }
}

