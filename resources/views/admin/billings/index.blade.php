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
        <div class="relative bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-800 rounded-3xl p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
            <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-indigo-400/20 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col xl:flex-row xl:items-center xl:justify-end gap-8">
                <form method="GET" action="{{ route('admin.billings.index') }}" class="flex flex-col sm:flex-row items-end gap-4 bg-white/10 p-5 rounded-2xl backdrop-blur-md border border-white/20 shadow-inner">
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-bold text-indigo-100 uppercase tracking-widest mb-2">Month</label>
                        <select name="month" class="w-full sm:w-48 rounded-xl border-0 bg-white/90 text-gray-900 shadow-sm focus:ring-4 focus:ring-purple-400/50 text-sm transition-all font-medium">
                            <option value="">All months</option>
                            @foreach($availableMonths as $month)
                                <option value="{{ $month }}" @selected($monthFilter === $month)>
                                    {{ \Carbon\Carbon::parse($month.'-01')->isoFormat('MMMM YYYY') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-bold text-indigo-100 uppercase tracking-widest mb-2">Status</label>
                        <select name="status" class="w-full sm:w-40 rounded-xl border-0 bg-white/90 text-gray-900 shadow-sm focus:ring-4 focus:ring-purple-400/50 text-sm transition-all font-medium">
                            <option value="">All statuses</option>
                            @foreach($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 bg-white text-indigo-700 hover:bg-indigo-50 hover:text-indigo-800 text-sm font-bold rounded-xl shadow-lg hover:shadow-xl transition-all focus:outline-none focus:ring-4 focus:ring-white/40 transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Filter
                    </button>
                </form>

                <a href="{{ route('admin.billings.export', ['tab' => $activeTab, 'month' => $monthFilter, 'status' => $statusFilter]) }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 bg-emerald-500 text-white hover:bg-emerald-600 text-sm font-bold rounded-xl shadow-lg hover:shadow-xl transition-all focus:outline-none focus:ring-4 focus:ring-emerald-300/50 transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Export {{ $monthFilter ? \Carbon\Carbon::parse($monthFilter.'-01')->isoFormat('MMMM YYYY') : 'All Months' }}
                </a>
            </div>
        </div>

        <div class="flex justify-center sm:justify-start">
            <div class="inline-flex rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden bg-white/50 dark:bg-gray-800/50 p-1.5 backdrop-blur-sm">
                <a href="{{ route('admin.billings.index', array_merge(request()->except('page'), ['tab' => 'automatic'])) }}"
                   class="px-6 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 flex items-center {{ $activeTab === 'automatic' ? 'bg-indigo-600 text-white shadow-md transform scale-105' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900' }}">
                    <svg class="w-4 h-4 mr-2 {{ $activeTab === 'automatic' ? 'text-indigo-200' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Automatic Billings
                </a>
                <a href="{{ route('admin.billings.index', array_merge(request()->except('page'), ['tab' => 'manual'])) }}"
                   class="px-6 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 flex items-center {{ $activeTab === 'manual' ? 'bg-indigo-600 text-white shadow-md transform scale-105' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900' }}">
                    <svg class="w-4 h-4 mr-2 {{ $activeTab === 'manual' ? 'text-indigo-200' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
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


