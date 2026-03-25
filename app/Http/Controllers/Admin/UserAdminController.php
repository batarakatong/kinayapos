<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    // GET /admin/users
    public function index(Request $request)
    {
        $users = User::with(['branches' => fn($q) => $q->select('branches.id', 'name', 'code')])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(20);

        return response()->json($users);
    }

    // GET /admin/users/{user}
    public function show(User $user)
    {
        return response()->json($user->load('branches'));
    }

    // POST /admin/users
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'branch_id' => 'required|exists:branches,id',
            'role'      => ['required', Rule::in(['super_admin', 'branch_admin', 'cashier'])],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->branches()->attach($data['branch_id'], [
            'role'       => $data['role'],
            'is_default' => true,
        ]);

        return response()->json($user->load('branches'), 201);
    }

    // PUT /admin/users/{user}
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|min:6',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json($user->fresh('branches'));
    }

    // DELETE /admin/users/{user}
    public function destroy(User $user)
    {
        // Cegah hapus diri sendiri
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 422);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }

    // POST /admin/users/{user}/branches  — assign user ke branch
    public function assignBranch(Request $request, User $user)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'role'      => ['required', Rule::in(['super_admin', 'branch_admin', 'cashier'])],
        ]);

        $user->branches()->syncWithoutDetaching([
            $data['branch_id'] => ['role' => $data['role'], 'is_default' => false],
        ]);

        return response()->json($user->fresh('branches'));
    }

    // DELETE /admin/users/{user}/branches/{branch}  — lepas user dari branch
    public function removeBranch(User $user, int $branchId)
    {
        $user->branches()->detach($branchId);
        return response()->json($user->fresh('branches'));
    }
}
