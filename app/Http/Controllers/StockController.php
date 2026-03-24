<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustRequest;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $stocks = Stock::with('product:id,name,sku,barcode')
            ->where('branch_id', $branchId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($stocks);
    }

    public function adjust(StockAdjustRequest $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $data = $request->validated();

        $this->stockService->adjust(
            $branchId,
            $data['product_id'],
            $data['quantity'],
            $data['type'],
            'manual_adjust',
            null,
            $data['note'] ?? null
        );

        return response()->json(['message' => 'stock adjusted']);
    }
}
