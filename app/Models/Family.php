<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'whatsapp_number',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withTimestamps();
    }

    public function billings(): HasManyThrough
    {
        return $this->hasManyThrough(
            Billing::class,
            FamilyStudent::class,
            'family_id',
            'student_id',
            'id',
            'student_id'
        );
    }

    public function getWhatsappNumberForLinkAttribute(): ?string
    {
        if (! $this->whatsapp_number) {
            return null;
        }

        return preg_replace('/\D+/', '', $this->whatsapp_number);
    }

    public function whatsappLink(string $message): ?string
    {
        $number = $this->whatsapp_number_for_link;

        if (! $number) {
            return null;
        }

        return sprintf(
            'https://wa.me/%s?text=%s',
            $number,
            rawurlencode($message)
        );
    }
}


