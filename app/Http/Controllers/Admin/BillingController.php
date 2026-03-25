<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\BillingPackage;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    // GET /admin/billings
    public function index(Request $request)
    {
        $billings = Billing::with('branch:id,name,code', 'package:id,name,slug')
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->package_id, fn($q) => $q->where('package_id', $request->package_id))
            ->orderByDesc('billing_date')
            ->paginate(20);

        return response()->json($billings);
    }

    // GET /admin/billings/{billing}
    public function show(Billing $billing)
    {
        return response()->json($billing->load('branch:id,name,code', 'package'));
    }

    // POST /admin/billings
    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'      => 'required|exists:branches,id',
            'package_id'     => 'nullable|exists:billing_packages,id',
            'plan'           => 'required|in:monthly,quarterly,yearly,lifetime,custom',
            'amount'         => 'nullable|numeric|min:0',
            'billing_date'   => 'required|date',
            'period_start'   => 'nullable|date',
            'period_end'     => 'nullable|date',
            'due_date'       => 'required|date|after_or_equal:billing_date',
            'notes'          => 'nullable|string',
            'payment_method' => 'nullable|string|max:50',
        ]);

        // Jika package dipilih dan amount tidak diisi, ambil dari package
        if (!isset($data['amount']) && isset($data['package_id'])) {
            $pkg = BillingPackage::find($data['package_id']);
            $data['amount'] = $pkg ? $pkg->getPriceForPlan($data['plan']) : 0;
        }
        $data['amount'] = $data['amount'] ?? 0;

        // Auto hitung period jika tidak diisi
        if (empty($data['period_start'])) {
            $data['period_start'] = $data['billing_date'];
        }
        if (empty($data['period_end']) && $data['plan'] !== 'lifetime') {
            $start = \Carbon\Carbon::parse($data['period_start']);
            $data['period_end'] = match ($data['plan']) {
                'quarterly' => $start->addMonths(3)->toDateString(),
                'yearly'    => $start->addYear()->toDateString(),
                default     => $start->addMonth()->toDateString(),
            };
        }

        $data['invoice_number'] = Billing::generateInvoice();
        $data['status']         = 'unpaid';

        $billing = Billing::create($data);
        return response()->json($billing->load('branch:id,name,code', 'package:id,name,slug'), 201);
    }

    // PUT /admin/billings/{billing}
    public function update(Request $request, Billing $billing)
    {
        $data = $request->validate([
            'package_id'     => 'nullable|exists:billing_packages,id',
            'plan'           => 'sometimes|in:monthly,quarterly,yearly,lifetime,custom',
            'amount'         => 'sometimes|numeric|min:0',
            'billing_date'   => 'sometimes|date',
            'period_start'   => 'nullable|date',
            'period_end'     => 'nullable|date',
            'due_date'       => 'sometimes|date',
            'status'         => 'sometimes|in:unpaid,paid,overdue,cancelled',
            'paid_at'        => 'nullable|date',
            'notes'          => 'nullable|string',
            'payment_method' => 'nullable|string|max:50',
        ]);

        if (($data['status'] ?? null) === 'paid' && !isset($data['paid_at'])) {
            $data['paid_at'] = now()->toDateString();
        }

        $billing->update($data);
        return response()->json($billing->fresh()->load('branch:id,name,code', 'package:id,name,slug'));
    }

    // DELETE /admin/billings/{billing}
    public function destroy(Billing $billing)
    {
        $billing->delete();
        return response()->json(['message' => 'Billing deleted']);
    }

    // PATCH /admin/billings/{billing}/pay — tandai lunas
    public function markPaid(Billing $billing)
    {
        $billing->update([
            'status'  => 'paid',
            'paid_at' => now()->toDateString(),
        ]);

        return response()->json($billing->fresh()->load('branch:id,name,code', 'package:id,name,slug'));
    }
}
