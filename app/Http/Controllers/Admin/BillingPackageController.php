<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingPackageController extends Controller
{
    // GET /admin/packages
    public function index()
    {
        $packages = BillingPackage::orderBy('sort_order')->orderBy('price_monthly')->get();
        return response()->json($packages);
    }

    // GET /admin/packages/{package}
    public function show(BillingPackage $package)
    {
        return response()->json($package->load('billings'));
    }

    // POST /admin/packages
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'slug'             => 'nullable|string|max:50|unique:billing_packages,slug',
            'description'      => 'nullable|string',
            'price_monthly'    => 'required|numeric|min:0',
            'price_quarterly'  => 'nullable|numeric|min:0',
            'price_yearly'     => 'nullable|numeric|min:0',
            'features'         => 'nullable|array',
            'features.*'       => 'string',
            'max_users'        => 'nullable|integer|min:1',
            'max_branches'     => 'nullable|integer|min:1',
            'is_active'        => 'boolean',
            'sort_order'       => 'nullable|integer',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        // Auto hitung quarterly/yearly jika tidak di-set
        if (empty($data['price_quarterly'])) {
            $data['price_quarterly'] = round($data['price_monthly'] * 3 * 0.95, 0); // diskon 5%
        }
        if (empty($data['price_yearly'])) {
            $data['price_yearly'] = round($data['price_monthly'] * 12 * 0.85, 0); // diskon 15%
        }

        $package = BillingPackage::create($data);
        return response()->json($package, 201);
    }

    // PUT /admin/packages/{package}
    public function update(Request $request, BillingPackage $package)
    {
        $data = $request->validate([
            'name'             => 'sometimes|string|max:100',
            'slug'             => "sometimes|string|max:50|unique:billing_packages,slug,{$package->id}",
            'description'      => 'nullable|string',
            'price_monthly'    => 'sometimes|numeric|min:0',
            'price_quarterly'  => 'nullable|numeric|min:0',
            'price_yearly'     => 'nullable|numeric|min:0',
            'features'         => 'nullable|array',
            'features.*'       => 'string',
            'max_users'        => 'nullable|integer|min:1',
            'max_branches'     => 'nullable|integer|min:1',
            'is_active'        => 'boolean',
            'sort_order'       => 'nullable|integer',
        ]);

        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $package->update($data);
        return response()->json($package->fresh());
    }

    // DELETE /admin/packages/{package}
    public function destroy(BillingPackage $package)
    {
        if ($package->billings()->exists()) {
            return response()->json([
                'message' => 'Package masih digunakan oleh ' . $package->billings()->count() . ' tagihan. Nonaktifkan dulu.',
            ], 422);
        }
        $package->delete();
        return response()->json(['message' => 'Package deleted']);
    }

    // PATCH /admin/packages/{package}/toggle — aktif/nonaktif
    public function toggle(BillingPackage $package)
    {
        $package->update(['is_active' => !$package->is_active]);
        return response()->json([
            'id'        => $package->id,
            'is_active' => $package->is_active,
            'message'   => $package->is_active ? 'Package diaktifkan' : 'Package dinonaktifkan',
        ]);
    }
}
