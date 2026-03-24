<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $branchId = $request->attributes->get('branch_id');

        $customers = Customer::where(function ($q) use ($branchId) {
            $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
        })
        ->when(
            $request->query('q'),
            fn ($q, $term) => $q->where('name', 'like', "%{$term}%")
                               ->orWhere('phone', 'like', "%{$term}%")
        )
        ->orderBy('name')
        ->paginate(50);

        return response()->json($customers);
    }

    public function store(CustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $branchId = $request->attributes->get('branch_id');
        $data     = $request->validated();

        $customer = Customer::create([
            ...$data,
            'branch_id' => ($data['is_global'] ?? false) ? null : $branchId,
        ]);

        return response()->json($customer, 201);
    }

    public function show(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $customer = Customer::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })->firstOrFail();

        $this->authorize('view', $customer);

        return response()->json($customer);
    }

    public function update(CustomerRequest $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $customer = Customer::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })->firstOrFail();

        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return response()->json($customer->refresh());
    }

    public function destroy(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $customer = Customer::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })->firstOrFail();

        $this->authorize('delete', $customer);

        $customer->delete();

        return response()->json(['message' => 'deleted']);
    }
}
