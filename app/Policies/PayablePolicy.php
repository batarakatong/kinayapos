<?php

namespace App\Policies;

use App\Models\Payable;
use App\Models\User;

class PayablePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payable $payable): bool
    {
        return $user->branches()->where('branches.id', $payable->branch_id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Payable $payable): bool
    {
        return $this->view($user, $payable);
    }

    public function delete(User $user, Payable $payable): bool
    {
        return $this->view($user, $payable)
            && $payable->payments()->count() === 0;
    }

    public function pay(User $user, Payable $payable): bool
    {
        return $this->view($user, $payable)
            && $payable->status !== 'paid';
    }
}
