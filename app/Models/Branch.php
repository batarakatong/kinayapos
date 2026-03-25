<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'address', 'phone', 'email',
        'timezone', 'is_active',
        'logo', 'bank_name', 'bank_account', 'bank_holder',
        'tax_id', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branch')
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, 'notification_branches')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class);
    }

    public function smtpSetting(): HasOne
    {
        return $this->hasOne(SmtpSetting::class)->where('is_active', true);
    }

    public function reportSchedule(): HasOne
    {
        return $this->hasOne(ReportSchedule::class);
    }
}
