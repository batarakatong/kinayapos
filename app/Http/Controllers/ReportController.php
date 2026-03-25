<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Payable;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\Stock;

class ReportController extends Controller
{
    public function sales(ReportRequest $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $from     = $request->query('from');
        $to       = $request->query('to');

        $q = Sale::where('branch_id', $branchId)->where('status', 'paid');
        if ($from) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('created_at', '<=', $to);
        }

        return response()->json([
            'total' => $q->sum('total'),
            'count' => $q->count(),
        ]);
    }

    public function stocks(ReportRequest $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $stocks   = Stock::with('product:id,name,sku')
            ->where('branch_id', $branchId)
            ->get()
            ->map(fn ($s) => [
                'product_id'  => $s->product_id,
                'name'        => $s->product->name,
                'sku'         => $s->product->sku,
                'qty_on_hand' => $s->qty_on_hand,
                'min_qty'     => $s->min_qty,
                'low_stock'   => $s->qty_on_hand < $s->min_qty,
            ]);

        return response()->json($stocks);
    }

    public function purchases(ReportRequest $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $from     = $request->query('from');
        $to       = $request->query('to');

        $q = Purchase::where('branch_id', $branchId);
        if ($from) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('created_at', '<=', $to);
        }

        return response()->json([
            'total' => $q->sum('total'),
            'paid'  => $q->sum('paid'),
            'count' => $q->count(),
        ]);
    }

    /** GET /reports/receivables?from=&to=&status= */
    public function receivables(ReportRequest $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $from     = $request->query('from');
        $to       = $request->query('to');
        $status   = $request->query('status');

        $q = Receivable::where('branch_id', $branchId);
        if ($from) {
            $q->whereDate('due_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('due_date', '<=', $to);
        }
        if ($status) {
            $q->where('status', $status);
        }

        return response()->json([
            'total_amount'  => $q->sum('amount'),
            'total_balance' => $q->sum('balance'),
            'count'         => $q->count(),
            'by_status'     => (clone $q)
                ->selectRaw('status, COUNT(*) as count, SUM(balance) as balance')
                ->groupBy('status')
                ->get(),
        ]);
    }

    /** GET /reports/payables?from=&to=&status= */
    public function payables(ReportRequest $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $from     = $request->query('from');
        $to       = $request->query('to');
        $status   = $request->query('status');

        $q = Payable::where('branch_id', $branchId);
        if ($from) {
            $q->whereDate('due_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('due_date', '<=', $to);
        }
        if ($status) {
            $q->where('status', $status);
        }

        return response()->json([
            'total_amount'  => $q->sum('amount'),
            'total_balance' => $q->sum('balance'),
            'count'         => $q->count(),
            'by_status'     => (clone $q)
                ->selectRaw('status, COUNT(*) as count, SUM(balance) as balance')
                ->groupBy('status')
                ->get(),
        ]);
    }
}
