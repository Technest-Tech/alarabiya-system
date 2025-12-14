<x-app-layout pageTitle="Financial Overview">
    <div class="space-y-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Financial Overview</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Track academy income and expenses by period.
                </p>
            </div>
            <form method="GET" class="flex items-center gap-3 flex-wrap">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">USD to EGP Rate</label>
                    <input type="number" name="conversion_rate" step="0.01" min="0" value="{{ $conversionRate }}"
                           placeholder="e.g., 50.5"
                           class="w-32 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ $fromDate }}"
                           class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ $toDate }}"
                           class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                        Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-800">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Income</p>
                <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                    EGP {{ number_format($totalIncome, 2) }}
                </p>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="text-green-600 dark:text-green-400">Paid: EGP {{ number_format($totalPaidIncome, 2) }}</span>
                    <span class="mx-2">|</span>
                    <span class="text-yellow-600 dark:text-yellow-400">Unpaid: EGP {{ number_format($totalUnpaidIncome, 2) }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 italic">(All currencies converted to EGP)</p>
            </div>
            
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-800">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Expenses</p>
                <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">
                    EGP {{ number_format($totalExpenses, 2) }}
                </p>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="text-green-600 dark:text-green-400">Paid: EGP {{ number_format($totalPaidExpenses, 2) }}</span>
                    <span class="mx-2">|</span>
                    <span class="text-yellow-600 dark:text-yellow-400">Pending: EGP {{ number_format($totalPendingExpenses, 2) }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 italic">(All currencies converted to EGP)</p>
            </div>
            
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-800">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Net Profit/Loss</p>
                <p class="mt-2 text-3xl font-bold {{ $totalNet >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    EGP {{ number_format($totalNet, 2) }}
                </p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ $start->isoFormat('MMM D') }} - {{ $end->isoFormat('MMM D, YYYY') }}
                </p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 italic">(All currencies converted to EGP)</p>
            </div>
            
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-800">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Profit Margin</p>
                <p class="mt-2 text-3xl font-bold {{ $totalIncome > 0 ? ($totalNet / $totalIncome * 100 >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') : 'text-gray-600 dark:text-gray-400' }}">
                    {{ $totalIncome > 0 ? number_format(($totalNet / $totalIncome) * 100, 2) : '0.00' }}%
                </p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ $totalIncome > 0 ? 'of total income' : 'No income data' }}
                </p>
                @if($conversionRate > 1)
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 italic">Rate: 1 USD = {{ number_format($conversionRate, 2) }} EGP</p>
                @endif
            </div>
        </div>

        <!-- Summary Cards by Currency -->
        @php
            $allCurrencies = $incomeByCurrency->keys()->merge($expensesByCurrency->keys())->unique();
        @endphp
        @if($allCurrencies->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($allCurrencies as $currency)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-800">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $currency }} Summary</p>
                        <div class="mt-4 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Income:</span>
                                <div class="text-right">
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                        {{ $currency }} {{ number_format($incomeByCurrency[$currency]['total'] ?? 0, 2) }}
                                    </span>
                                    @if($currency === 'USD' && isset($incomeByCurrency[$currency]['total_egp']))
                                        <br><span class="text-xs text-gray-500 dark:text-gray-400">
                                            (EGP {{ number_format($incomeByCurrency[$currency]['total_egp'], 2) }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Expenses:</span>
                                <div class="text-right">
                                    <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                                        {{ $currency }} {{ number_format($expensesByCurrency[$currency]['original'] ?? 0, 2) }}
                                    </span>
                                    @if($currency === 'USD' && isset($expensesByCurrency[$currency]['egp']))
                                        <br><span class="text-xs text-gray-500 dark:text-gray-400">
                                            (EGP {{ number_format($expensesByCurrency[$currency]['egp'], 2) }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="pt-2 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">Net:</span>
                                <div class="text-right">
                                    <span class="text-lg font-bold {{ ($netByCurrency[$currency] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $currency }} {{ number_format($netByCurrency[$currency] ?? 0, 2) }}
                                    </span>
                                    @if($currency === 'USD')
                                        @php
                                            $netEGP = ($incomeByCurrency[$currency]['total_egp'] ?? 0) - ($expensesByCurrency[$currency]['egp'] ?? 0);
                                        @endphp
                                        <br><span class="text-xs text-gray-500 dark:text-gray-400">
                                            (EGP {{ number_format($netEGP, 2) }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Income Section -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Income (Billings)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Currency</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paid</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unpaid</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($incomeByCurrency as $currency => $data)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $currency }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $currency }} {{ number_format($data['total'], 2) }}
                                    @if($currency === 'USD' && isset($data['total_egp']))
                                        <br><span class="text-xs text-gray-500 dark:text-gray-400">(EGP {{ number_format($data['total_egp'], 2) }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-green-600 dark:text-green-400">
                                    {{ $currency }} {{ number_format($data['paid'], 2) }}
                                    @if($currency === 'USD' && isset($data['paid_egp']))
                                        <br><span class="text-xs text-gray-500 dark:text-gray-400">(EGP {{ number_format($data['paid_egp'], 2) }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-red-600 dark:text-red-400">
                                    {{ $currency }} {{ number_format($data['unpaid'], 2) }}
                                    @if($currency === 'USD' && isset($data['unpaid_egp']))
                                        <br><span class="text-xs text-gray-500 dark:text-gray-400">(EGP {{ number_format($data['unpaid_egp'], 2) }})</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No income data for the selected period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expenses Section -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Expenses</h3>
            </div>
            
            <!-- Teacher Salaries -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white">Teacher Salaries</h4>
                    <a href="{{ route('admin.teacher-salaries.index', ['month' => $start->format('Y-m')]) }}" 
                       class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                        View Details →
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Teacher</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($teacherSalaries as $salary)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $salary->teacher->user?->name ?? 'Unassigned' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $salary->teacher->currency ?? 'EGP' }} {{ number_format($salary->total_amount, 2) }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $salary->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' }}">
                                            {{ ucfirst($salary->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No teacher salaries for the selected period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Support Salaries -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white">Support Salaries</h4>
                    <button onclick="document.getElementById('add-support-salary-form').classList.toggle('hidden')"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700 transition-colors">
                        + Add Salary
                    </button>
                </div>

                <!-- Add Support Salary Form -->
                <div id="add-support-salary-form" class="hidden mb-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <form method="POST" action="{{ route('admin.financials.support-salary.store') }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="month" value="{{ $start->format('Y-m') }}">
                        <input type="hidden" name="from_date" value="{{ $fromDate }}">
                        <input type="hidden" name="to_date" value="{{ $toDate }}">
                        <input type="hidden" name="conversion_rate" value="{{ $conversionRate }}">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Support Name</label>
                                <input type="text" name="name" required placeholder="Enter support name"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Amount</label>
                                <input type="number" name="total_amount" step="0.01" min="0" required
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Currency</label>
                                <select name="currency" required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                                    <option value="EGP">EGP</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Notes</label>
                                <input type="text" name="notes"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                                Save
                            </button>
                            <button type="button" onclick="document.getElementById('add-support-salary-form').classList.add('hidden')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Support Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Notes</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($supportSalaries as $salary)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $salary->name }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $salary->currency }} {{ number_format($salary->total_amount, 2) }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $salary->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' }}">
                                            {{ ucfirst($salary->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $salary->notes ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-sm">
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('admin.financials.support-salary.status', $salary) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                                                <input type="hidden" name="to_date" value="{{ $toDate }}">
                                                <input type="hidden" name="conversion_rate" value="{{ $conversionRate }}">
                                                <input type="hidden" name="status" value="{{ $salary->status === 'paid' ? 'pending' : 'paid' }}">
                                                <button type="submit" 
                                                        class="inline-flex items-center px-3 py-1.5 {{ $salary->status === 'paid' ? 'bg-yellow-600' : 'bg-green-600' }} text-white rounded-lg text-xs font-semibold hover:{{ $salary->status === 'paid' ? 'bg-yellow-700' : 'bg-green-700' }} transition-colors">
                                                    {{ $salary->status === 'paid' ? 'Mark Pending' : 'Mark Paid' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.financials.support-salary.delete', $salary) }}" method="POST"
                                                  onsubmit="return confirm('Are you sure you want to delete this salary entry?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                                                <input type="hidden" name="to_date" value="{{ $toDate }}">
                                                <input type="hidden" name="conversion_rate" value="{{ $conversionRate }}">
                                                <button type="submit" 
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs font-semibold hover:bg-red-700 transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No support salaries for the selected period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Accountant Salaries -->
            <div class="px-6 py-4">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white">Accountant Salaries</h4>
                    <button onclick="document.getElementById('add-accountant-salary-form').classList.toggle('hidden')"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700 transition-colors">
                        + Add Salary
                    </button>
                </div>

                <!-- Add Accountant Salary Form -->
                <div id="add-accountant-salary-form" class="hidden mb-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <form method="POST" action="{{ route('admin.financials.accountant-salary.store') }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="month" value="{{ $start->format('Y-m') }}">
                        <input type="hidden" name="from_date" value="{{ $fromDate }}">
                        <input type="hidden" name="to_date" value="{{ $toDate }}">
                        <input type="hidden" name="conversion_rate" value="{{ $conversionRate }}">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Accountant Name</label>
                                <input type="text" name="name" required placeholder="Enter accountant name"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Amount</label>
                                <input type="number" name="total_amount" step="0.01" min="0" required
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Currency</label>
                                <select name="currency" required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                                    <option value="EGP">EGP</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Notes</label>
                                <input type="text" name="notes"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                                Save
                            </button>
                            <button type="button" onclick="document.getElementById('add-accountant-salary-form').classList.add('hidden')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Accountant</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Notes</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($accountantSalaries as $salary)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $salary->name }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $salary->currency }} {{ number_format($salary->total_amount, 2) }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $salary->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' }}">
                                            {{ ucfirst($salary->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $salary->notes ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-sm">
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('admin.financials.accountant-salary.status', $salary) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                                                <input type="hidden" name="to_date" value="{{ $toDate }}">
                                                <input type="hidden" name="conversion_rate" value="{{ $conversionRate }}">
                                                <input type="hidden" name="status" value="{{ $salary->status === 'paid' ? 'pending' : 'paid' }}">
                                                <button type="submit" 
                                                        class="inline-flex items-center px-3 py-1.5 {{ $salary->status === 'paid' ? 'bg-yellow-600' : 'bg-green-600' }} text-white rounded-lg text-xs font-semibold hover:{{ $salary->status === 'paid' ? 'bg-yellow-700' : 'bg-green-700' }} transition-colors">
                                                    {{ $salary->status === 'paid' ? 'Mark Pending' : 'Mark Paid' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.financials.accountant-salary.delete', $salary) }}" method="POST"
                                                  onsubmit="return confirm('Are you sure you want to delete this salary entry?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                                                <input type="hidden" name="to_date" value="{{ $toDate }}">
                                                <input type="hidden" name="conversion_rate" value="{{ $conversionRate }}">
                                                <button type="submit" 
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs font-semibold hover:bg-red-700 transition-colors">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No accountant salaries for the selected period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
