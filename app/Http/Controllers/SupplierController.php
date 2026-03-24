<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::when(
            $request->query('q'),
            fn ($q, $term) => $q->where('name', 'like', "%{$term}%")
                               ->orWhere('phone', 'like', "%{$term}%")
        )->orderBy('name')->paginate(50);

        return response()->json($suppliers);
    }

    public function store(SupplierRequest $request)
    {
        $this->authorize('create', Supplier::class);

        $supplier = Supplier::create($request->validated());

        return response()->json($supplier, 201);
    }

    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
        $this->authorize('view', $supplier);

        return response()->json($supplier);
    }

    public function update(SupplierRequest $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());

        return response()->json($supplier->refresh());
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $this->authorize('delete', $supplier);

        $supplier->delete();

        return response()->json(['message' => 'deleted']);
    }
}
