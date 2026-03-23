<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $stocks = Stock::with('product:id,name,sku,barcode')
            ->where('branch_id', $branchId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($stocks);
    }

    public function adjust(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric',
            'type' => 'required|in:in,out,adjust',
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($branchId, $data) {
            $stock = Stock::firstOrCreate(
                ['branch_id' => $branchId, 'product_id' => $data['product_id']],
                ['qty_on_hand' => 0, 'min_qty' => 0]
            );

            $stock->qty_on_hand = $stock->qty_on_hand + $data['quantity'];
            $stock->save();

            StockMovement::create([
                'branch_id' => $branchId,
                'product_id' => $data['product_id'],
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'ref_type' => 'manual_adjust',
                'ref_id' => null,
                'note' => $data['note'] ?? null,
            ]);
        });

        return response()->json(['message' => 'stock adjusted']);
    }
}
