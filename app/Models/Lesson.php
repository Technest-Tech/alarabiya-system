<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'student_id','teacher_id','duration_minutes','date','status','is_trial',
        'student_package_id','package_cumulative_hours','is_pending','package_lesson_number',
    ];

    protected $casts = [
        'is_pending' => 'boolean',
        'is_trial' => 'boolean',
        'package_cumulative_hours' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function billingItems(): HasMany
    {
        return $this->hasMany(BillingItem::class);
    }

    public function studentPackage(): BelongsTo
    {
        return $this->belongsTo(StudentPackage::class);
    }

    /**
     * Check if the lesson status is calculated (included in billing).
     */
    public function isCalculated(): bool
    {
        return in_array($this->status, ['attended', 'absent_student']) && !$this->isTrial();
    }

    /**
     * Check if the lesson is a trial lesson (not charged from package).
     */
    public function isTrial(): bool
    {
        return $this->status === 'trial' || $this->is_trial === true;
    }

    /**
     * Get duration in hours (decimal)
     */
    public function getDurationHoursAttribute(): float
    {
        return round($this->duration_minutes / 60, 2);
    }
}
