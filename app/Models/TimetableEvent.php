<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'timetable_id',
        'student_id',
        'teacher_id',
        'course_name',
        'timezone',
        'start_at',
        'end_at',
        'is_override',
        'status',
        'metadata',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_override' => 'boolean',
        'metadata' => 'array',
    ];

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}

