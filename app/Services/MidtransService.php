<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Midtrans QRIS / Charge API wrapper.
 *
 * Docs: https://docs.midtrans.com/reference/charge
 *
 * Required .env:
 *   MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxx
 *   MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxx
 *   MIDTRANS_ENVIRONMENT=sandbox|production
 *   MIDTRANS_WEBHOOK_SECRET=   (same as Server Key for Midtrans signature)
 */
class MidtransService
{
    private string $serverKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key');
        $this->baseUrl   = config('services.midtrans.base_url');
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Public API
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Create a QRIS charge at Midtrans.
     *
     * @param  string $orderId    Unique order reference (e.g. "SALE-{uuid}")
     * @param  int    $amount     Amount in IDR (integer, no decimal)
     * @param  array  $items      [['id'=>, 'name'=>, 'price'=>, 'quantity'=>]]
     * @param  array  $customer   ['first_name'=>, 'email'=>, 'phone'=>]
     * @return array{
     *   qr_string: string,
     *   qr_image_url: string,
     *   transaction_id: string,
     *   order_id: string,
     *   status: string,
     *   raw: array
     * }
     */
    public function chargeQris(
        string $orderId,
        int    $amount,
        array  $items = [],
        array  $customer = []
    ): array {
        $payload = [
            'payment_type'       => 'qris',
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $amount,
            ],
            'qris' => [
                'acquirer' => 'gopay',   // gopay | airpay shopee
            ],
        ];

        if (! empty($items)) {
            $payload['item_details'] = $items;
        }

        if (! empty($customer)) {
            $payload['customer_details'] = $customer;
        }

        $response = $this->post('/v2/charge', $payload);

        $body = $response->json();

        if ($response->failed()) {
            Log::error('Midtrans QRIS charge failed', ['body' => $body, 'order_id' => $orderId]);
            throw new \RuntimeException('Midtrans charge failed: ' . ($body['status_message'] ?? 'unknown error'));
        }

        $qrUrl = $body['actions'][0]['url'] ?? null;

        return [
            'qr_string'       => $body['qr_string'] ?? null,
            'qr_image_url'    => $qrUrl,
            'transaction_id'  => $body['transaction_id'] ?? null,
            'order_id'        => $body['order_id'],
            'status'          => $body['transaction_status'] ?? 'pending',
            'raw'             => $body,
        ];
    }

    /**
     * Query the transaction status at Midtrans.
     */
    public function getStatus(string $orderId): array
    {
        $response = $this->get("/v2/{$orderId}/status");
        return $response->json();
    }

    /**
     * Cancel / expire a pending QRIS transaction.
     */
    public function cancel(string $orderId): array
    {
        $response = $this->post("/v2/{$orderId}/cancel", []);
        return $response->json();
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Webhook signature validation
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Validate the incoming Midtrans webhook notification signature.
     *
     * Midtrans signs each notification with:
     *   SHA-512( order_id + status_code + gross_amount + server_key )
     *
     * @param  array $payload  The raw webhook JSON payload
     * @return bool
     */
    public function validateSignature(array $payload): bool
    {
        $expected = hash('sha512',
            ($payload['order_id']     ?? '') .
            ($payload['status_code']  ?? '') .
            ($payload['gross_amount'] ?? '') .
            $this->serverKey
        );

        return hash_equals($expected, $payload['signature_key'] ?? '');
    }

    /**
     * Translate Midtrans transaction_status to our internal payment status.
     */
    public function mapStatus(string $transactionStatus, string $fraudStatus = ''): string
    {
        return match ($transactionStatus) {
            'capture'    => ($fraudStatus === 'accept' || $fraudStatus === '') ? 'paid' : 'failed',
            'settlement' => 'paid',
            'pending'    => 'pending',
            'deny', 'cancel', 'expire', 'failure' => 'failed',
            default      => 'pending',
        };
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Internal HTTP helpers
    // ────────────────────────────────────────────────────────────────────────────

    private function post(string $path, array $payload): Response
    {
        return Http::withBasicAuth($this->serverKey, '')
            ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->post($this->baseUrl . $path, $payload);
    }

    private function get(string $path): Response
    {
        return Http::withBasicAuth($this->serverKey, '')
            ->withHeaders(['Accept' => 'application/json'])
            ->get($this->baseUrl . $path);
    }
}
