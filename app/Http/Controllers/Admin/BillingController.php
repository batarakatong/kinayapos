<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    // GET /admin/billings
    public function index(Request $request)
    {
        $billings = Billing::with('branch:id,name,code')
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('billing_date')
            ->paginate(20);

        return response()->json($billings);
    }

    // GET /admin/billings/{billing}
    public function show(Billing $billing)
    {
        return response()->json($billing->load('branch:id,name,code'));
    }

    // POST /admin/billings
    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'    => 'required|exists:branches,id',
            'plan'         => 'required|string|max:50',
            'amount'       => 'required|numeric|min:0',
            'billing_date' => 'required|date',
            'due_date'     => 'required|date|after_or_equal:billing_date',
            'notes'        => 'nullable|string',
        ]);

        $data['invoice_number'] = Billing::generateInvoice();
        $data['status']         = 'unpaid';

        $billing = Billing::create($data);
        return response()->json($billing->load('branch:id,name,code'), 201);
    }

    // PUT /admin/billings/{billing}
    public function update(Request $request, Billing $billing)
    {
        $data = $request->validate([
            'plan'         => 'sometimes|string|max:50',
            'amount'       => 'sometimes|numeric|min:0',
            'billing_date' => 'sometimes|date',
            'due_date'     => 'sometimes|date',
            'status'       => 'sometimes|in:unpaid,paid,overdue,cancelled',
            'paid_at'      => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        // Auto-set paid_at saat status di-set ke paid
        if (($data['status'] ?? null) === 'paid' && !isset($data['paid_at'])) {
            $data['paid_at'] = now()->toDateString();
        }

        $billing->update($data);
        return response()->json($billing->fresh('branch:id,name,code'));
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

        return response()->json($billing->fresh('branch:id,name,code'));
    }
}
