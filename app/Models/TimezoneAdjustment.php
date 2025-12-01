<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimezoneAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'timezone',
        'adjustment_hours',
        'applied_at',
        'applied_by',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}
