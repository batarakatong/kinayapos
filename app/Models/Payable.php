<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payable extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'supplier_id',
        'branch_id',
        'purchase_id',
        'amount',
        'balance',
        'status',
        'due_date',
        'note',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(PayablePayment::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
