<?php

namespace Tests\Feature;

use App\Jobs\ProcessMidtransWebhook;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $serverKey = 'test-server-key-123';

    protected function setUp(): void
    {
        parent::setUp();

        // Override Midtrans server key for tests
        config(['services.midtrans.server_key' => $this->serverKey]);
    }

    private function makePayload(
        string $orderId,
        string $statusCode    = '200',
        string $grossAmount   = '25000.00',
        string $txStatus      = 'settlement',
        string $fraudStatus   = 'accept',
        ?string $signatureKey = null,
    ): array {
        $sig = $signatureKey ?? hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

        return [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'transaction_status' => $txStatus,
            'fraud_status'       => $fraudStatus,
            'payment_type'       => 'qris',
            'transaction_id'     => 'MT-TX-001',
            'signature_key'      => $sig,
        ];
    }

    private function makeSaleWithPayment(): array
    {
        $branch = Branch::create(['name' => 'WBranch', 'code' => 'WB']);

        $product = Product::create([
            'uuid' => Str::uuid(), 'name' => 'Item', 'sku' => 'IT01',
            'price' => 25000, 'cost' => 15000, 'track_stock' => false,
        ]);

        $sale = Sale::create([
            'uuid'      => Str::uuid(),
            'branch_id' => $branch->id,
            'status'    => 'draft',
            'total'     => 25000,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $product->id,
            'qty' => 1, 'price' => 25000,
        ]);

        $payment = Payment::create([
            'sale_id'      => $sale->id,
            'method'       => 'qris',
            'status'       => 'pending',
            'reference_id' => 'SALE-TEST-WH',
            'provider'     => 'midtrans',
            'amount'       => 25000,
            'payload'      => ['order_id' => 'SALE-TEST-WH'],
        ]);

        return [$sale, $payment];
    }

    /** @test */
    public function valid_webhook_returns_200_and_dispatches_job(): void
    {
        Queue::fake();
        [$sale, $payment] = $this->makeSaleWithPayment();

        $payload = $this->makePayload(orderId: 'SALE-' . $sale->uuid . '-20260325120000');

        $this->postJson('/api/payments/callback/midtrans', $payload)
            ->assertStatus(200)
            ->assertJsonPath('message', 'ok');

        Queue::assertPushed(ProcessMidtransWebhook::class);
    }

    /** @test */
    public function invalid_signature_returns_200_but_does_not_dispatch(): void
    {
        Queue::fake();

        $payload = $this->makePayload(
            orderId:      'SALE-FAKE-001',
            signatureKey: 'bad-signature-here',
        );

        $this->postJson('/api/payments/callback/midtrans', $payload)
            ->assertStatus(200);

        Queue::assertNotPushed(ProcessMidtransWebhook::class);
    }

    /** @test */
    public function webhook_job_updates_payment_and_sale_to_paid(): void
    {
        [$sale, $payment] = $this->makeSaleWithPayment();

        $payload = [
            'order_id'           => 'SALE-' . $sale->uuid . '-20260325120000',
            'status_code'        => '200',
            'gross_amount'       => '25000.00',
            'transaction_status' => 'settlement',
            'fraud_status'       => 'accept',
            'payment_type'       => 'qris',
            'transaction_id'     => 'MT-TX-002',
            'signature_key'      => 'unused-in-job', // job skips signature; controller validates first
        ];

        // Directly run the job (sync) to test its logic
        app()->call([new ProcessMidtransWebhook($payload), 'handle']);

        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('sales', [
            'id'     => $sale->id,
            'status' => 'paid',
        ]);
    }

    /** @test */
    public function webhook_job_is_idempotent_for_already_paid_payment(): void
    {
        [$sale, $payment] = $this->makeSaleWithPayment();

        // Pre-set to paid
        $payment->update(['status' => 'paid', 'paid_at' => now()->subMinutes(5)]);
        $paidAt = $payment->fresh()->paid_at;

        $payload = [
            'order_id'           => 'SALE-' . $sale->uuid . '-20260325120000',
            'status_code'        => '200',
            'gross_amount'       => '25000.00',
            'transaction_status' => 'settlement',
            'fraud_status'       => 'accept',
            'payment_type'       => 'qris',
            'transaction_id'     => 'MT-TX-003',
            'signature_key'      => 'unused-in-job',
        ];

        (app()->call([new ProcessMidtransWebhook($payload), 'handle']));

        // paid_at should not change — idempotent skip
        $this->assertEquals(
            $paidAt->toDateTimeString(),
            $payment->fresh()->paid_at->toDateTimeString(),
        );
    }

    /** @test */
    public function webhook_job_marks_payment_failed_for_expire(): void
    {
        [$sale, $payment] = $this->makeSaleWithPayment();

        $payload = [
            'order_id'           => 'SALE-' . $sale->uuid . '-20260325120000',
            'status_code'        => '202',
            'gross_amount'       => '25000.00',
            'transaction_status' => 'expire',
            'fraud_status'       => 'accept',
            'payment_type'       => 'qris',
            'transaction_id'     => 'MT-TX-004',
            'signature_key'      => 'unused-in-job',
        ];

        app()->call([new ProcessMidtransWebhook($payload), 'handle']);

        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => 'failed',
        ]);
    }
}
