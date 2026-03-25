<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPackage extends Model
{
    protected $fillable = [
        'name', 'slug', 'description',
        'price_monthly', 'price_quarterly', 'price_yearly',
        'features', 'max_users', 'max_branches',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'features'        => 'array',
        'price_monthly'   => 'decimal:2',
        'price_quarterly' => 'decimal:2',
        'price_yearly'    => 'decimal:2',
        'is_active'       => 'boolean',
        'max_users'       => 'integer',
        'max_branches'    => 'integer',
        'sort_order'      => 'integer',
    ];

    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class, 'package_id');
    }

    // Helper: harga berdasarkan plan period
    public function getPriceForPlan(string $plan): float
    {
        return match ($plan) {
            'quarterly' => (float) $this->price_quarterly,
            'yearly'    => (float) $this->price_yearly,
            default     => (float) $this->price_monthly,
        };
    }
}
