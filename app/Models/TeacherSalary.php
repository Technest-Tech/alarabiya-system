<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSalary extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'month',
        'total_minutes',
        'hourly_rate',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'month' => 'date',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}


