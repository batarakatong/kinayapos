<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $from = $request->query('from');
        $to = $request->query('to');
        $q = Sale::where('branch_id', $branchId)->where('status', 'paid');
        if ($from) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('created_at', '<=', $to);
        }
        $total = $q->sum('total');
        $count = $q->count();
        return response()->json(['total' => $total, 'count' => $count]);
    }

    public function stocks(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $stocks = Stock::with('product:id,name,sku')
            ->where('branch_id', $branchId)
            ->get();
        return response()->json($stocks);
    }

    public function purchases(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $from = $request->query('from');
        $to = $request->query('to');
        $q = Purchase::where('branch_id', $branchId);
        if ($from) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('created_at', '<=', $to);
        }
        $total = $q->sum('total');
        $count = $q->count();
        return response()->json(['total' => $total, 'count' => $count]);
    }
}
