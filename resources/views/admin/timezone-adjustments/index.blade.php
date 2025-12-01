<x-app-layout pageTitle="Timezone Adjustments">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Timezone Adjustments</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Apply winter/summer time adjustments to timetables</p>
            </div>
        </div>

        <!-- Adjustment Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Apply Adjustment</h3>
            <form action="{{ route('timezone-adjustments.store') }}" method="POST" class="grid gap-4 md:grid-cols-3">
                @csrf
                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Timezone</label>
                    <select
                        name="timezone"
                        required
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    >
                        <option value="">Select timezone</option>
                        @foreach ($timezoneOptions as $tz => $label)
                            <option value="{{ $tz }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Adjustment</label>
                    <select
                        name="adjustment_hours"
                        required
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    >
                        <option value="">Select adjustment</option>
                        <option value="1">+1 Hour (Summer Time)</option>
                        <option value="-1">-1 Hour (Winter Time)</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Apply Adjustment
                    </button>
                </div>
            </form>
        </div>

        <!-- Adjustments History -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Adjustment History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Timezone</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Adjustment</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Applied At</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Applied By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($adjustments as $adjustment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $timezoneOptions[$adjustment->timezone] ?? $adjustment->timezone }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $adjustment->adjustment_hours > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300' }}">
                                        {{ $adjustment->adjustment_hours > 0 ? '+' : '' }}{{ $adjustment->adjustment_hours }} hour(s)
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $adjustment->applied_at->format('M d, Y H:i') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $adjustment->appliedBy->name ?? '—' }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No adjustments have been applied yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($adjustments->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $adjustments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>


