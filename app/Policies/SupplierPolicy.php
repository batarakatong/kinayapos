<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return true; // suppliers are global
    }

    public function create(User $user): bool
    {
        // Only branch_admin and above may create suppliers
        return $user->branches()
            ->wherePivotIn('role', ['super_admin', 'branch_admin'])
            ->exists();
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->create($user);
    }
}
