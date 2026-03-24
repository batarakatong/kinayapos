<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $branchId = request()->attributes->get('branch_id');
        $products = Product::query()
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }

    public function store(ProductRequest $request)
    {
        $branchId = $request->attributes->get('branch_id');

        $data = $request->validated();

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

    public function update(ProductRequest $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $product = Product::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->firstOrFail();

        $data = $request->validated();

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
