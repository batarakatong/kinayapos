<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $sales = Sale::with('items.product:id,name,sku', 'payments')
            ->where('branch_id', $branchId)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($sales);
    }

    public function store(SaleRequest $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $userId = $request->user()->id;

        $data = $request->validated();

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
                    $this->stockService->adjust(
                        $branchId,
                        $product->id,
                        -1 * $item['qty'],
                        'sale',
                        'sale',
                        (string) $sale->id,
                        null
                    );
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
