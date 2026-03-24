<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayablePayRequest;
use App\Http\Requests\PayableRequest;
use App\Models\Payable;
use App\Services\PayableService;
use Illuminate\Http\Request;

class PayableController extends Controller
{
    public function __construct(private readonly PayableService $payableService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Payable::class);

        $branchId = $request->attributes->get('branch_id');

        $list = Payable::with(['payments', 'supplier:id,name'])
            ->where('branch_id', $branchId)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('supplier_id'), fn ($q, $s) => $q->where('supplier_id', $s))
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('due_date', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->whereDate('due_date', '<=', $d))
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($list);
    }

    public function store(PayableRequest $request)
    {
        $this->authorize('create', Payable::class);

        $branchId = $request->attributes->get('branch_id');
        $data     = $request->validated();

        $payable = Payable::create([
            ...$data,
            'branch_id' => $branchId,
            'balance'   => $data['amount'],
            'status'    => 'open',
        ]);

        return response()->json($payable, 201);
    }

    public function show(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $payable  = Payable::with(['payments', 'supplier:id,name', 'purchase'])
            ->where('branch_id', $branchId)
            ->findOrFail($id);

        $this->authorize('view', $payable);

        return response()->json($payable);
    }

    public function update(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $payable  = Payable::where('branch_id', $branchId)->findOrFail($id);

        $this->authorize('update', $payable);

        $data = $request->validate([
            'due_date' => 'nullable|date',
            'note'     => 'nullable|string',
        ]);

        $payable->update($data);

        return response()->json($payable->refresh());
    }

    public function destroy(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $payable  = Payable::where('branch_id', $branchId)->findOrFail($id);

        $this->authorize('delete', $payable);

        $payable->delete();

        return response()->json(['message' => 'deleted']);
    }

    /** POST /payables/{payable}/pay */
    public function pay(PayablePayRequest $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $payable  = Payable::where('branch_id', $branchId)->findOrFail($id);

        $this->authorize('pay', $payable);

        $result = $this->payableService->pay($payable, $request->validated());

        return response()->json($result);
    }
}
