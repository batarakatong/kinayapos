<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchAdminController extends Controller
{
    // GET /admin/branches
    public function index(Request $request)
    {
        $branches = Branch::withCount('users')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when(isset($request->is_active), fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(20);

        return response()->json($branches);
    }

    // GET /admin/branches/{branch}
    public function show(Branch $branch)
    {
        return response()->json($branch->load([
            'users'          => fn($q) => $q->select('users.id', 'name', 'email'),
            'billings'       => fn($q) => $q->orderByDesc('billing_date')->limit(5),
            'reportSchedule',
        ]));
    }

    // POST /admin/branches
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'code'         => 'required|string|max:20|unique:branches,code',
            'address'      => 'nullable|string',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:255',
            'timezone'     => 'nullable|string|max:64',
            'bank_name'    => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'bank_holder'  => 'nullable|string|max:255',
            'tax_id'       => 'nullable|string|max:30',
            'notes'        => 'nullable|string',
        ]);

        $branch = Branch::create($data);

        // Upload logo jika ada
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branches/logos', 'public');
            $branch->update(['logo' => $path]);
        }

        return response()->json($branch, 201);
    }

    // PUT /admin/branches/{branch}
    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'code'         => "sometimes|string|max:20|unique:branches,code,{$branch->id}",
            'address'      => 'nullable|string',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:255',
            'timezone'     => 'nullable|string|max:64',
            'bank_name'    => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'bank_holder'  => 'nullable|string|max:255',
            'tax_id'       => 'nullable|string|max:30',
            'notes'        => 'nullable|string',
        ]);

        $branch->update($data);

        // Upload logo jika ada
        if ($request->hasFile('logo')) {
            if ($branch->logo) {
                \Storage::disk('public')->delete($branch->logo);
            }
            $path = $request->file('logo')->store('branches/logos', 'public');
            $branch->update(['logo' => $path]);
        }

        return response()->json($branch->fresh());
    }

    // DELETE /admin/branches/{branch}
    public function destroy(Branch $branch)
    {
        if ($branch->users()->count() > 0) {
            return response()->json([
                'message' => 'Branch masih memiliki ' . $branch->users()->count() . ' user. Hapus atau pindahkan user terlebih dahulu.',
            ], 422);
        }

        if ($branch->logo) {
            \Storage::disk('public')->delete($branch->logo);
        }

        $branch->delete();
        return response()->json(['message' => 'Branch deleted']);
    }

    // PATCH /admin/branches/{branch}/toggle — aktif/nonaktif
    public function toggle(Branch $branch)
    {
        $branch->update(['is_active' => !$branch->is_active]);
        return response()->json([
            'id'        => $branch->id,
            'is_active' => $branch->is_active,
            'message'   => $branch->is_active ? 'Branch diaktifkan' : 'Branch dinonaktifkan',
        ]);
    }
}
