<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Payable;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayableTest extends TestCase
{
    use RefreshDatabase;

    private User   $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST3']);
        $this->admin  = User::factory()->create();
        $this->admin->branches()->attach($this->branch->id, ['role' => 'branch_admin', 'is_default' => true]);
    }

    private function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'sanctum')
            ->withHeaders(['X-Branch-Id' => $this->branch->id]);
    }

    /** @test */
    public function can_create_payable_manually(): void
    {
        $supplier = Supplier::create(['name' => 'Sup X']);

        $response = $this->actingAsAdmin()->postJson('/api/payables', [
            'supplier_id' => $supplier->id,
            'amount'      => 500000,
            'due_date'    => now()->addDays(30)->toDateString(),
            'note'        => 'Invoice #001',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'open');
        $response->assertJsonPath('balance', 500000);
    }

    /** @test */
    public function paying_payable_syncs_purchase_status(): void
    {
        $supplier = Supplier::create(['name' => 'Sup Y']);
        $product  = \App\Models\Product::create([
            'uuid' => \Str::uuid(), 'name' => 'Prod D', 'sku' => 'P004',
            'price' => 1000, 'cost' => 800, 'track_stock' => false,
        ]);

        // Create a purchase that auto-generates a payable
        $createPurchase = $this->actingAsAdmin()->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'items'       => [['product_id' => $product->id, 'qty' => 5, 'price' => 1000]],
            'paid'        => 0,
        ]);
        $createPurchase->assertStatus(201);
        $purchaseId = $createPurchase->json('id');

        $payable = Payable::where('purchase_id', $purchaseId)->firstOrFail();

        // Pay the payable – should also update purchase status
        $pay = $this->actingAsAdmin()->postJson("/api/payables/{$payable->id}/pay", [
            'amount' => 5000,
            'method' => 'transfer',
        ]);
        $pay->assertOk();
        $pay->assertJsonPath('payable.status', 'paid');

        $this->assertDatabaseHas('purchases', ['id' => $purchaseId, 'status' => 'paid']);
    }

    /** @test */
    public function payable_list_filters_by_status(): void
    {
        $supplier = Supplier::create(['name' => 'Sup Z']);
        Payable::create([
            'uuid' => \Str::uuid(), 'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id, 'amount' => 1000, 'balance' => 1000, 'status' => 'open',
        ]);
        Payable::create([
            'uuid' => \Str::uuid(), 'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id, 'amount' => 2000, 'balance' => 0, 'status' => 'paid',
        ]);

        $open = $this->actingAsAdmin()->getJson('/api/payables?status=open');
        $open->assertOk();
        $this->assertCount(1, $open->json('data'));

        $paid = $this->actingAsAdmin()->getJson('/api/payables?status=paid');
        $paid->assertOk();
        $this->assertCount(1, $paid->json('data'));
    }
}
