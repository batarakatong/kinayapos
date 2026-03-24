<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierCustomerTest extends TestCase
{
    use RefreshDatabase;

    private User   $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST4']);
        $this->admin  = User::factory()->create();
        $this->admin->branches()->attach($this->branch->id, ['role' => 'branch_admin', 'is_default' => true]);
    }

    private function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'sanctum')
            ->withHeaders(['X-Branch-Id' => $this->branch->id]);
    }

    // ── Supplier ────────────────────────────────────────────────────────────

    /** @test */
    public function can_crud_supplier(): void
    {
        $create = $this->actingAsAdmin()->postJson('/api/suppliers', [
            'name'  => 'Supplier Baru',
            'phone' => '081200000000',
            'email' => 'baru@example.com',
        ]);
        $create->assertStatus(201);
        $id = $create->json('id');

        $this->actingAsAdmin()->getJson("/api/suppliers/{$id}")->assertOk();

        $update = $this->actingAsAdmin()->putJson("/api/suppliers/{$id}", ['name' => 'Supplier Diupdate']);
        $update->assertOk();
        $update->assertJsonPath('name', 'Supplier Diupdate');

        $this->actingAsAdmin()->deleteJson("/api/suppliers/{$id}")->assertOk();
        $this->assertDatabaseMissing('suppliers', ['id' => $id]);
    }

    /** @test */
    public function supplier_list_supports_search(): void
    {
        Supplier::create(['name' => 'Alpha Supplier']);
        Supplier::create(['name' => 'Beta Supplier']);

        $result = $this->actingAsAdmin()->getJson('/api/suppliers?q=Alpha');
        $result->assertOk();
        $this->assertCount(1, $result->json('data'));
        $this->assertStringContainsString('Alpha', $result->json('data.0.name'));
    }

    // ── Customer ─────────────────────────────────────────────────────────────

    /** @test */
    public function can_crud_customer(): void
    {
        $create = $this->actingAsAdmin()->postJson('/api/customers', [
            'name'  => 'Customer Baru',
            'phone' => '08133000001',
        ]);
        $create->assertStatus(201);
        $id = $create->json('id');

        $this->actingAsAdmin()->getJson("/api/customers/{$id}")->assertOk();

        $update = $this->actingAsAdmin()->putJson("/api/customers/{$id}", ['name' => 'Customer Updated']);
        $update->assertOk();
        $update->assertJsonPath('name', 'Customer Updated');

        $this->actingAsAdmin()->deleteJson("/api/customers/{$id}")->assertOk();
        $this->assertDatabaseMissing('customers', ['id' => $id]);
    }

    /** @test */
    public function global_customer_visible_in_all_branches(): void
    {
        $otherBranch = Branch::create(['name' => 'Other', 'code' => 'OTH']);
        Customer::create(['name' => 'Global Cust', 'branch_id' => null]);
        Customer::create(['name' => 'Branch Cust', 'branch_id' => $otherBranch->id]);

        $list = $this->actingAsAdmin()->getJson('/api/customers');
        $list->assertOk();

        $names = collect($list->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Global Cust'));
        $this->assertFalse($names->contains('Branch Cust'));
    }
}
