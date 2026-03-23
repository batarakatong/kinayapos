<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $purchases = Purchase::with('items.product:id,name,sku')
            ->where('branch_id', $branchId)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');

        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'paid' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        $purchase = DB::transaction(function () use ($data, $branchId) {
            $items = collect($data['items']);
            $total = $items->sum(fn ($i) => $i['price'] * $i['qty']);

            $purchase = Purchase::create([
                'uuid' => (string) Str::uuid(),
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $branchId,
                'total' => $total,
                'paid' => $data['paid'] ?? 0,
                'due_date' => $data['due_date'] ?? null,
                'status' => ($data['paid'] ?? 0) >= $total ? 'paid' : 'open',
            ]);

            foreach ($items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['qty'],
                ]);

                $product = Product::find($item['product_id']);
                if ($product->track_stock) {
                    $stock = Stock::firstOrCreate(
                        ['branch_id' => $branchId, 'product_id' => $product->id],
                        ['qty_on_hand' => 0, 'min_qty' => 0]
                    );
                    $stock->increment('qty_on_hand', $item['qty']);

                    StockMovement::create([
                        'branch_id' => $branchId,
                        'product_id' => $product->id,
                        'type' => 'purchase',
                        'quantity' => $item['qty'],
                        'ref_type' => 'purchase',
                        'ref_id' => $purchase->id,
                        'note' => null,
                    ]);
                }
            }

            return $purchase->load('items');
        });

        return response()->json($purchase, 201);
    }

    public function show(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $purchase = Purchase::with('items.product')
            ->where('branch_id', $branchId)
            ->findOrFail($id);
        return response()->json($purchase);
    }

    public function update(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $purchase = Purchase::where('branch_id', $branchId)->findOrFail($id);
        $data = $request->validate([
            'status' => 'sometimes|in:draft,open,paid,partial',
        ]);
        $purchase->update($data);
        return response()->json($purchase);
    }

    public function destroy(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $purchase = Purchase::where('branch_id', $branchId)->findOrFail($id);
        $purchase->delete();
        return response()->json(['message' => 'deleted']);
    }
}
