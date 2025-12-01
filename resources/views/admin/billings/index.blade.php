<x-app-layout pageTitle="Billing Management">
    @php
        $activeTab = $tab ?? 'automatic';
        $statusFilter = $statusFilter ?? null;
        $monthFilter = $monthFilter ?? null;
        $statusLabels = [
            'unpaid' => 'Unpaid',
            'paid' => 'Paid',
        ];
    @endphp

    <div class="space-y-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Billing Overview</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Track all student billings, send reminders, and manage payment statuses.
                </p>
            </div>

            <form method="GET" action="{{ route('admin.billings.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Month</label>
                    <select name="month" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                        <option value="">All months</option>
                        @foreach($availableMonths as $month)
                            <option value="{{ $month }}" @selected($monthFilter === $month)>
                                {{ \Carbon\Carbon::parse($month.'-01')->isoFormat('MMMM YYYY') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Status</label>
                    <select name="status" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm">
                        <option value="">All statuses</option>
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    Apply Filters
                </button>
            </form>
        </div>

        <div>
            <div class="inline-flex rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <a href="{{ route('admin.billings.index', array_merge(request()->except('page'), ['tab' => 'automatic'])) }}"
                   class="px-4 py-2 text-sm font-medium {{ $activeTab === 'automatic' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    Automatic Billings
                </a>
                <a href="{{ route('admin.billings.index', array_merge(request()->except('page'), ['tab' => 'manual'])) }}"
                   class="px-4 py-2 text-sm font-medium {{ $activeTab === 'manual' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    Manual Billings
                </a>
            </div>
        </div>

        @if ($activeTab === 'automatic')
            @include('admin.billings.partials.table', [
                'billingsByStatus' => $automaticBillings,
                'statusLabels' => $statusLabels,
                'type' => 'automatic',
            ])
        @else
            @include('admin.billings.partials.manual-panel', [
                'billingsByStatus' => $manualBillings,
                'statusLabels' => $statusLabels,
                'students' => $students,
            ])
        @endif
    </div>
</x-app-layout>


