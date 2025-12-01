<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'course_name',
        'timezone',
        'teacher_timezone',
        'start_time',
        'end_time',
        'student_time_from',
        'student_time_to',
        'time_difference_hours',
        'use_manual_time_diff',
        'start_date',
        'end_date',
        'days_of_week',
        'is_active',
        'deactivated_at',
        'deactivated_until',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'deactivated_at' => 'datetime',
        'deactivated_until' => 'date',
        'is_active' => 'boolean',
        'use_manual_time_diff' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TimetableEvent::class);
    }
}

