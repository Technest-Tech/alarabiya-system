<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'from_time',
        'to_time',
        'status',
        'notes',
        'created_by',
        'device_type',
        'support_name_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supportName(): BelongsTo
    {
        return $this->belongsTo(SupportName::class);
    }
}
