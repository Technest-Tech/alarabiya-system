<x-app-layout pageTitle="Today's Lessons">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <a 
                    href="{{ route('today-lessons.index', array_merge(request()->except(['date', 'page']), ['date' => $previousDay->format('Y-m-d')])) }}"
                    class="flex items-center justify-center h-10 w-10 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    title="Previous Day ({{ $previousDay->format('M d, Y') }})"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Today's Lessons</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $today }}
                        @if(!$isToday)
                            <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">(Not Today)</span>
                        @endif
                    </p>
                </div>
                <a 
                    href="{{ route('today-lessons.index', array_merge(request()->except(['date', 'page']), ['date' => $nextDay->format('Y-m-d')])) }}"
                    class="flex items-center justify-center h-10 w-10 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    title="Next Day ({{ $nextDay->format('M d, Y') }})"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
                @if(!$isToday)
                    <a 
                        href="{{ route('today-lessons.index', request()->except(['date', 'page'])) }}"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                        title="Go to Today"
                    >
                        Today
                    </a>
                @endif
            </div>
        </div>

        @php
            $currentYear = now()->year;
        @endphp

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="GET" class="grid gap-4 md:grid-cols-4">
                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Month</label>
                    <select
                        name="month"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    >
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($filters['month'] == $m)>
                                {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Year</label>
                    <select
                        name="year"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    >
                        @for ($y = $currentYear - 2; $y <= $currentYear + 2; $y++)
                            <option value="{{ $y }}" @selected($filters['year'] == $y)>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Student</label>
                    <select
                        name="student_id"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    >
                        <option value="">All students</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected($filters['student_id'] == $student->id)>
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Teacher</label>
                    <select
                        name="teacher_id"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    >
                        <option value="">All teachers</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected($filters['teacher_id'] == $teacher->id)>
                                {{ optional($teacher->user)->name ?? '—' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-3 md:col-span-4">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Apply Filters
                    </button>
                    <a
                        href="{{ route('today-lessons.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Today Only
                    </a>
                </div>
            </form>
        </div>

        <!-- Lessons Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Teacher</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Course</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky right-0 bg-gray-50 dark:bg-gray-700/50 z-10 border-l border-gray-200 dark:border-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($events as $event)
                            <tr class="transition-colors group @if($event['status'] === 'cancelled' || $event['status'] === 'cancelled_student' || $event['status'] === 'cancelled_teacher') bg-red-200 dark:bg-red-900/60 hover:bg-red-300 dark:hover:bg-red-900/80 @elseif($event['status'] === 'absent') bg-orange-200 dark:bg-orange-900/60 hover:bg-orange-300 dark:hover:bg-orange-900/80 @elseif($event['status'] === 'rescheduled') bg-yellow-200 dark:bg-yellow-900/60 hover:bg-yellow-300 dark:hover:bg-yellow-900/80 @elseif($event['status'] === 'attended') bg-green-200 dark:bg-green-900/60 hover:bg-green-300 dark:hover:bg-green-900/80 @else hover:bg-gray-50 dark:hover:bg-gray-700/50 @endif">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-base font-medium text-gray-900 dark:text-white">
                                        {{ $event['start_at']->format('M d, Y') }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $event['start_at']->format('l') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-base font-medium text-gray-900 dark:text-white">
                                        {{ $event['time'] }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-base font-semibold text-gray-900 dark:text-white">{{ $event['student'] }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $event['student_timezone'] }} • {{ $event['student_time'] }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-base font-semibold text-gray-900 dark:text-white">{{ $event['teacher'] ?? '—' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $event['teacher_timezone'] }} • {{ $event['teacher_time'] }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-base text-gray-900 dark:text-white">{{ $event['course_name'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium 
                                        @if($event['status'] === 'scheduled') bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300
                                        @elseif($event['status'] === 'cancelled' || $event['status'] === 'cancelled_student' || $event['status'] === 'cancelled_teacher') bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300
                                        @elseif($event['status'] === 'rescheduled') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300
                                        @elseif($event['status'] === 'absent') bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300
                                        @elseif($event['status'] === 'attended') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endif">
                                        @if($event['status'] === 'cancelled_student')
                                            Cancelled (Student)
                                        @elseif($event['status'] === 'cancelled_teacher')
                                            Cancelled (Teacher)
                                        @else
                                            {{ ucfirst($event['status']) }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium sticky right-0 z-10 border-l border-gray-200 dark:border-gray-600 @if($event['status'] === 'cancelled' || $event['status'] === 'cancelled_student' || $event['status'] === 'cancelled_teacher') bg-red-200 dark:bg-red-900 group-hover:bg-red-300 dark:group-hover:bg-red-800 @elseif($event['status'] === 'absent') bg-orange-200 dark:bg-orange-900 group-hover:bg-orange-300 dark:group-hover:bg-orange-800 @elseif($event['status'] === 'rescheduled') bg-yellow-200 dark:bg-yellow-900 group-hover:bg-yellow-300 dark:group-hover:bg-yellow-800 @elseif($event['status'] === 'attended') bg-green-200 dark:bg-green-900 group-hover:bg-green-300 dark:group-hover:bg-green-800 @else bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-700 @endif">
                                    <div class="flex items-center justify-end space-x-2">
                                        <div class="relative group/button">
                                            <button
                                                type="button"
                                                aria-label="Reschedule lesson"
                                                title="Reschedule"
                                                onclick="openRescheduleModal({{ $event['id'] }}, '{{ $event['start_at']->format('Y-m-d') }}', '{{ $event['start_at']->format('H:i') }}', '{{ $event['end_at']->format('H:i') }}')"
                                                class="p-1.5 rounded-md text-indigo-600 hover:bg-indigo-100 hover:text-indigo-900 dark:text-indigo-400 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-300 transition-colors"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                            <span class="pointer-events-none absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-sm font-medium text-white opacity-0 transition group-hover/button:opacity-100 group-focus-within/button:opacity-100 dark:bg-gray-700 z-20">
                                                Reschedule
                                            </span>
                                        </div>
                                        @if($event['status'] === 'cancelled' || $event['status'] === 'cancelled_student' || $event['status'] === 'cancelled_teacher')
                                            <div class="text-sm font-medium text-red-700 dark:text-red-300">
                                                @if($event['status'] === 'cancelled_student')
                                                    Cancelled (Student)
                                                @elseif($event['status'] === 'cancelled_teacher')
                                                    Cancelled (Teacher)
                                                @else
                                                    Cancelled
                                                @endif
                                            </div>
                                        @else
                                            <div class="relative group/button">
                                                <button
                                                    type="button"
                                                    aria-label="Cancel lesson"
                                                    title="Cancel"
                                                    onclick="openCancelModal({{ $event['id'] }})"
                                                    class="p-1.5 rounded-md text-red-600 hover:bg-red-100 hover:text-red-900 dark:text-red-400 dark:hover:bg-red-900/30 dark:hover:text-red-300 transition-colors"
                                                >
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                                <span class="pointer-events-none absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-sm font-medium text-white opacity-0 transition group-hover/button:opacity-100 group-focus-within/button:opacity-100 dark:bg-gray-700 z-20">
                                                    Cancel
                                                </span>
                                            </div>
                                        @endif
                                        <form action="{{ route('today-lessons.absent', $event['id']) }}" method="POST" class="inline" onsubmit="return confirm('Mark this lesson as absent?')">
                                            @csrf
                                            @method('POST')
                                            <div class="relative group/button">
                                                <button type="submit" aria-label="Mark lesson as absent" title="Mark as absent" class="p-1.5 rounded-md text-orange-600 hover:bg-orange-100 hover:text-orange-900 dark:text-orange-400 dark:hover:bg-orange-900/30 dark:hover:text-orange-300 transition-colors">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                                <span class="pointer-events-none absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-sm font-medium text-white opacity-0 transition group-hover/button:opacity-100 group-focus-within/button:opacity-100 dark:bg-gray-700 z-20">
                                                    Mark as absent
                                                </span>
                                            </div>
                                        </form>
                                        <form action="{{ route('today-lessons.attended', $event['id']) }}" method="POST" class="inline" onsubmit="return confirm('Mark this lesson as attended?')">
                                            @csrf
                                            @method('POST')
                                            <div class="relative group/button">
                                                <button type="submit" aria-label="Mark lesson as attended" title="Mark as attended" class="p-1.5 rounded-md text-green-600 hover:bg-green-100 hover:text-green-900 dark:text-green-400 dark:hover:bg-green-900/30 dark:hover:text-green-300 transition-colors">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                                <span class="pointer-events-none absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-sm font-medium text-white opacity-0 transition group-hover/button:opacity-100 group-focus-within/button:opacity-100 dark:bg-gray-700 z-20">
                                                    Mark as attended
                                                </span>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No lessons scheduled for today</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All lessons for today have been completed or there are no scheduled lessons.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($events->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                    {{ $events->links() }}
                </div>
            @endif
        </div>

        <!-- Cancel Modal -->
        <div id="cancelModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/40 px-4 py-6">
            <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Cancel Lesson</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Who is cancelling this lesson?</p>
                    </div>
                    <button type="button" onclick="closeCancelModal()" class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="cancelForm" method="POST" class="space-y-6 px-6 py-6">
                    @csrf
                    @method('POST')
                    <input type="hidden" name="event_id" id="cancel_event_id">
                    <input type="hidden" name="cancel_type" id="cancel_type">

                    <div class="space-y-3">
                        <button
                            type="button"
                            onclick="submitCancel('student')"
                            class="w-full rounded-xl border-2 border-red-200 bg-red-50 px-4 py-3 text-left text-sm font-semibold text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/30"
                        >
                            <div class="flex items-center justify-between">
                                <span>Cancel Student</span>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">Student cancelled this lesson</p>
                        </button>

                        <button
                            type="button"
                            onclick="submitCancel('teacher')"
                            class="w-full rounded-xl border-2 border-red-200 bg-red-50 px-4 py-3 text-left text-sm font-semibold text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/30"
                        >
                            <div class="flex items-center justify-between">
                                <span>Cancel Teacher</span>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">Teacher cancelled this lesson</p>
                        </button>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <button
                            type="button"
                            onclick="closeCancelModal()"
                            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reschedule Modal -->
        <div id="rescheduleModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/40 px-4 py-6">
            <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Reschedule Lesson</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Update the date and time for this lesson</p>
                    </div>
                    <button type="button" onclick="closeRescheduleModal()" class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="rescheduleForm" method="POST" class="space-y-6 px-6 py-6">
                    @csrf
                    @method('POST')
                    <input type="hidden" name="event_id" id="event_id">

                    <div class="flex flex-col">
                        <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                        <input
                            type="date"
                            name="date"
                            id="reschedule_date"
                            required
                            class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                        />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Start Time</label>
                            <input
                                type="time"
                                name="start_time"
                                id="reschedule_start_time"
                                required
                                class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                            />
                        </div>

                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">End Time</label>
                            <input
                                type="time"
                                name="end_time"
                                id="reschedule_end_time"
                                required
                                class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <button
                            type="button"
                            onclick="closeRescheduleModal()"
                            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Reschedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRescheduleModal(eventId, date, startTime, endTime) {
            document.getElementById('event_id').value = eventId;
            document.getElementById('reschedule_date').value = date;
            document.getElementById('reschedule_start_time').value = startTime;
            document.getElementById('reschedule_end_time').value = endTime;
            document.getElementById('rescheduleForm').action = `/admin/today-lessons/${eventId}/reschedule`;
            document.getElementById('rescheduleModal').classList.remove('hidden');
            document.getElementById('rescheduleModal').classList.add('flex');
        }

        function closeRescheduleModal() {
            document.getElementById('rescheduleModal').classList.add('hidden');
            document.getElementById('rescheduleModal').classList.remove('flex');
        }

        function openCancelModal(eventId) {
            document.getElementById('cancel_event_id').value = eventId;
            document.getElementById('cancelForm').action = `/admin/today-lessons/${eventId}/cancel`;
            document.getElementById('cancelModal').classList.remove('hidden');
            document.getElementById('cancelModal').classList.add('flex');
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
            document.getElementById('cancelModal').classList.remove('flex');
        }

        function submitCancel(cancelType) {
            const eventId = document.getElementById('cancel_event_id').value;
            document.getElementById('cancel_type').value = cancelType;
            document.getElementById('cancelForm').action = `/admin/today-lessons/${eventId}/cancel`;
            document.getElementById('cancelForm').submit();
        }
    </script>
</x-app-layout>


