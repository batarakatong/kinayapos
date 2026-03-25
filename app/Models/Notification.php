<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notification extends Model
{
    protected $fillable = [
        'title', 'body', 'type', 'is_broadcast',
        'created_by', 'scheduled_at', 'sent_at',
        'image', 'action_url', 'is_draft',
    ];

    protected $casts = [
        'is_broadcast' => 'boolean',
        'is_draft'     => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'notification_branches')
            ->withPivot('read_at', 'delivered_at')
            ->withTimestamps();
    }
}
