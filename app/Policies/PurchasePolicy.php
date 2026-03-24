<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    /** Super-admin can do everything. Branch roles are gated by the branch middleware. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->branches()->where('branches.id', $purchase->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $this->view($user, $purchase);
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        // Only allow deletion while still in draft/open status
        if (! in_array($purchase->status, ['draft', 'open'])) {
            return false;
        }

        return $this->view($user, $purchase);
    }
}
