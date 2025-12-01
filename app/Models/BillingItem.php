<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_id',
        'lesson_id',
        'description',
        'duration_minutes',
        'hourly_rate',
        'amount',
    ];

    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}


