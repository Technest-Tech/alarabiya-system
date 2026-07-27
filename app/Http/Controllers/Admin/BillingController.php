<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManualBillingRequest;
use App\Models\Billing;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'automatic');
        $monthFilter = $request->query('month');
        $statusFilter = $request->query('status');

        $monthDate = null;

        if ($monthFilter && preg_match('/^\d{4}-\d{2}$/', $monthFilter)) {
            $monthDate = Carbon::parse(sprintf('%s-01', $monthFilter))->startOfMonth();
        }

        $automaticBillings = Billing::with(['student', 'items'])
            ->automatic()
            ->when($monthDate, fn ($query) => $query->where('month', $monthDate->toDateString()))
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest('month')
            ->get()
            ->groupBy('status');

        $manualBillings = Billing::with(['student', 'items'])
            ->manual()
            ->when($monthDate, fn ($query) => $query->where('month', $monthDate->toDateString()))
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest('month')
            ->get()
            ->groupBy('status');

        $availableMonths = Billing::select('month')
            ->distinct()
            ->orderByDesc('month')
            ->limit(12)
            ->pluck('month')
            ->map(fn ($month) => Carbon::parse($month)->format('Y-m'));

        return view('admin.billings.index', [
            'tab' => $tab,
            'monthFilter' => $monthFilter,
            'statusFilter' => $statusFilter,
            'automaticBillings' => $automaticBillings,
            'manualBillings' => $manualBillings,
            'availableMonths' => $availableMonths,
            'students' => Student::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Download every billing matching the filters currently applied on the index
     * page: one row per lesson, a total per student, and the grand total last.
     */
    public function export(Request $request)
    {
        $type = $request->query('tab') === 'manual' ? 'manual' : 'automatic';
        $monthFilter = $request->query('month');
        $statusFilter = in_array($request->query('status'), ['paid', 'unpaid'], true)
            ? $request->query('status')
            : null;

        $monthDate = null;

        if ($monthFilter && preg_match('/^\d{4}-\d{2}$/', $monthFilter)) {
            $monthDate = Carbon::parse(sprintf('%s-01', $monthFilter))->startOfMonth();
        }

        $billings = Billing::with(['student', 'items.lesson.teacher.user'])
            ->where('type', $type)
            ->when($monthDate, fn ($query) => $query->where('month', $monthDate->toDateString()))
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->get()
            ->groupBy(fn (Billing $billing) => Carbon::parse($billing->month)->format('Y-m'))
            ->sortKeysDesc()
            ->map(fn ($group) => $group->sortBy(fn (Billing $billing) => mb_strtolower($billing->student?->name ?? '')))
            ->flatten(1)
            ->values();

        $monthLabel = $monthDate ? $monthDate->isoFormat('MMMM YYYY') : 'All months';

        $fileName = sprintf(
            'billings-%s-%s.csv',
            $type,
            $monthDate ? $monthDate->format('Y-m') : 'all-months'
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
        ];

        $callback = function () use ($billings, $type, $monthLabel, $statusFilter) {
            $handle = fopen('php://output', 'w');

            // BOM so Excel picks up UTF-8 and renders Arabic student names.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Billing Report']);
            fputcsv($handle, ['Month', $monthLabel]);
            fputcsv($handle, ['Billing type', ucfirst($type)]);
            fputcsv($handle, ['Status', $statusFilter ? ucfirst($statusFilter) : 'All statuses']);
            fputcsv($handle, ['Generated at', now()->format('Y-m-d H:i')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Student', 'WhatsApp', 'Country', 'Payment Method', 'Currency', 'Student Hourly Rate',
                'Month', 'Status', 'Lesson Date', 'Teacher', 'Description',
                'Duration (mins)', 'Item Rate', 'Amount',
            ]);

            $totalsByCurrency = [];
            $grandTotalMinutes = 0;

            foreach ($billings as $billing) {
                $student = $billing->student;
                $currency = $billing->currency ?: ($student?->currency ?? 'EGP');
                $studentName = $student?->name ?? 'Unknown student';
                $billingMinutes = 0;

                $studentColumns = [
                    $studentName,
                    $student?->whatsapp_number,
                    $student?->country_code,
                    $student?->payment_method,
                    $currency,
                    $this->formatAmount($student?->hourly_rate),
                    $billing->month_label,
                    ucfirst($billing->status),
                ];

                // Manual billings are sometimes saved without line items; still show the charge.
                if ($billing->items->isEmpty()) {
                    fputcsv($handle, array_merge($studentColumns, [
                        '-',
                        '-',
                        $billing->description ?: 'Billing entry',
                        0,
                        '',
                        $this->formatAmount($billing->total_amount),
                    ]));
                }

                foreach ($billing->items as $item) {
                    $lesson = $item->lesson;
                    $billingMinutes += (int) $item->duration_minutes;

                    fputcsv($handle, array_merge($studentColumns, [
                        $lesson ? Carbon::parse($lesson->date)->format('Y-m-d') : '-',
                        $lesson?->teacher?->user?->name ?? '-',
                        $item->description ?: ($lesson ? 'Lesson' : '-'),
                        (int) $item->duration_minutes,
                        $item->hourly_rate !== null ? $this->formatAmount($item->hourly_rate) : '',
                        $this->formatAmount($item->amount),
                    ]));
                }

                $grandTotalMinutes += $billingMinutes;
                $totalsByCurrency[$currency] = ($totalsByCurrency[$currency] ?? 0) + (float) $billing->total_amount;

                fputcsv($handle, [
                    sprintf('Total for %s', $studentName),
                    '', '', '',
                    $currency,
                    '',
                    $billing->month_label,
                    ucfirst($billing->status),
                    '', '',
                    sprintf('%s hrs', $this->formatHours($billingMinutes)),
                    $billingMinutes,
                    '',
                    $this->formatAmount($billing->total_amount),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['SUMMARY']);
            fputcsv($handle, ['Billings', $billings->count()]);
            fputcsv($handle, ['Total hours', $this->formatHours($grandTotalMinutes)]);

            if (empty($totalsByCurrency)) {
                fputcsv($handle, ['TOTAL COST', $this->formatAmount(0)]);
            }

            foreach ($totalsByCurrency as $currency => $amount) {
                fputcsv($handle, [sprintf('TOTAL COST (%s)', $currency), $this->formatAmount($amount)]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function markPaid(Billing $billing): RedirectResponse
    {
        $billing->markAsPaid();

        return back()->with('status', 'Billing marked as paid.');
    }

    public function markUnpaid(Billing $billing): RedirectResponse
    {
        $billing->markAsUnpaid();

        return back()->with('status', 'Billing status reverted to unpaid.');
    }

    public function storeManual(StoreManualBillingRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $month = Carbon::createFromFormat('Y-m', $payload['month'])->startOfMonth();
        $currency = strtoupper($payload['currency']);

        $billing = Billing::firstOrNew([
            'student_id' => $payload['student_id'],
            'month' => $month->toDateString(),
            'type' => 'manual',
        ]);

        $billing->currency = $currency;
        $billing->description = $payload['description'] ?? null;
        $billing->total_amount = $payload['total_amount'];
        $billing->save();

        $billing->items()->updateOrCreate(
            [
                'billing_id' => $billing->id,
                'lesson_id' => null,
            ],
            [
                'description' => $payload['description'] ?? 'Manual billing entry',
                'duration_minutes' => 0,
                'hourly_rate' => null,
                'amount' => $payload['total_amount'],
            ]
        );

        if (! empty($payload['mark_as_paid'])) {
            $billing->markAsPaid();
        }

        return redirect()
            ->route('admin.billings.index', [
                'tab' => 'manual',
                'month' => $payload['month'],
            ])
            ->with('status', 'Manual billing saved successfully.');
    }

    public function report(Billing $billing)
    {
        $billing->load(['student', 'items.lesson.teacher']);

        $pdf = Pdf::loadView('admin.billings.report', compact('billing'));

        return $pdf->download("Billing_{$billing->student->name}_{$billing->month_label}.pdf");
    }

    /** Plain decimal (no thousands separator) so spreadsheets read it as a number. */
    protected function formatAmount(float|int|string|null $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    protected function formatHours(int $minutes): string
    {
        return number_format($minutes / 60, 2, '.', '');
    }
}


