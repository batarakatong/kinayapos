<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $customers = Customer::where(function ($q) use ($branchId) {
            $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
        })->orderBy('name')->get();

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'is_global' => 'boolean',
        ]);

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

        return response()->json($customer);
    }

    public function update(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $customer = Customer::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })->firstOrFail();

        $data = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $customer->update($data);
        return response()->json($customer);
    }

    public function destroy(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $customer = Customer::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })->firstOrFail();
        $customer->delete();
        return response()->json(['message' => 'deleted']);
    }
}
