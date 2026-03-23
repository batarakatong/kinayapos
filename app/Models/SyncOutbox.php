<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncOutbox extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'table_name',
        'row_id',
        'action',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
