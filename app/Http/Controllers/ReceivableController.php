<?php

namespace App\Http\Controllers;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $list = Receivable::with('payments')
            ->where('branch_id', $branchId)
            ->orderByDesc('id')
            ->paginate(20);
        return response()->json($list);
    }

    public function store(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $receivable = Receivable::create([
            ...$data,
            'branch_id' => $branchId,
            'balance' => $data['amount'],
            'status' => 'open',
        ]);

        return response()->json($receivable, 201);
    }

    public function show(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $item = Receivable::with('payments')->where('branch_id', $branchId)->findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $item = Receivable::where('branch_id', $branchId)->findOrFail($id);
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
        Receivable::where('branch_id', $branchId)->findOrFail($id)->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function pay(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $receivable = Receivable::where('branch_id', $branchId)->findOrFail($id);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,transfer,qris',
            'note' => 'nullable|string',
        ]);

        $payment = ReceivablePayment::create([
            'receivable_id' => $receivable->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'paid_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        $receivable->balance -= $data['amount'];
        if ($receivable->balance <= 0) {
            $receivable->balance = 0;
            $receivable->status = 'paid';
        } elseif ($receivable->balance < $receivable->amount) {
            $receivable->status = 'partial';
        }
        $receivable->save();

        return response()->json(['payment' => $payment, 'receivable' => $receivable]);
    }
}
