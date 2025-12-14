<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccountantSalaryRequest;
use App\Http\Requests\Admin\StoreSupportSalaryRequest;
use App\Models\AccountantSalary;
use App\Models\Billing;
use App\Models\SupportName;
use App\Models\SupportSalary;
use App\Models\TeacherSalary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialOverviewController extends Controller
{
    public function index(Request $request): View
    {
        // Handle date range filtering
        $fromDate = $request->get('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        // Get conversion rate (from request or session, default to 1 if not set)
        $conversionRate = $request->get('conversion_rate', session('usd_to_egp_rate', null));
        if ($conversionRate !== null) {
            $conversionRate = (float) $conversionRate;
            session(['usd_to_egp_rate' => $conversionRate]);
        } else {
            $conversionRate = session('usd_to_egp_rate', 1);
        }
        
        $start = Carbon::parse($fromDate)->startOfDay();
        $end = Carbon::parse($toDate)->endOfDay();

        // Get income from billings
        $billings = Billing::whereDate('month', '>=', $start->toDateString())
            ->whereDate('month', '<=', $end->toDateString())
            ->get();

        $incomeByCurrency = $billings->groupBy('currency')->map(function ($group) {
            return [
                'total' => $group->sum('total_amount'),
                'paid' => $group->where('status', 'paid')->sum('total_amount'),
                'unpaid' => $group->where('status', 'unpaid')->sum('total_amount'),
            ];
        });

        // Get teacher salaries
        $teacherSalaries = TeacherSalary::whereDate('month', '>=', $start->toDateString())
            ->whereDate('month', '<=', $end->toDateString())
            ->with('teacher.user')
            ->get();

        $teacherSalariesByCurrency = $teacherSalaries->groupBy(function ($salary) {
            return $salary->teacher->currency ?? 'EGP';
        })->map(function ($group) {
            return $group->sum('total_amount');
        });

        // Get support salaries
        $supportSalaries = SupportSalary::whereDate('month', '>=', $start->toDateString())
            ->whereDate('month', '<=', $end->toDateString())
            ->get();

        $supportSalariesByCurrency = $supportSalaries->groupBy('currency')->map(function ($group) {
            return $group->sum('total_amount');
        });

        // Get accountant salaries
        $accountantSalaries = AccountantSalary::whereDate('month', '>=', $start->toDateString())
            ->whereDate('month', '<=', $end->toDateString())
            ->get();

        $accountantSalariesByCurrency = $accountantSalaries->groupBy('currency')->map(function ($group) {
            return $group->sum('total_amount');
        });

        // Calculate total expenses by currency
        $expensesByCurrency = collect();
        foreach ($teacherSalariesByCurrency as $currency => $amount) {
            $expensesByCurrency[$currency] = ($expensesByCurrency[$currency] ?? 0) + $amount;
        }
        foreach ($supportSalariesByCurrency as $currency => $amount) {
            $expensesByCurrency[$currency] = ($expensesByCurrency[$currency] ?? 0) + $amount;
        }
        foreach ($accountantSalariesByCurrency as $currency => $amount) {
            $expensesByCurrency[$currency] = ($expensesByCurrency[$currency] ?? 0) + $amount;
        }

        // Calculate net profit/loss by currency
        $netByCurrency = collect();
        $allCurrencies = $incomeByCurrency->keys()->merge($expensesByCurrency->keys())->unique();
        foreach ($allCurrencies as $currency) {
            $income = $incomeByCurrency[$currency]['total'] ?? 0;
            $expenses = $expensesByCurrency[$currency] ?? 0;
            $netByCurrency[$currency] = $income - $expenses;
        }

        // Get available months
        $availableMonths = collect();
        $billingMonths = Billing::select('month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn ($m) => Carbon::parse($m)->format('Y-m'));
        $salaryMonths = TeacherSalary::select('month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn ($m) => Carbon::parse($m)->format('Y-m'));
        $supportSalaryMonths = SupportSalary::select('month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn ($m) => Carbon::parse($m)->format('Y-m'));
        $accountantSalaryMonths = AccountantSalary::select('month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month')
            ->map(fn ($m) => Carbon::parse($m)->format('Y-m'));

        $availableMonths = $billingMonths
            ->merge($salaryMonths)
            ->merge($supportSalaryMonths)
            ->merge($accountantSalaryMonths)
            ->unique()
            ->sortDesc()
            ->values();

        if ($availableMonths->isEmpty()) {
            $availableMonths = collect([$start->format('Y-m')]);
        }

        // Helper function to convert to EGP
        $convertToEGP = function ($amount, $currency) use ($conversionRate) {
            if ($currency === 'USD') {
                return $amount * $conversionRate;
            }
            return $amount; // Already in EGP
        };

        // Convert all amounts to EGP for unified statistics
        $totalIncomeEGP = 0;
        $totalPaidIncomeEGP = 0;
        $totalUnpaidIncomeEGP = 0;
        foreach ($incomeByCurrency as $currency => $data) {
            $totalIncomeEGP += $convertToEGP($data['total'], $currency);
            $totalPaidIncomeEGP += $convertToEGP($data['paid'], $currency);
            $totalUnpaidIncomeEGP += $convertToEGP($data['unpaid'], $currency);
        }

        $totalExpensesEGP = 0;
        $totalPaidExpensesEGP = 0;
        $totalPendingExpensesEGP = 0;
        
        // Convert teacher salaries
        foreach ($teacherSalaries as $salary) {
            $currency = $salary->teacher->currency ?? 'EGP';
            $amountEGP = $convertToEGP($salary->total_amount, $currency);
            $totalExpensesEGP += $amountEGP;
            if ($salary->status === 'paid') {
                $totalPaidExpensesEGP += $amountEGP;
            } else {
                $totalPendingExpensesEGP += $amountEGP;
            }
        }
        
        // Convert support salaries
        foreach ($supportSalaries as $salary) {
            $amountEGP = $convertToEGP($salary->total_amount, $salary->currency);
            $totalExpensesEGP += $amountEGP;
            if ($salary->status === 'paid') {
                $totalPaidExpensesEGP += $amountEGP;
            } else {
                $totalPendingExpensesEGP += $amountEGP;
            }
        }
        
        // Convert accountant salaries
        foreach ($accountantSalaries as $salary) {
            $amountEGP = $convertToEGP($salary->total_amount, $salary->currency);
            $totalExpensesEGP += $amountEGP;
            if ($salary->status === 'paid') {
                $totalPaidExpensesEGP += $amountEGP;
            } else {
                $totalPendingExpensesEGP += $amountEGP;
            }
        }

        $totalNetEGP = $totalIncomeEGP - $totalExpensesEGP;

        // Also calculate converted amounts by currency for display
        $incomeByCurrencyConverted = $incomeByCurrency->map(function ($data, $currency) use ($convertToEGP) {
            return [
                'total' => $data['total'],
                'total_egp' => $convertToEGP($data['total'], $currency),
                'paid' => $data['paid'],
                'paid_egp' => $convertToEGP($data['paid'], $currency),
                'unpaid' => $data['unpaid'],
                'unpaid_egp' => $convertToEGP($data['unpaid'], $currency),
            ];
        });

        $expensesByCurrencyConverted = collect();
        foreach ($teacherSalariesByCurrency as $currency => $amount) {
            $expensesByCurrencyConverted->put($currency, [
                'original' => $amount,
                'egp' => $convertToEGP($amount, $currency),
            ]);
        }
        foreach ($supportSalariesByCurrency as $currency => $amount) {
            if (!$expensesByCurrencyConverted->has($currency)) {
                $expensesByCurrencyConverted->put($currency, ['original' => 0, 'egp' => 0]);
            }
            $current = $expensesByCurrencyConverted->get($currency);
            $expensesByCurrencyConverted->put($currency, [
                'original' => $current['original'] + $amount,
                'egp' => $current['egp'] + $convertToEGP($amount, $currency),
            ]);
        }
        foreach ($accountantSalariesByCurrency as $currency => $amount) {
            if (!$expensesByCurrencyConverted->has($currency)) {
                $expensesByCurrencyConverted->put($currency, ['original' => 0, 'egp' => 0]);
            }
            $current = $expensesByCurrencyConverted->get($currency);
            $expensesByCurrencyConverted->put($currency, [
                'original' => $current['original'] + $amount,
                'egp' => $current['egp'] + $convertToEGP($amount, $currency),
            ]);
        }

        return view('admin.financials.index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'start' => $start,
            'end' => $end,
            'conversionRate' => $conversionRate,
            'availableMonths' => $availableMonths,
            'incomeByCurrency' => $incomeByCurrencyConverted,
            'expensesByCurrency' => $expensesByCurrencyConverted,
            'netByCurrency' => $netByCurrency,
            'teacherSalaries' => $teacherSalaries,
            'supportSalaries' => $supportSalaries,
            'accountantSalaries' => $accountantSalaries,
            'totalIncome' => $totalIncomeEGP,
            'totalExpenses' => $totalExpensesEGP,
            'totalNet' => $totalNetEGP,
            'totalPaidIncome' => $totalPaidIncomeEGP,
            'totalUnpaidIncome' => $totalUnpaidIncomeEGP,
            'totalPaidExpenses' => $totalPaidExpensesEGP,
            'totalPendingExpenses' => $totalPendingExpensesEGP,
        ]);
    }

    public function storeSupportSalary(StoreSupportSalaryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $month = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();

        SupportSalary::updateOrCreate(
            [
                'name' => $validated['name'],
                'month' => $month->toDateString(),
            ],
            [
                'total_amount' => $validated['total_amount'],
                'currency' => strtoupper($validated['currency']),
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]
        );

        $redirectParams = $request->only(['from_date', 'to_date', 'month', 'conversion_rate']);
        if (!$redirectParams['month']) {
            unset($redirectParams['month']);
        }

        return redirect()->route('admin.financials.index', $redirectParams)
            ->with('status', 'Support salary saved successfully.');
    }

    public function storeAccountantSalary(StoreAccountantSalaryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $month = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();

        AccountantSalary::updateOrCreate(
            [
                'name' => $validated['name'],
                'month' => $month->toDateString(),
            ],
            [
                'total_amount' => $validated['total_amount'],
                'currency' => strtoupper($validated['currency']),
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]
        );

        $redirectParams = $request->only(['from_date', 'to_date', 'month', 'conversion_rate']);
        if (!$redirectParams['month']) {
            unset($redirectParams['month']);
        }

        return redirect()->route('admin.financials.index', $redirectParams)
            ->with('status', 'Accountant salary saved successfully.');
    }

    public function updateSupportSalaryStatus(Request $request, SupportSalary $salary): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,paid'],
        ]);

        $salary->update(['status' => $request->status]);

        $redirectParams = $request->only(['from_date', 'to_date', 'month', 'conversion_rate']);

        return redirect()->route('admin.financials.index', $redirectParams)
            ->with('status', 'Support salary status updated.');
    }

    public function updateAccountantSalaryStatus(Request $request, AccountantSalary $salary): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,paid'],
        ]);

        $salary->update(['status' => $request->status]);

        $redirectParams = $request->only(['from_date', 'to_date', 'month', 'conversion_rate']);

        return redirect()->route('admin.financials.index', $redirectParams)
            ->with('status', 'Accountant salary status updated.');
    }

    public function deleteSupportSalary(Request $request, SupportSalary $salary): RedirectResponse
    {
        $salary->delete();

        $redirectParams = $request->only(['from_date', 'to_date', 'month', 'conversion_rate']);

        return redirect()->route('admin.financials.index', $redirectParams)
            ->with('status', 'Support salary deleted successfully.');
    }

    public function deleteAccountantSalary(Request $request, AccountantSalary $salary): RedirectResponse
    {
        $salary->delete();

        $redirectParams = $request->only(['from_date', 'to_date', 'month', 'conversion_rate']);

        return redirect()->route('admin.financials.index', $redirectParams)
            ->with('status', 'Accountant salary deleted successfully.');
    }

    protected function resolveMonth(string $month): Carbon
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::now()->startOfMonth();
        }

        return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    }
}
