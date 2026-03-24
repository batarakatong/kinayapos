<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceivablePayRequest;
use App\Http\Requests\ReceivableRequest;
use App\Models\Receivable;
use App\Services\ReceivableService;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    public function __construct(private readonly ReceivableService $receivableService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Receivable::class);

        $branchId = $request->attributes->get('branch_id');

        $list = Receivable::with(['payments', 'customer:id,name,phone'])
            ->where('branch_id', $branchId)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('customer_id'), fn ($q, $c) => $q->where('customer_id', $c))
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('due_date', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->whereDate('due_date', '<=', $d))
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($list);
    }

    public function store(ReceivableRequest $request)
    {
        $this->authorize('create', Receivable::class);

        $branchId = $request->attributes->get('branch_id');
        $data     = $request->validated();

        $receivable = Receivable::create([
            ...$data,
            'branch_id' => $branchId,
            'balance'   => $data['amount'],
            'status'    => 'open',
        ]);

        return response()->json($receivable, 201);
    }

    public function show(Request $request, $id)
    {
        $branchId   = $request->attributes->get('branch_id');
        $receivable = Receivable::with(['payments', 'customer:id,name,phone'])
            ->where('branch_id', $branchId)
            ->findOrFail($id);

        $this->authorize('view', $receivable);

        return response()->json($receivable);
    }

    public function update(Request $request, $id)
    {
        $branchId   = $request->attributes->get('branch_id');
        $receivable = Receivable::where('branch_id', $branchId)->findOrFail($id);

        $this->authorize('update', $receivable);

        $data = $request->validate([
            'due_date' => 'nullable|date',
            'note'     => 'nullable|string',
        ]);

        $receivable->update($data);

        return response()->json($receivable->refresh());
    }

    public function destroy(Request $request, $id)
    {
        $branchId   = $request->attributes->get('branch_id');
        $receivable = Receivable::where('branch_id', $branchId)->findOrFail($id);

        $this->authorize('delete', $receivable);

        $receivable->delete();

        return response()->json(['message' => 'deleted']);
    }

    /** POST /receivables/{receivable}/pay */
    public function pay(ReceivablePayRequest $request, $id)
    {
        $branchId   = $request->attributes->get('branch_id');
        $receivable = Receivable::where('branch_id', $branchId)->findOrFail($id);

        $this->authorize('pay', $receivable);

        $result = $this->receivableService->pay($receivable, $request->validated());

        return response()->json($result);
    }
}
