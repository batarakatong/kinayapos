<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SmtpSetting extends Model
{
    protected $fillable = [
        'branch_id', 'driver', 'host', 'port', 'encryption',
        'username', 'password', 'from_address', 'from_name', 'is_active',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'port'      => 'integer',
        'is_active' => 'boolean',
    ];

    // Encrypt password on save
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    // Decrypt password on get
    public function getPasswordAttribute(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return '';
        }
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
