<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManualBillingRequest;
use App\Models\Billing;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $automaticBillings = Billing::with('student')
            ->automatic()
            ->when($monthDate, fn ($query) => $query->where('month', $monthDate->toDateString()))
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest('month')
            ->get()
            ->groupBy('status');

        $manualBillings = Billing::with('student')
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
            'students' => Student::orderBy('name')->get(),
        ]);
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
}


