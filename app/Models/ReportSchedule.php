<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSchedule extends Model
{
    protected $fillable = [
        'branch_id', 'enabled', 'send_at', 'recipients', 'report_types',
    ];

    protected $casts = [
        'enabled'      => 'boolean',
        'report_types' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Parse recipients string to array
    public function getRecipientsArrayAttribute(): array
    {
        return array_map('trim', explode(',', $this->recipients));
    }
}
