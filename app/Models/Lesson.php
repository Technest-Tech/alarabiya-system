<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    /**
     * Statuses the teacher is paid for.
     *
     * 'trial' is listed deliberately: a trial lesson is free for the student,
     * but the academy still pays the teacher for it. Keep it in this list so a
     * future change to the salary query can never silently drop trial pay.
     */
    public const TEACHER_PAYABLE_STATUSES = [
        'attended',
        'absent_student',
        'absent_teacher',
        'cancelled_student',
        'cancelled_teacher',
        'trial',
    ];

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
     * Check if the academy — rather than the student — covers this lesson.
     *
     * A trial lesson is free for the student: it is never billed and never
     * consumes package hours. The teacher is still paid for it, so the cost
     * is absorbed by the academy.
     */
    public function isAcademyPaid(): bool
    {
        return $this->isTrial();
    }

    /**
     * Check if the teacher is paid for this lesson.
     */
    public function isTeacherPayable(): bool
    {
        return in_array($this->status, self::TEACHER_PAYABLE_STATUSES, true);
    }

    /**
     * Limit a query to lessons the teacher is paid for.
     */
    public function scopeTeacherPayable(Builder $query): Builder
    {
        return $query->whereIn('status', self::TEACHER_PAYABLE_STATUSES);
    }

    /**
     * Limit a query to trial lessons, which the academy pays for.
     */
    public function scopeAcademyPaid(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', 'trial')->orWhere('is_trial', true);
        });
    }

    /**
     * Get duration in hours (decimal)
     */
    public function getDurationHoursAttribute(): float
    {
        return round($this->duration_minutes / 60, 2);
    }
}
