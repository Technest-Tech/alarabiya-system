<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'type',      // 'reward' or 'deduction'
        'amount',    // positive number, in the teacher's currency
        'reason',
        'month',     // first day of the salary month it applies to
        'created_by',
    ];

    protected $casts = [
        'month' => 'date',
        'amount' => 'decimal:2',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isReward(): bool
    {
        return $this->type === 'reward';
    }

    /**
     * Signed effect on salary: rewards add, deductions subtract.
     */
    public function signedAmount(): float
    {
        return $this->isReward() ? (float) $this->amount : -(float) $this->amount;
    }
}
