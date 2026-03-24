<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Sale;
use App\Services\MidtransService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dispatched by the Midtrans webhook controller after signature validation.
 * Updates Payment + Sale status atomically.
 *
 * Queue: webhooks  (isolated, high-priority)
 * Retries: 5
 * Unique per payment: prevents duplicate processing
 */
class ProcessMidtransWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $timeout = 15;

    public function __construct(public readonly array $payload) {}

    public function backoff(): array
    {
        return [2, 5, 15, 60, 300];
    }

    public function handle(MidtransService $midtrans): void
    {
        $payload = $this->payload;
        $orderId = $payload['order_id'] ?? null;

        if (! $orderId) {
            Log::warning('ProcessMidtransWebhook: missing order_id', $payload);
            return;
        }

        $payment = Payment::where('reference_id', $orderId)
            ->orWhere(fn ($q) => $q->whereJsonContains('payload->order_id', $orderId))
            ->first();

        if (! $payment) {
            // Try matching by the original reference we stored before Midtrans responded
            $payment = Payment::whereJsonContains('payload->order_id', $orderId)->first();
        }

        if (! $payment) {
            Log::warning('ProcessMidtransWebhook: payment not found', ['order_id' => $orderId]);
            return;
        }

        // Idempotency: skip if already in terminal state
        if (in_array($payment->status, ['paid', 'failed'])) {
            Log::info('ProcessMidtransWebhook: already terminal, skipping', [
                'payment_id' => $payment->id,
                'status'     => $payment->status,
            ]);
            return;
        }

        $newStatus = $midtrans->mapStatus(
            $payload['transaction_status'] ?? '',
            $payload['fraud_status']       ?? ''
        );

        DB::transaction(function () use ($payment, $newStatus, $payload) {
            $payment->update([
                'status'  => $newStatus,
                'payload' => $payload,
                'paid_at' => $newStatus === 'paid' ? now() : null,
            ]);

            $sale = $payment->sale;
            if ($sale) {
                $saleStatus = match ($newStatus) {
                    'paid'   => 'paid',
                    'failed' => 'failed',
                    default  => $sale->status,
                };

                $sale->update([
                    'status'         => $saleStatus,
                    'payment_method' => $saleStatus === 'paid' ? 'qris' : $sale->payment_method,
                    'paid_at'        => $saleStatus === 'paid' ? now() : null,
                ]);
            }
        });

        Log::info('ProcessMidtransWebhook: processed', [
            'payment_id' => $payment->id,
            'status'     => $newStatus,
            'order_id'   => $orderId,
        ]);
    }
}
