<?php

namespace App\Policies;

use App\Models\Receivable;
use App\Models\User;

class ReceivablePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Receivable $receivable): bool
    {
        return $user->branches()->where('branches.id', $receivable->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Receivable $receivable): bool
    {
        return $this->view($user, $receivable);
    }

    public function delete(User $user, Receivable $receivable): bool
    {
        // Only allow deletion when fully paid (i.e., closing a record) or still open with no payments
        return $this->view($user, $receivable)
            && $receivable->payments()->count() === 0;
    }

    public function pay(User $user, Receivable $receivable): bool
    {
        return $this->view($user, $receivable)
            && $receivable->status !== 'paid';
    }
}
