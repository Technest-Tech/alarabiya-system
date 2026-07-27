<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherAdjustment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeacherAdjustmentController extends Controller
{
    /**
     * List rewards & deductions for a month, with the add form.
     */
    public function index(Request $request): View
    {
        $month = $this->resolveMonth($request->get('month', Carbon::now()->format('Y-m')));
        $monthDate = $month->toDateString();

        $adjustments = TeacherAdjustment::with(['teacher.user', 'creator'])
            ->whereDate('month', $monthDate)
            ->latest()
            ->get();

        $teachers = Teacher::with('user')
            ->get()
            ->sortBy(fn (Teacher $t) => strtolower($t->user->name ?? ''))
            ->values();

        // Totals for the month, grouped by the teacher's currency (amounts are stored
        // in the teacher's own currency, so we cannot blindly sum across currencies).
        $totalsByCurrency = [];
        foreach ($adjustments as $adj) {
            $currency = $adj->teacher->currency ?? 'EGP';
            $totalsByCurrency[$currency] ??= ['reward' => 0.0, 'deduction' => 0.0];
            $totalsByCurrency[$currency][$adj->type] += (float) $adj->amount;
        }

        $availableMonths = TeacherAdjustment::selectRaw('DISTINCT month')
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn ($m) => Carbon::parse($m)->format('Y-m'));

        if ($availableMonths->isEmpty()) {
            $availableMonths = collect([$month->format('Y-m')]);
        }

        return view('admin.teachers.adjustments.index', [
            'adjustments' => $adjustments,
            'teachers' => $teachers,
            'month' => $month->format('Y-m'),
            'monthLabel' => $month->isoFormat('MMMM YYYY'),
            'totalsByCurrency' => $totalsByCurrency,
            'availableMonths' => $availableMonths,
        ]);
    }

    /**
     * Add a reward or deduction for a teacher.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'type' => ['required', 'in:reward,deduction'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'reason' => ['required', 'string', 'max:1000'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ], [
            'reason.required' => 'Please state the exact reason for this reward/deduction.',
        ]);

        $month = $this->resolveMonth($validated['month']);

        TeacherAdjustment::create([
            'teacher_id' => $validated['teacher_id'],
            'type' => $validated['type'],
            'amount' => round((float) $validated['amount'], 2),
            'reason' => $validated['reason'],
            'month' => $month->toDateString(),
            'created_by' => Auth::id(),
        ]);

        $label = $validated['type'] === 'reward' ? 'Reward' : 'Deduction';

        return redirect()
            ->route('admin.teacher-adjustments.index', ['month' => $month->format('Y-m')])
            ->with('status', $label . ' added successfully.');
    }

    /**
     * Remove an adjustment.
     */
    public function destroy(Request $request, TeacherAdjustment $adjustment): RedirectResponse
    {
        $month = Carbon::parse($adjustment->month)->format('Y-m');
        $adjustment->delete();

        return redirect()
            ->route('admin.teacher-adjustments.index', ['month' => $month])
            ->with('status', 'Adjustment removed.');
    }

    protected function resolveMonth(string $month): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::now()->startOfMonth();
        }

        return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    }
}
