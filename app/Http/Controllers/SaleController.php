<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $sales = Sale::with('items.product:id,name,sku', 'payments')
            ->where('branch_id', $branchId)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $userId = $request->user()->id;

        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,transfer,qris,other',
            'status' => 'nullable|in:draft,pending,paid',
        ]);

        $sale = DB::transaction(function () use ($data, $branchId, $userId) {
            $items = collect($data['items']);
            $subtotal = $items->sum(fn ($i) => ($i['price'] * $i['qty']) - ($i['discount'] ?? 0));
            $discount = $data['discount'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $total = $subtotal - $discount + $tax;

            $sale = Sale::create([
                'uuid' => (string) Str::uuid(),
                'branch_id' => $branchId,
                'user_id' => $userId,
                'customer_id' => $data['customer_id'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'status' => $data['status'] ?? 'pending',
                'payment_method' => $data['payment_method'] ?? null,
            ]);

            foreach ($items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'discount' => $item['discount'] ?? 0,
                    'tax_amount' => 0,
                    'total' => ($item['price'] * $item['qty']) - ($item['discount'] ?? 0),
                ]);

                // deduct stock if track_stock
                $product = Product::find($item['product_id']);
                if ($product->track_stock) {
                    $stock = Stock::firstOrCreate(
                        ['branch_id' => $branchId, 'product_id' => $product->id],
                        ['qty_on_hand' => 0, 'min_qty' => 0]
                    );
                    $stock->decrement('qty_on_hand', $item['qty']);

                    StockMovement::create([
                        'branch_id' => $branchId,
                        'product_id' => $product->id,
                        'type' => 'sale',
                        'quantity' => -1 * $item['qty'],
                        'ref_type' => 'sale',
                        'ref_id' => $sale->id,
                        'note' => null,
                    ]);
                }
            }

            return $sale->load('items');
        });

        return response()->json($sale, 201);
    }

    public function show(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $sale = Sale::with('items.product', 'payments')
            ->where('branch_id', $branchId)
            ->findOrFail($id);

        return response()->json($sale);
    }

    public function update(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $sale = Sale::where('branch_id', $branchId)->findOrFail($id);
        $data = $request->validate([
            'status' => 'sometimes|in:draft,pending,paid,failed,void',
        ]);
        $sale->update($data);
        return response()->json($sale);
    }

    public function destroy(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $sale = Sale::where('branch_id', $branchId)->findOrFail($id);
        $sale->delete();
        return response()->json(['message' => 'deleted']);
    }
}
