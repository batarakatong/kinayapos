<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            ->when($request->query('is_active') !== 'all', fn($q) => $q->where('is_active', true))
            ->when($request->query('q'), fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->with('stocks')
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
        $product = Product::with('stocks')
            ->where('id', $id)
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

        return response()->json($product->fresh());
    }

    public function destroy(Request $request, $id)
    {
        $branchId = $request->attributes->get('branch_id');
        $product = Product::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->firstOrFail();

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();
        return response()->json(['message' => 'deleted']);
    }

    /** POST /products/{id}/image — Upload/replace product image */
    public function uploadImage(Request $request, $id)
    {
        $request->validate(['image' => 'required|image|max:2048']);

        $branchId = $request->attributes->get('branch_id');
        $product = Product::where('id', $id)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->firstOrFail();

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $path = $request->file('image')->store('products', 'public');
        $product->update(['image_path' => $path]);

        return response()->json(['image_url' => $product->image_url]);
    }
}

