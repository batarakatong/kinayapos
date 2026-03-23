<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayablePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payable_id',
        'amount',
        'method',
        'paid_at',
        'note',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }
}
