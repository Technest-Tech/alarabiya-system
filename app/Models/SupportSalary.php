<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportSalary extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_name_id',
        'name',
        'month',
        'total_amount',
        'currency',
        'status',
        'notes',
    ];

    protected $casts = [
        'month' => 'date',
    ];

    public function supportName(): BelongsTo
    {
        return $this->belongsTo(SupportName::class);
    }
}
