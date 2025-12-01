<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyStudent extends Model
{
    use HasFactory;

    protected $table = 'family_student';

    protected $fillable = [
        'family_id',
        'student_id',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}


