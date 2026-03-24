<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'method',
        'status',
        'reference_id',
        'provider',
        'amount',
        'payload',
        'paid_at',
        'expires_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'paid_at'    => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
