<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private User   $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Test Branch', 'code' => 'TST',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->branches()->attach($this->branch->id, [
            'role'       => 'branch_admin',
            'is_default' => true,
        ]);
    }

    private function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'sanctum')
            ->withHeaders(['X-Branch-Id' => $this->branch->id]);
    }

    /** @test */
    public function can_create_purchase_and_auto_creates_payable(): void
    {
        $supplier = Supplier::create(['name' => 'Sup A']);
        $product  = Product::create([
            'uuid' => \Str::uuid(), 'name' => 'Prod A', 'sku' => 'P001',
            'price' => 1000, 'cost' => 800, 'track_stock' => true,
        ]);

        $response = $this->actingAsAdmin()->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'items'       => [
                ['product_id' => $product->id, 'qty' => 10, 'price' => 800],
            ],
            'paid'     => 5000,
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('purchases', ['supplier_id' => $supplier->id, 'total' => 8000, 'paid' => 5000, 'status' => 'partial']);
        $this->assertDatabaseHas('payables', ['purchase_id' => $response->json('id'), 'balance' => 3000, 'status' => 'partial']);
        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'branch_id' => $this->branch->id, 'qty_on_hand' => 10]);
    }

    /** @test */
    public function can_pay_purchase_instalment(): void
    {
        $supplier = Supplier::create(['name' => 'Sup B']);
        $product  = Product::create([
            'uuid' => \Str::uuid(), 'name' => 'Prod B', 'sku' => 'P002',
            'price' => 2000, 'cost' => 1500, 'track_stock' => false,
        ]);

        $create = $this->actingAsAdmin()->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'items'       => [['product_id' => $product->id, 'qty' => 5, 'price' => 2000]],
            'paid'        => 0,
        ]);
        $create->assertStatus(201);
        $purchaseId = $create->json('id');

        $pay = $this->actingAsAdmin()->postJson("/api/purchases/{$purchaseId}/pay", ['amount' => 5000]);
        $pay->assertOk();
        $pay->assertJsonPath('status', 'partial');

        $payFull = $this->actingAsAdmin()->postJson("/api/purchases/{$purchaseId}/pay", ['amount' => 5000]);
        $payFull->assertOk();
        $payFull->assertJsonPath('status', 'paid');
    }

    /** @test */
    public function purchase_list_respects_branch(): void
    {
        $supplier = Supplier::create(['name' => 'Sup C']);
        $product  = Product::create([
            'uuid' => \Str::uuid(), 'name' => 'Prod C', 'sku' => 'P003',
            'price' => 500, 'cost' => 400, 'track_stock' => false,
        ]);

        $this->actingAsAdmin()->postJson('/api/purchases', [
            'supplier_id' => $supplier->id,
            'items'       => [['product_id' => $product->id, 'qty' => 2, 'price' => 500]],
        ]);

        $list = $this->actingAsAdmin()->getJson('/api/purchases');
        $list->assertOk();
        $list->assertJsonPath('data.0.branch_id', $this->branch->id);
    }
}
