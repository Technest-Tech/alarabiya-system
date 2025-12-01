<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentPackage extends Model
{
    protected $fillable = [
        'student_id',
        'package_hours',
        'hours_used',
        'started_at',
        'completed_at',
        'paid_at',
        'status',
        'is_active',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * Get hours used in decimal format
     */
    public function getHoursUsedDecimalAttribute(): float
    {
        return round($this->hours_used / 60, 2);
    }

    /**
     * Get remaining hours in decimal format
     */
    public function getRemainingHoursAttribute(): float
    {
        return max(0, $this->package_hours - $this->hours_used_decimal);
    }

    /**
     * Check if package is exhausted
     */
    public function isExhausted(): bool
    {
        return $this->hours_used >= ($this->package_hours * 60);
    }

    /**
     * Mark package as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'is_active' => false,
        ]);
    }

    /**
     * Mark package as paid
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'is_active' => false,
        ]);
    }
}
