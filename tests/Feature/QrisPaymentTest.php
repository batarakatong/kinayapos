<?php

namespace Tests\Feature;

use App\Jobs\CreateQrisCharge;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class QrisPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User   $user;
    private Branch $branch;
    private Sale   $sale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'QBranch', 'code' => 'QB']);

        $this->user = User::factory()->create();
        $this->user->branches()->attach($this->branch->id, [
            'role' => 'branch_admin', 'is_default' => true,
        ]);

        $product = Product::create([
            'uuid'        => Str::uuid(),
            'name'        => 'Widget',
            'sku'         => 'WDG01',
            'price'       => 25000,
            'cost'        => 15000,
            'track_stock' => false,
        ]);

        $this->sale = Sale::create([
            'uuid'      => Str::uuid(),
            'branch_id' => $this->branch->id,
            'status'    => 'draft',
            'total'     => 25000,
            'paid_at'   => null,
        ]);

        SaleItem::create([
            'sale_id'    => $this->sale->id,
            'product_id' => $product->id,
            'qty'        => 1,
            'price'      => 25000,
        ]);
    }

    private function api()
    {
        return $this->actingAs($this->user, 'sanctum')
            ->withHeaders(['X-Branch-Id' => $this->branch->id]);
    }

    /** @test */
    public function create_qris_returns_202_and_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->api()->postJson('/api/payments/qris', [
            'sale_id' => $this->sale->id,
            'amount'  => 25000,
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure(['payment_id', 'order_id', 'status', 'message'])
            ->assertJsonPath('status', 'pending');

        Queue::assertPushed(CreateQrisCharge::class, function ($job) use ($response) {
            return $job->paymentId === $response->json('payment_id')
                && $job->amount === 25000;
        });

        $this->assertDatabaseHas('payments', [
            'id'       => $response->json('payment_id'),
            'method'   => 'qris',
            'status'   => 'pending',
            'provider' => 'midtrans',
        ]);
    }

    /** @test */
    public function create_qris_returns_422_for_already_paid_sale(): void
    {
        Queue::fake();

        $this->sale->update(['status' => 'paid']);

        $this->api()->postJson('/api/payments/qris', [
            'sale_id' => $this->sale->id,
            'amount'  => 25000,
        ])->assertStatus(422);

        Queue::assertNotPushed(CreateQrisCharge::class);
    }

    /** @test */
    public function status_endpoint_returns_payment_details(): void
    {
        $payment = Payment::create([
            'sale_id'      => $this->sale->id,
            'method'       => 'qris',
            'status'       => 'pending',
            'reference_id' => 'SALE-TEST-001',
            'provider'     => 'midtrans',
            'amount'       => 25000,
            'payload'      => [
                'actions' => [
                    ['name' => 'generate-qr-code', 'url' => 'https://qr.midtrans.com/test.png'],
                ],
            ],
        ]);

        $this->api()
            ->getJson("/api/payments/{$payment->id}/status")
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('method', 'qris')
            ->assertJsonPath('qr_image_url', 'https://qr.midtrans.com/test.png');
    }

    /** @test */
    public function status_endpoint_returns_paid_after_webhook_processed(): void
    {
        $payment = Payment::create([
            'sale_id'      => $this->sale->id,
            'method'       => 'qris',
            'status'       => 'paid',
            'reference_id' => 'SALE-TEST-002',
            'provider'     => 'midtrans',
            'amount'       => 25000,
            'paid_at'      => now(),
            'payload'      => [],
        ]);

        $this->api()
            ->getJson("/api/payments/{$payment->id}/status")
            ->assertOk()
            ->assertJsonPath('status', 'paid');
    }

    /** @test */
    public function status_endpoint_forbidden_for_other_branch(): void
    {
        $other = Branch::create(['name' => 'Other', 'code' => 'OTH']);
        $otherUser = User::factory()->create();
        $otherUser->branches()->attach($other->id, ['role' => 'branch_admin', 'is_default' => true]);

        $payment = Payment::create([
            'sale_id'      => $this->sale->id,   // sale belongs to $this->branch
            'method'       => 'qris',
            'status'       => 'pending',
            'reference_id' => 'SALE-TEST-003',
            'provider'     => 'midtrans',
            'amount'       => 25000,
            'payload'      => [],
        ]);

        $this->actingAs($otherUser, 'sanctum')
            ->withHeaders(['X-Branch-Id' => $other->id])
            ->getJson("/api/payments/{$payment->id}/status")
            ->assertForbidden();
    }
}
