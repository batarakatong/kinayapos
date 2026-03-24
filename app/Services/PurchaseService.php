<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * Create a purchase order, update stock for each item, and
     * automatically open a Payable when paid < total.
     *
     * @param  array{
     *     supplier_id: int,
     *     items: array<array{product_id:int, qty:float, price:float}>,
     *     paid?: float,
     *     due_date?: string|null,
     * } $data
     */
    public function create(int $branchId, array $data): Purchase
    {
        return DB::transaction(function () use ($branchId, $data) {
            $items  = collect($data['items']);
            $total  = $items->sum(fn ($i) => $i['price'] * $i['qty']);
            $paid   = (float) ($data['paid'] ?? 0);
            $status = match (true) {
                $paid >= $total => 'paid',
                $paid > 0       => 'partial',
                default         => 'open',
            };

            /** @var Purchase $purchase */
            $purchase = Purchase::create([
                'uuid'        => (string) Str::uuid(),
                'supplier_id' => $data['supplier_id'],
                'branch_id'   => $branchId,
                'total'       => $total,
                'paid'        => $paid,
                'due_date'    => $data['due_date'] ?? null,
                'status'      => $status,
            ]);

            foreach ($items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'qty'         => $item['qty'],
                    'price'       => $item['price'],
                    'total'       => $item['price'] * $item['qty'],
                ]);

                $product = Product::find($item['product_id']);
                if ($product?->track_stock) {
                    $this->stockService->adjust(
                        branchId:  $branchId,
                        productId: $product->id,
                        qty:       $item['qty'],
                        type:      'purchase',
                        refType:   'purchase',
                        refId:     (string) $purchase->id,
                        note:      null,
                    );
                }
            }

            // Auto-create payable for the unpaid balance
            $balance = $total - $paid;
            if ($balance > 0) {
                Payable::create([
                    'uuid'        => (string) Str::uuid(),
                    'supplier_id' => $data['supplier_id'],
                    'branch_id'   => $branchId,
                    'purchase_id' => $purchase->id,
                    'amount'      => $balance,
                    'balance'     => $balance,
                    'status'      => $paid > 0 ? 'partial' : 'open',
                    'due_date'    => $data['due_date'] ?? null,
                    'note'        => "Auto from purchase #{$purchase->id}",
                ]);
            }

            return $purchase->load('items.product:id,name,sku');
        });
    }

    /**
     * Record an additional payment against a purchase and update its linked payable.
     */
    public function pay(Purchase $purchase, float $amount): Purchase
    {
        return DB::transaction(function () use ($purchase, $amount) {
            $purchase->paid += $amount;
            if ($purchase->paid >= $purchase->total) {
                $purchase->paid   = $purchase->total;
                $purchase->status = 'paid';
            } else {
                $purchase->status = 'partial';
            }
            $purchase->save();

            // Sync the linked payable balance
            $payable = Payable::where('purchase_id', $purchase->id)
                ->where('status', '!=', 'paid')
                ->first();

            if ($payable) {
                $payable->balance -= $amount;
                if ($payable->balance <= 0) {
                    $payable->balance = 0;
                    $payable->status  = 'paid';
                } else {
                    $payable->status = 'partial';
                }
                $payable->save();
            }

            return $purchase->refresh();
        });
    }
}
