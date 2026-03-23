<?php

namespace App\Http\Controllers;

use App\Models\Payable;
use App\Models\PayablePayment;
use Illuminate\Http\Request;

class PayableController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $list = Payable::with('payments')
            ->where('branch_id', $branchId)
            ->orderByDesc('id')
            ->paginate(20);
        return response()->json($list);
    }

    public function store(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $payable = Payable::create([
            ...$data,
            'branch_id' => $branchId,
            'balance' => $data['amount'],
            'status' => 'open',
        ]);

        return response()->json($payable, 201);
    }

    public function show(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $item = Payable::with('payments')->where('branch_id', $branchId)->findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $item = Payable::where('branch_id', $branchId)->findOrFail($id);
        $data = $request->validate([
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);
        $item->update($data);
        return response()->json($item);
    }

    public function destroy(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        Payable::where('branch_id', $branchId)->findOrFail($id)->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function pay(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $payable = Payable::where('branch_id', $branchId)->findOrFail($id);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,transfer,qris',
            'note' => 'nullable|string',
        ]);

        $payment = PayablePayment::create([
            'payable_id' => $payable->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'paid_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        $payable->balance -= $data['amount'];
        if ($payable->balance <= 0) {
            $payable->balance = 0;
            $payable->status = 'paid';
        } elseif ($payable->balance < $payable->amount) {
            $payable->status = 'partial';
        }
        $payable->save();

        return response()->json(['payment' => $payment, 'payable' => $payable]);
    }
}
