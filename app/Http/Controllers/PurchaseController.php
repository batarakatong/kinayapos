<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchaseService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Purchase::class);

        $branchId = $request->attributes->get('branch_id');

        $purchases = Purchase::with(['items.product:id,name,sku', 'supplier:id,name'])
            ->where('branch_id', $branchId)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('supplier_id'), fn ($q, $s) => $q->where('supplier_id', $s))
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($purchases);
    }

    public function store(PurchaseRequest $request)
    {
        $this->authorize('create', Purchase::class);

        $branchId = $request->attributes->get('branch_id');
        $purchase = $this->purchaseService->create($branchId, $request->validated());

        return response()->json($purchase, 201);
    }

    public function show(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $purchase = Purchase::with(['items.product', 'supplier', 'payables.payments'])
            ->where('branch_id', $branchId)
            ->findOrFail($id);

        $this->authorize('view', $purchase);

        return response()->json($purchase);
    }

    public function update(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $purchase = Purchase::where('branch_id', $branchId)->findOrFail($id);

        $this->authorize('update', $purchase);

        $data = $request->validate([
            'status'   => 'sometimes|in:draft,open,paid,partial',
            'due_date' => 'sometimes|nullable|date',
        ]);

        $purchase->update($data);

        return response()->json($purchase->refresh());
    }

    public function destroy(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $purchase = Purchase::where('branch_id', $branchId)->findOrFail($id);

        $this->authorize('delete', $purchase);

        $purchase->delete();

        return response()->json(['message' => 'deleted']);
    }

    /** POST /purchases/{purchase}/pay – record an additional payment on a purchase */
    public function pay(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $purchase = Purchase::where('branch_id', $branchId)->findOrFail($id);

        $this->authorize('update', $purchase);

        $data    = $request->validate(['amount' => 'required|numeric|min:0.01']);
        $updated = $this->purchaseService->pay($purchase, (float) $data['amount']);

        return response()->json($updated);
    }
}
