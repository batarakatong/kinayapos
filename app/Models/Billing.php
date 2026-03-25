<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Billing extends Model
{
    protected $fillable = [
        'branch_id', 'plan', 'amount', 'billing_date',
        'due_date', 'paid_at', 'status', 'invoice_number', 'notes',
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
