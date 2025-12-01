<x-app-layout pageTitle="Teacher Salaries">
    <div class="space-y-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Teacher Salaries</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Review monthly teaching activity and calculate payouts.
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <form method="GET" class="flex items-center gap-2">
                    <input type="month" name="month" value="{{ $month }}"
                           class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                        Apply
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.teacher-salaries.apply-exchange-rate') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}">
                    <label for="exchange_rate" class="text-sm font-medium text-gray-700 dark:text-gray-300">سعر الصرف:</label>
                    <input 
                        type="number" 
                        name="exchange_rate" 
                        id="exchange_rate"
                        step="0.01" 
                        min="0" 
                        value="{{ old('exchange_rate', session('exchange_rate', '')) }}"
                        placeholder="مثال: 50.5"
                        class="w-32 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm px-3 py-2"
                        required
                    >
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-blue-700 transition-colors">
                        Apply
                    </button>
                </form>
                <a href="{{ route('admin.teacher-salaries.export', ['month' => $month]) }}"
                   class="inline-flex items-center px-4 py-2 border border-green-500 text-green-600 dark:text-green-400 rounded-lg text-sm font-semibold hover:bg-green-50 dark:hover:bg-green-900/10 transition-colors">
                    Export to Excel
                </a>
            </div>
        </div>

        @if($availableMonths->isNotEmpty())
            <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="font-semibold uppercase tracking-wide">Available:</span>
                @foreach ($availableMonths as $available)
                    <a href="{{ route('admin.teacher-salaries.index', ['month' => $available]) }}"
                       class="px-2 py-1 rounded-full border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $available === $month ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                        {{ \Carbon\Carbon::createFromFormat('Y-m', $available)->isoFormat('MMM YYYY') }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-800">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Payout</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">EGP {{ number_format($totalPayout, 2) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $monthLabel }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Teacher</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lessons</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Hours</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hourly Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Salary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($summaries as $summary)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $summary['teacher']->user?->name ?? 'Unassigned' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $summary['teacher']->user?->email ?? 'No email' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $summary['lessons'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ number_format($summary['total_hours'], 2) }} hrs
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $summary['currency'] ?? 'EGP' }} {{ number_format($summary['hourly_rate'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                    EGP {{ number_format($summary['salary_amount'], 2) }}
                                    @if(($summary['currency'] ?? 'EGP') === 'USD')
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(USD converted)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $summary['status'] === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' }}">
                                        {{ ucfirst($summary['status']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($summary['status'] === 'pending')
                                            <form action="{{ route('admin.teacher-salaries.markPaid', $summary['record']) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="month" value="{{ $month }}">
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700 transition-colors">
                                                    Mark as Paid
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.teacher-salaries.markUnpaid', $summary['record']) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="month" value="{{ $month }}">
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                                    Mark as Pending
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No salary data available for {{ $monthLabel }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>


