<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Billing extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'month',
        'type',
        'total_amount',
        'currency',
        'status',
        'description',
    ];

    protected $casts = [
        'month' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingItem::class);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('type', 'automatic');
    }

    public function scopeManual($query)
    {
        return $query->where('type', 'manual');
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }

    public function markAsUnpaid(): void
    {
        $this->update(['status' => 'unpaid']);
    }

    public function getMonthLabelAttribute(): string
    {
        return Carbon::parse($this->month)->isoFormat('MMMM YYYY');
    }

    public function getWhatsappNumberAttribute(): ?string
    {
        $number = $this->student?->whatsapp_number;

        if (! $number) {
            return null;
        }

        return preg_replace('/\D+/', '', $number);
    }

    public function getWhatsappMessageAttribute(): string
    {
        $studentName = $this->student?->name ?? 'Student';
        $amount = number_format($this->total_amount, 2);
        $totalHours = round($this->items->sum('duration_minutes') / 60, 2);
        $hourlyRate = number_format($this->student?->hourly_rate ?? 0, 2);

        return sprintf(
            "Hello %s, your %s lessons billing for %s is ready. You have taken a total of %s hours at an hourly rate of %s %s. The total amount due is %s %s. Please complete the payment at your earliest convenience. Thank you!",
            $studentName,
            $this->type === 'automatic' ? 'automatic' : 'manual',
            $this->month_label,
            $totalHours,
            $this->currency,
            $hourlyRate,
            $this->currency,
            $amount
        );
    }

    public function getWhatsappLinkAttribute(): ?string
    {
        $number = $this->whatsapp_number;

        if (! $number) {
            return null;
        }

        return sprintf(
            'https://wa.me/%s?text=%s',
            $number,
            rawurlencode($this->whatsapp_message)
        );
    }
}


