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
 * Dispatched when a QRIS payment is created.
 * Calls Midtrans /charge API and stores the resulting transaction_id + qr_string.
 *
 * Queue: default
 * Retries: 3  (with exponential back-off via backoff())
 * Timeout: 30 s
 */
class CreateQrisCharge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public int   $timeout = 30;

    public function __construct(
        public readonly int    $paymentId,
        public readonly string $orderId,
        public readonly int    $amount,
        public readonly array  $items    = [],
        public readonly array  $customer = [],
    ) {}

    public function backoff(): array
    {
        return [5, 30, 90]; // seconds between retries
    }

    public function handle(MidtransService $midtrans): void
    {
        $payment = Payment::findOrFail($this->paymentId);

        try {
            $result = $midtrans->chargeQris(
                orderId:  $this->orderId,
                amount:   $this->amount,
                items:    $this->items,
                customer: $this->customer,
            );

            $payment->update([
                'reference_id' => $result['transaction_id'],
                'status'       => 'pending',
                'payload'      => $result['raw'],
                // Midtrans QRIS expires in 15 minutes by default
                'expires_at'   => now()->addMinutes(15),
            ]);

            Log::info('QRIS charge created', [
                'payment_id'     => $this->paymentId,
                'transaction_id' => $result['transaction_id'],
                'order_id'       => $result['order_id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('CreateQrisCharge job failed', [
                'payment_id' => $this->paymentId,
                'error'      => $e->getMessage(),
            ]);

            // Mark payment failed after all retries exhausted
            if ($this->attempts() >= $this->tries) {
                $payment->update(['status' => 'failed']);
            }

            throw $e;
        }
    }
}
