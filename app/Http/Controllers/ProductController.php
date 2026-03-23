<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $products = Product::query()
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');

        $data = $request->validate([
            'name' => 'required|string',
            'sku' => 'required|string|unique:products,sku',
            'barcode' => 'nullable|string|unique:products,barcode',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'is_global' => 'boolean',
            'track_stock' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $product = Product::create([
            ...$data,
            'uuid' => (string) Str::uuid(),
            'branch_id' => ($data['is_global'] ?? true) ? null : $branchId,
        ]);

        return response()->json($product, 201);
    }

    public function show(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $product = Product::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->firstOrFail();

        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $product = Product::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->firstOrFail();

        $data = $request->validate([
            'name' => 'sometimes|string',
            'sku' => 'sometimes|string|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'price' => 'sometimes|numeric|min:0',
            'cost' => 'sometimes|numeric|min:0',
            'tax_rate' => 'sometimes|numeric|min:0',
            'is_global' => 'boolean',
            'track_stock' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (array_key_exists('is_global', $data)) {
            $data['branch_id'] = $data['is_global'] ? null : $branchId;
        }

        $product->update($data);

        return response()->json($product);
    }

    public function destroy(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $product = Product::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->firstOrFail();

        $product->delete();
        return response()->json(['message' => 'deleted']);
    }
}
