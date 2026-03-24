<?php

namespace Tests\Unit;

use App\Services\MidtransService;
use Tests\TestCase;

class MidtransServiceTest extends TestCase
{
    private MidtransService $service;
    private string $serverKey = 'unit-test-server-key';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key'    => $this->serverKey,
            'services.midtrans.client_key'    => 'client-key',
            'services.midtrans.environment'   => 'sandbox',
            'services.midtrans.base_url'      => 'https://api.sandbox.midtrans.com',
            'services.midtrans.snap_url'      => 'https://app.sandbox.midtrans.com/snap/v1',
            'services.midtrans.webhook_secret' => '',
        ]);

        $this->service = new MidtransService();
    }

    // ─── validateSignature ────────────────────────────────────────────────────

    /** @test */
    public function valid_signature_passes(): void
    {
        $orderId      = 'SALE-UUID-001';
        $statusCode   = '200';
        $grossAmount  = '50000.00';
        $signature    = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

        $payload = [
            'order_id'     => $orderId,
            'status_code'  => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
        ];

        $this->assertTrue($this->service->validateSignature($payload));
    }

    /** @test */
    public function tampered_signature_fails(): void
    {
        $payload = [
            'order_id'      => 'SALE-UUID-001',
            'status_code'   => '200',
            'gross_amount'  => '50000.00',
            'signature_key' => 'bad-signature',
        ];

        $this->assertFalse($this->service->validateSignature($payload));
    }

    /** @test */
    public function missing_signature_field_fails(): void
    {
        $payload = [
            'order_id'     => 'SALE-UUID-001',
            'status_code'  => '200',
            'gross_amount' => '50000.00',
            // signature_key intentionally missing
        ];

        $this->assertFalse($this->service->validateSignature($payload));
    }

    // ─── mapStatus ────────────────────────────────────────────────────────────

    /** @test */
    public function maps_settlement_accept_to_paid(): void
    {
        $this->assertSame('paid', $this->service->mapStatus('settlement', 'accept'));
    }

    /** @test */
    public function maps_capture_accept_to_paid(): void
    {
        $this->assertSame('paid', $this->service->mapStatus('capture', 'accept'));
    }

    /** @test */
    public function maps_capture_challenge_to_pending(): void
    {
        $this->assertSame('pending', $this->service->mapStatus('capture', 'challenge'));
    }

    /** @test */
    public function maps_pending_to_pending(): void
    {
        $this->assertSame('pending', $this->service->mapStatus('pending', 'accept'));
    }

    /** @test */
    public function maps_deny_to_failed(): void
    {
        $this->assertSame('failed', $this->service->mapStatus('deny', 'deny'));
    }

    /** @test */
    public function maps_cancel_to_failed(): void
    {
        $this->assertSame('failed', $this->service->mapStatus('cancel', 'accept'));
    }

    /** @test */
    public function maps_expire_to_failed(): void
    {
        $this->assertSame('failed', $this->service->mapStatus('expire', 'accept'));
    }

    /** @test */
    public function maps_failure_to_failed(): void
    {
        $this->assertSame('failed', $this->service->mapStatus('failure', 'accept'));
    }

    /** @test */
    public function unknown_status_defaults_to_pending(): void
    {
        $this->assertSame('pending', $this->service->mapStatus('unknown_state', 'accept'));
    }
}
