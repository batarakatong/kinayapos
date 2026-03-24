<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function adjust(
        int $branchId,
        int $productId,
        float $qty,
        string $type,
        ?string $refType = null,
        ?string $refId = null,
        ?string $note = null
    ): void {
        DB::transaction(function () use ($branchId, $productId, $qty, $type, $refType, $refId, $note) {
            $stock = Stock::firstOrCreate(
                ['branch_id' => $branchId, 'product_id' => $productId],
                ['qty_on_hand' => 0, 'min_qty' => 0]
            );
            $stock->qty_on_hand += $qty;
            $stock->save();

            StockMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'type' => $type,
                'quantity' => $qty,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'note' => $note,
            ]);
        });
    }
}
