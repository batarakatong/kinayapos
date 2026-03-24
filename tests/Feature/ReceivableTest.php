<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivableTest extends TestCase
{
    use RefreshDatabase;

    private User   $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TST2']);
        $this->admin  = User::factory()->create();
        $this->admin->branches()->attach($this->branch->id, ['role' => 'branch_admin', 'is_default' => true]);
    }

    private function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'sanctum')
            ->withHeaders(['X-Branch-Id' => $this->branch->id]);
    }

    /** @test */
    public function can_create_receivable(): void
    {
        $customer = Customer::create(['name' => 'Toko ABC', 'branch_id' => null]);

        $response = $this->actingAsAdmin()->postJson('/api/receivables', [
            'customer_id' => $customer->id,
            'amount'      => 150000,
            'due_date'    => now()->addDays(14)->toDateString(),
            'note'        => 'Pelunasan minggu depan',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'open');
        $response->assertJsonPath('balance', 150000);
    }

    /** @test */
    public function can_pay_receivable_in_instalments(): void
    {
        $customer   = Customer::create(['name' => 'Toko DEF', 'branch_id' => null]);
        $receivable = Receivable::create([
            'uuid'        => \Str::uuid(),
            'customer_id' => $customer->id,
            'branch_id'   => $this->branch->id,
            'amount'      => 300000,
            'balance'     => 300000,
            'status'      => 'open',
        ]);

        // First instalment
        $pay1 = $this->actingAsAdmin()->postJson("/api/receivables/{$receivable->id}/pay", [
            'amount' => 100000,
            'method' => 'cash',
        ]);
        $pay1->assertOk();
        $pay1->assertJsonPath('receivable.status', 'partial');
        $pay1->assertJsonPath('receivable.balance', 200000);

        // Second instalment – full payment
        $pay2 = $this->actingAsAdmin()->postJson("/api/receivables/{$receivable->id}/pay", [
            'amount' => 200000,
            'method' => 'transfer',
        ]);
        $pay2->assertOk();
        $pay2->assertJsonPath('receivable.status', 'paid');
        $pay2->assertJsonPath('receivable.balance', 0);
    }

    /** @test */
    public function cannot_pay_already_paid_receivable(): void
    {
        $customer   = Customer::create(['name' => 'Toko GHI', 'branch_id' => null]);
        $receivable = Receivable::create([
            'uuid'        => \Str::uuid(),
            'customer_id' => $customer->id,
            'branch_id'   => $this->branch->id,
            'amount'      => 50000,
            'balance'     => 0,
            'status'      => 'paid',
        ]);

        $response = $this->actingAsAdmin()->postJson("/api/receivables/{$receivable->id}/pay", [
            'amount' => 1000,
            'method' => 'cash',
        ]);

        $response->assertStatus(403);
    }
}
