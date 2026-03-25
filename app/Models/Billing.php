<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Billing extends Model
{
    protected $fillable = [
        'branch_id', 'package_id', 'plan', 'amount',
        'billing_date', 'period_start', 'period_end',
        'due_date', 'paid_at', 'status', 'invoice_number',
        'notes', 'payment_method', 'payment_proof',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'due_date'     => 'date',
        'paid_at'      => 'date',
        'amount'       => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(BillingPackage::class, 'package_id');
    }

    // Auto-generate invoice number
    public static function generateInvoice(): string
    {
        $prefix = 'INV-' . date('Ymd');
        $last = static::where('invoice_number', 'like', "$prefix%")
            ->orderByDesc('id')->value('invoice_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
