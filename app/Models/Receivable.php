<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receivable extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'customer_id',
        'branch_id',
        'sale_id',
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
        return $this->hasMany(ReceivablePayment::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
