<x-app-layout pageTitle="Package Notifications">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Package Notifications</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Students who have completed their packages</p>
            </div>
        </div>

        <!-- Notifications Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($completedPackages as $package)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold text-lg">
                                {{ strtoupper(substr($package->student->name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $package->student->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $package->student->whatsapp_number }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                            Completed
                        </span>
                    </div>

                    <!-- Teacher Info -->
                    <div class="mb-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Teacher</p>
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ $package->student->teacher?->user?->name ?? 'Unassigned' }}
                        </p>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Package</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $package->package_hours }} hrs</p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Hours Used</p>
                            <p class="mt-1 text-xl font-bold text-indigo-600 dark:text-indigo-400">
                                {{ number_format($package->hours_used_decimal, 2) }} hrs
                            </p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Cost</p>
                            <p class="mt-1 text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ $package->student->currency ?? 'USD' }} {{ number_format($package->package_hours * $package->student->hourly_rate, 2) }}</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>Usage</span>
                            <span>{{ $package->package_hours > 0 ? round(($package->hours_used_decimal / $package->package_hours) * 100) : 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-2 rounded-full transition-all duration-300" style="width: {{ $package->package_hours > 0 ? min(100, ($package->hours_used_decimal / $package->package_hours) * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    <!-- Package Info -->
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Completed:</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $package->completed_at ? $package->completed_at->format('M d, Y') : '—' }}
                            </span>
                        </div>
                        @if($package->completed_at)
                            <div class="text-xs text-gray-500 dark:text-gray-400 text-right">
                                {{ $package->completed_at->diffForHumans() }}
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Pending Lessons:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $package->student->pending_lessons_count > 0 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $package->student->pending_lessons_count }} pending
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <form action="{{ route('admin.package-notifications.mark-paid', $package) }}" method="POST" onsubmit="return confirm('Mark this package as paid and create a new package? This will activate all pending lessons.')">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-green-700 dark:hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-green-400 transition-colors" style="background-color: #16a34a;">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Mark as Paid
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No completed packages</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All students have active packages or have already been paid.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($completedPackages->hasPages())
            <div class="mt-6">
                {{ $completedPackages->links() }}
            </div>
        @endif
    </div>
</x-app-layout>

