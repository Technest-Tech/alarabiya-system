<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'whatsapp_number', 'country_code', 'package_hours_total',
        'hours_taken_cached', 'status', 'payment_method', 'hourly_rate',
        'assigned_teacher_id', 'current_package_id',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'assigned_teacher_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function timetableEvents(): HasMany
    {
        return $this->hasMany(TimetableEvent::class);
    }

    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class);
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class)->withTimestamps();
    }

    public function currentPackage(): BelongsTo
    {
        return $this->belongsTo(StudentPackage::class, 'current_package_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(StudentPackage::class);
    }

    public function getTakenHoursAttribute(): int
    {
        return intdiv($this->hours_taken_cached, 60);
    }

    public function getRemainingHoursAttribute(): float
    {
        // If student has a current package, use that for remaining hours
        if ($this->currentPackage) {
            // Use the package's remaining hours, but ensure it doesn't exceed the student's total
            $packageRemaining = $this->currentPackage->remaining_hours;
            // The package remaining should be based on package_hours, but cap it at student's total
            return max(0, min($packageRemaining, $this->package_hours_total));
        }
        return max(0, $this->package_hours_total - $this->taken_hours);
    }

    public function recalculateHoursTaken(): void
    {
        // Only count calculated lessons (attended, absent_student) that are not pending
        $minutes = $this->lessons()
            ->where('is_pending', false)
            ->whereIn('status', ['attended', 'absent_student'])
            ->sum('duration_minutes');
        $this->hours_taken_cached = (int) $minutes;
        $this->save();
    }

    /**
     * Get pending lessons count
     */
    public function getPendingLessonsCountAttribute(): int
    {
        return $this->lessons()->where('is_pending', true)->count();
    }
}
