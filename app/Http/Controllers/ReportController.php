<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Payable;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\Stock;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Parse a Y-m-d date string into start-of-day / end-of-day Carbon in app timezone.
     * Returns [Carbon $start, Carbon $end] or [null, null] if input is empty.
     */
    private function dayRange(?string $from, ?string $to): array
    {
        $tz    = config('app.timezone'); // Asia/Jakarta
        $start = $from ? Carbon::createFromFormat('Y-m-d', $from, $tz)->startOfDay() : null;
        $end   = $to   ? Carbon::createFromFormat('Y-m-d', $to,   $tz)->endOfDay()   : null;
        return [$start, $end];
    }

    public function sales(ReportRequest $request)
    {
        $branchId      = $request->attributes->get('branch_id');
        [$start, $end] = $this->dayRange($request->query('from'), $request->query('to'));

        $q = Sale::where('branch_id', $branchId)->where('status', 'paid');
        if ($start) $q->where('created_at', '>=', $start);
        if ($end)   $q->where('created_at', '<=', $end);

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
        $branchId      = $request->attributes->get('branch_id');
        [$start, $end] = $this->dayRange($request->query('from'), $request->query('to'));

        $q = Purchase::where('branch_id', $branchId);
        if ($start) $q->where('created_at', '>=', $start);
        if ($end)   $q->where('created_at', '<=', $end);

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

        // due_date is a DATE column — whereDate is fine here (no tz conversion needed)
        $q = Receivable::where('branch_id', $branchId);
        if ($from)   $q->whereDate('due_date', '>=', $from);
        if ($to)     $q->whereDate('due_date', '<=', $to);
        if ($status) $q->where('status', $status);

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

        // due_date is a DATE column — whereDate is fine here
        $q = Payable::where('branch_id', $branchId);
        if ($from)   $q->whereDate('due_date', '>=', $from);
        if ($to)     $q->whereDate('due_date', '<=', $to);
        if ($status) $q->where('status', $status);

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
