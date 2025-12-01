<x-app-layout pageTitle="My Calendar">
    @php
        $studentFilter = $filters['student_id'] ?? null;
    @endphp

    <div
        x-data="teacherCalendarPage({
            studentId: '{{ $studentFilter }}',
            teacherId: '{{ $teacherId }}'
        })"
        class="space-y-6"
    >
        <!-- Header -->
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">My Calendar</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    View all your scheduled sessions and lessons.
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:p-6">
            <form method="GET" class="grid gap-4 md:grid-cols-2">
                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Student</label>
                    <select
                        name="student_id"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    >
                        <option value="">All students</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected($studentFilter === $student->id)>{{ $student->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                    >
                        Apply filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Calendar -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div
                data-calendar
                data-events-url="{{ route('teacher.timetables.events.index') }}"
                data-student-id="{{ $studentFilter }}"
                data-teacher-id="{{ $teacherId }}"
                class="calendar-root p-4"
            ></div>
        </div>

        <!-- Event Info Modal (Read-only) -->
        <div
            x-cloak
            x-show="eventModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 px-4 py-6"
            x-transition.opacity
        >
            <div
                x-show="eventModalOpen"
                x-transition
                class="relative w-full max-w-2xl rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Event Details</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            View session information.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200"
                        @click="closeEventModal"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-6 px-6 py-6">
                    <div class="rounded-xl bg-indigo-50 px-4 py-3 text-sm text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200">
                        <div class="font-semibold" x-text="selectedEvent.course_name || 'No course name'"></div>
                        <div class="mt-1 flex flex-wrap gap-4 text-xs">
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span><span class="font-semibold">Student:</span> <span x-text="selectedEvent.student || 'Unknown'"></span></span>
                            </span>
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2h-1V3a1 1 0 00-1-1h-2a1 1 0 00-1 1v2H9V3a1 1 0 00-1-1H6a1 1 0 00-1 1v2H4a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span x-text="selectedEvent.displayDate || '—'"></span>
                            </span>
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m4 0h1M12 6v6" />
                                </svg>
                                <span x-text="selectedEvent.displayTime || '—'"></span>
                            </span>
                            <template x-if="selectedEvent.studentTime">
                                <span class="flex items-center gap-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m-3-3l-3 3" />
                                    </svg>
                                    <span><span class="font-semibold">Student time:</span> <span x-text="selectedEvent.studentTime"></span></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <button
                            type="button"
                            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                            @click="closeEventModal"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('teacherCalendarPage', ({ studentId, teacherId }) => ({
                eventModalOpen: false,
                selectedEvent: {
                    student: '',
                    course_name: '',
                    timezone: '',
                    displayDate: '',
                    displayTime: '',
                    studentTime: '',
                },

                init() {
                    this.calendarEl = this.$root.querySelector('[data-calendar]');

                    window.addEventListener('timetable:event-click', (event) => {
                        this.populateEvent(event.detail);
                    });
                },

                populateEvent(detail) {
                    const timezone = detail.extendedProps.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone;
                    const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'full', timeZone: timezone });
                    const timeFormatter = new Intl.DateTimeFormat(undefined, { timeStyle: 'short', timeZone: timezone });

                    this.selectedEvent = {
                        student: detail.extendedProps.student || 'Unknown',
                        course_name: detail.extendedProps.course_name || 'No course name',
                        timezone,
                        displayDate: dateFormatter.format(new Date(detail.start)),
                        displayTime: `${timeFormatter.format(new Date(detail.start))} – ${timeFormatter.format(new Date(detail.end))}`,
                        studentTime: detail.extendedProps.student_time_display || '',
                    };

                    this.eventModalOpen = true;
                },

                closeEventModal() {
                    this.eventModalOpen = false;
                },
            }));
        });
    </script>
</x-app-layout>

