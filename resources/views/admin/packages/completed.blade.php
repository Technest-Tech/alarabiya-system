<x-app-layout pageTitle="Completed Packages - {{ $student->name }}">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <a href="{{ route('admin.packages.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Completed Packages</h2>
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $student->name }} - View all completed and paid packages</p>
            </div>
        </div>

        <!-- Packages List -->
        <div class="space-y-4">
            @if($packages->count() > 0)
                @foreach($packages as $package)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Package Header -->
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Package #{{ $package->id }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $package->package_hours }} hours package
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $package->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' }}">
                                    {{ ucfirst($package->status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Package Details -->
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Hours Used</p>
                                    <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                        {{ number_format($package->hours_used_decimal, 2) }} / {{ $package->package_hours }} hrs
                                    </p>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $package->package_hours > 0 ? min(100, ($package->hours_used_decimal / $package->package_hours) * 100) : 0 }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Lessons</p>
                                    <p class="mt-1 text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $package->lessons->count() }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Started</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $package->started_at ? $package->started_at->format('M d, Y') : '—' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Completed</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $package->completed_at ? $package->completed_at->format('M d, Y') : '—' }}
                                    </p>
                                </div>
                            </div>

                            @if($package->paid_at)
                                <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                    <p class="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wide">Paid On</p>
                                    <p class="mt-1 text-sm font-semibold text-green-900 dark:text-green-100">
                                        {{ $package->paid_at->format('M d, Y') }} ({{ $package->paid_at->diffForHumans() }})
                                    </p>
                                </div>
                            @endif

                            <!-- Lessons List -->
                            @if($package->lessons->count() > 0)
                                <div class="mt-4">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Lessons in this Package</h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Duration</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cumulative</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach($package->lessons->sortBy('date')->sortBy('id') as $lesson)
                                                    @php
                                                        $statusColors = [
                                                            'attended' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
                                                            'absent_student' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                                                            'absent_teacher' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
                                                            'cancelled_student' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                            'cancelled_teacher' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                            'trial' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
                                                        ];
                                                        $statusLabels = [
                                                            'attended' => 'Attended',
                                                            'absent_student' => 'Absent (Student)',
                                                            'absent_teacher' => 'Absent (Teacher)',
                                                            'cancelled_student' => 'Cancelled (Student)',
                                                            'cancelled_teacher' => 'Cancelled (Teacher)',
                                                            'trial' => 'Trial',
                                                        ];
                                                        $color = $statusColors[$lesson->status] ?? 'bg-gray-100 text-gray-800';
                                                        $label = $statusLabels[$lesson->status] ?? ucfirst(str_replace('_', ' ', $lesson->status));
                                                    @endphp
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                            {{ $lesson->package_lesson_number ?? '—' }}
                                                        </td>
                                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                            {{ \Carbon\Carbon::parse($lesson->date)->format('M d, Y') }}
                                                        </td>
                                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                            {{ intdiv($lesson->duration_minutes, 60) }}h {{ $lesson->duration_minutes % 60 }}m
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                                                                {{ $label }}
                                                            </span>
                                                            @if($lesson->is_pending)
                                                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300">
                                                                    Pending
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                                            {{ $lesson->package_cumulative_hours ? number_format($lesson->package_cumulative_hours, 2) . ' hrs' : '—' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No completed packages</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This student has no completed or paid packages yet.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

