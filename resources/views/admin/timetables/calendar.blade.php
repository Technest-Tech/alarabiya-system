<x-app-layout pageTitle="Timetable Calendar">
    @php
        $studentFilter = $filters['student_id'] ?? null;
        $teacherFilter = $filters['teacher_id'] ?? null;
        $teacherLookup = $teachers->mapWithKeys(fn ($teacher) => [(string) $teacher->id => optional($teacher->user)->name ?? '—']);
    @endphp

    <div
        x-data="calendarPage({
            studentId: '{{ $studentFilter }}',
            teacherId: '{{ $teacherFilter }}',
            exportUrl: '{{ route('timetables.export') }}',
            teachers: {{ $teacherLookup->toJson(JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }},
            cancelUrl: '{{ route('today-lessons.cancel', '__EVENT__') }}',
            absentUrl: '{{ route('today-lessons.absent', '__EVENT__') }}',
            attendedUrl: '{{ route('today-lessons.attended', '__EVENT__') }}'
        })"
        class="space-y-6"
    >
        <!-- Header -->
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">Timetable Calendar</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Visualise all scheduled sessions, make quick adjustments, and export filtered calendars.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('timetables.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                    Back to Timetables
                </a>
                <button
                    type="button"
                    @click="handleExport"
                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 px-5 py-3 text-sm font-semibold text-white shadow transition hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Export to PDF
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:p-6">
            <form method="GET" class="grid gap-4 md:grid-cols-3">
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

                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Teacher</label>
                    <select
                        name="teacher_id"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    >
                        <option value="">All teachers</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected($teacherFilter === $teacher->id)>
                                {{ optional($teacher->user)->name ?? '—' }}
                            </option>
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
                data-events-url="{{ route('timetables.events.index') }}"
                data-update-url-template="{{ route('timetables.events.update', '__EVENT__') }}"
                data-destroy-url-template="{{ route('timetables.events.destroy', '__EVENT__') }}"
                data-store-url="{{ route('timetables.events.store') }}"
                data-export-url="{{ route('timetables.export') }}"
                data-student-id="{{ $studentFilter }}"
                data-teacher-id="{{ $teacherFilter }}"
                class="calendar-root p-4"
            ></div>
        </div>

        <!-- Toast -->
        <div
            x-show="toast.visible"
            x-transition
            class="fixed bottom-6 right-6 z-50"
            x-cloak
        >
            <div class="rounded-xl bg-gray-900 px-5 py-4 text-sm font-semibold text-white shadow-xl">
                <span x-text="toast.message"></span>
            </div>
        </div>

        <!-- Event Modal -->
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
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Event details</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Update this session or remove it from the calendar.
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
                        <div class="flex items-center justify-between">
                            <div class="font-semibold" x-text="form.course_name || selectedEvent.course_name"></div>
                            <span 
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="{
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300': selectedEvent.status === 'scheduled',
                                    'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300': selectedEvent.status === 'cancelled',
                                    'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300': selectedEvent.status === 'absent',
                                    'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300': selectedEvent.status === 'attended',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300': selectedEvent.status === 'rescheduled',
                                }"
                                x-text="selectedEvent.status ? selectedEvent.status.charAt(0).toUpperCase() + selectedEvent.status.slice(1) : 'Scheduled'"
                            ></span>
                        </div>
                        <div class="mt-1 flex flex-wrap gap-4 text-xs">
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span><span class="font-semibold">Student:</span> <span x-text="selectedEvent.student"></span></span>
                            </span>
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                                </svg>
                                <span><span class="font-semibold">Teacher:</span> <span x-text="teacherLabel"></span></span>
                            </span>
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2h-1V3a1 1 0 00-1-1h-2a1 1 0 00-1 1v2H9V3a1 1 0 00-1-1H6a1 1 0 00-1 1v2H4a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span x-text="selectedEvent.displayDate"></span>
                            </span>
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m4 0h1M12 6v6" />
                                </svg>
                                <span x-text="selectedEvent.displayTime"></span>
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

                    <!-- Status Actions -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-4 dark:border-gray-700 dark:bg-gray-800/50">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
                        <div class="flex flex-wrap items-center gap-3">
                            <template x-if="selectedEvent.status !== 'cancelled'">
                                <button 
                                    type="button"
                                    @click="handleStatusAction('cancel')"
                                    aria-label="Cancel lesson" 
                                    title="Cancel" 
                                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200 dark:hover:bg-red-900/30"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Cancel
                                </button>
                            </template>
                            <template x-if="selectedEvent.status !== 'absent'">
                                <button 
                                    type="button"
                                    @click="handleStatusAction('absent')"
                                    aria-label="Mark lesson as absent" 
                                    title="Mark as absent" 
                                    class="inline-flex items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-sm font-medium text-orange-600 transition hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-orange-400 dark:border-orange-800 dark:bg-orange-900/20 dark:text-orange-200 dark:hover:bg-orange-900/30"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Mark as Absent
                                </button>
                            </template>
                            <template x-if="selectedEvent.status !== 'attended'">
                                <button 
                                    type="button"
                                    @click="handleStatusAction('attended')"
                                    aria-label="Mark lesson as attended" 
                                    title="Mark as attended" 
                                    class="inline-flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-600 transition hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-400 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200 dark:hover:bg-green-900/30"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Mark as Attended
                                </button>
                            </template>
                            <template x-if="selectedEvent.status === 'cancelled' || selectedEvent.status === 'absent' || selectedEvent.status === 'attended'">
                                <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                                    This lesson has already been marked. Change the status using a different action above.
                                </p>
                            </template>
                        </div>
                    </div>

                    <template x-if="errors.length">
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
                            <ul class="list-disc pl-5">
                                <template x-for="error in errors" :key="error">
                                    <li x-text="error"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <form class="space-y-4" @submit.prevent="updateEvent">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                                <input
                                    type="date"
                                    x-model="form.date"
                                    class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    required
                                />
                            </div>

                            <div class="flex flex-col">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Course name</label>
                                <input
                                    type="text"
                                    x-model="form.course_name"
                                    class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    required
                                />
                            </div>

                            <div class="flex flex-col">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Start time</label>
                                <input
                                    type="time"
                                    x-model="form.start_time"
                                    class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    required
                                />
                            </div>

                            <div class="flex flex-col">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">End time</label>
                                <input
                                    type="time"
                                    x-model="form.end_time"
                                    class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    required
                                />
                            </div>

                            <div class="flex flex-col md:col-span-2">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Teacher</label>
                                <select
                                    x-model="form.teacher_id"
                                    class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    required
                                >
                                    <option value="">Select teacher</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ optional($teacher->user)->name ?? '—' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                            <button
                                type="button"
                                class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200"
                                @click="deleteEvent"
                            >
                                Delete this event
                            </button>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                    @click="closeEventModal"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Save changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Lesson Modal -->
        <div
            x-cloak
            x-show="addLessonModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 px-4 py-6"
            x-transition.opacity
        >
            <div
                x-show="addLessonModalOpen"
                x-transition
                class="relative w-full max-w-2xl rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Add New Lesson</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Create a single lesson for the selected date.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200"
                        @click="closeAddLessonModal"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-6 px-6 py-6">
                    <template x-if="errors.length && errors.filter(e => !e.toLowerCase().includes('undefined variable')).length > 0">
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200">
                            <ul class="list-disc pl-5">
                                <template x-for="error in errors.filter(e => !e.toLowerCase().includes('undefined variable'))" :key="error">
                                    <li x-text="error"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <form class="space-y-4" @submit.prevent="createEvent">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                                <input
                                    type="date"
                                    x-model="addLessonForm.date"
                                    class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    required
                                />
                            </div>

                            <div class="flex flex-col">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Course name</label>
                                <input
                                    type="text"
                                    x-model="addLessonForm.course_name"
                                    class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    required
                                />
                            </div>

                            <div class="flex flex-col">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Student</label>
                                <select
                                    x-model="addLessonForm.student_id"
                                    class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    required
                                >
                                    <option value="">Select student</option>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}">{{ $student->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex flex-col">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Teacher</label>
                                <select
                                    x-model="addLessonForm.teacher_id"
                                    class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    required
                                >
                                    <option value="">Select teacher</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ optional($teacher->user)->name ?? '—' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Teacher Time</h3>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="flex flex-col">
                                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Teacher Timezone</label>
                                    <select
                                        x-model="addLessonForm.teacher_timezone"
                                        @change="calculateStudentTimes()"
                                        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                        required
                                    >
                                        @foreach ($timezoneOptions as $tz => $label)
                                            <option value="{{ $tz }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex flex-col">
                                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Start time</label>
                                    <input
                                        type="time"
                                        x-model="addLessonForm.teacher_start_time"
                                        @change="calculateStudentTimes()"
                                        class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                        required
                                    />
                                </div>

                                <div class="flex flex-col">
                                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">End time</label>
                                    <input
                                        type="time"
                                        x-model="addLessonForm.teacher_end_time"
                                        @change="calculateStudentTimes()"
                                        class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Student Time</h3>
                            <div class="mb-4 flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="use_manual_time_diff"
                                    x-model="addLessonForm.use_manual_time_diff"
                                    @change="calculateStudentTimes()"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label for="use_manual_time_diff" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Use manual time difference for student time
                                </label>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="flex flex-col">
                                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Student Timezone (Optional)</label>
                                    <select
                                        x-model="addLessonForm.student_timezone"
                                        @change="calculateStudentTimes()"
                                        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    >
                                        <option value="">Not specified</option>
                                        @foreach ($timezoneOptions as $tz => $label)
                                            <option value="{{ $tz }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex flex-col">
                                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Student Start time</label>
                                    <input
                                        type="time"
                                        x-model="addLessonForm.student_start_time"
                                        :readonly="!addLessonForm.use_manual_time_diff && addLessonForm.student_timezone"
                                        :class="(!addLessonForm.use_manual_time_diff && addLessonForm.student_timezone) ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : ''"
                                        class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    />
                                    <p x-show="!addLessonForm.use_manual_time_diff && addLessonForm.student_timezone" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Auto-calculated
                                    </p>
                                </div>

                                <div class="flex flex-col">
                                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Student End time</label>
                                    <input
                                        type="time"
                                        x-model="addLessonForm.student_end_time"
                                        :readonly="!addLessonForm.use_manual_time_diff && addLessonForm.student_timezone"
                                        :class="(!addLessonForm.use_manual_time_diff && addLessonForm.student_timezone) ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : ''"
                                        class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                    />
                                    <p x-show="!addLessonForm.use_manual_time_diff && addLessonForm.student_timezone" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Auto-calculated
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <button
                                type="button"
                                class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                                @click="closeAddLessonModal"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Lesson
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Export Modal -->
        <div
            x-cloak
            x-show="exportModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 px-4 py-6"
            x-transition.opacity
        >
            <div
                x-show="exportModalOpen"
                x-transition
                class="w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Export Calendar</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Choose the period you want to include in the PDF.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200"
                        @click="exportModalOpen = false"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-4 transition hover:border-indigo-500 dark:border-gray-700 dark:hover:border-indigo-500">
                            <input type="radio" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500" name="export_preset" value="today" x-model="exportPreset">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Today</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Export today’s calendar</div>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-4 transition hover:border-indigo-500 dark:border-gray-700 dark:hover:border-indigo-500">
                            <input type="radio" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500" name="export_preset" value="week" x-model="exportPreset">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">This week</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Monday to Sunday</div>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-4 transition hover:border-indigo-500 dark:border-gray-700 dark:hover:border-indigo-500">
                            <input type="radio" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500" name="export_preset" value="month" x-model="exportPreset">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">This month</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Full calendar month</div>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-4 transition hover:border-indigo-500 dark:border-gray-700 dark:hover:border-indigo-500">
                            <input type="radio" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500" name="export_preset" value="custom" x-model="exportPreset">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Custom range</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Select start and end dates</div>
                            </div>
                        </label>
                    </div>

                    <div x-show="exportPreset === 'custom'" x-transition>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Start date</label>
                                <input
                                    type="date"
                                    x-model="customRange.start"
                                    class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                />
                            </div>
                            <div class="flex flex-col">
                                <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">End date</label>
                                <input
                                    type="date"
                                    x-model="customRange.end"
                                    class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <button
                        type="button"
                        class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        @click="exportModalOpen = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="submitExport"
                    >
                        Generate PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('calendarPage', ({ studentId, teacherId, exportUrl, teachers, cancelUrl, absentUrl, attendedUrl }) => ({
                eventModalOpen: false,
                addLessonModalOpen: false,
                exportModalOpen: false,
                filters: {
                    student_id: studentId || '',
                    teacher_id: teacherId || '',
                },
                toast: {
                    visible: false,
                    message: '',
                    timeout: null,
                },
                form: {
                    date: '',
                    start_time: '',
                    end_time: '',
                    teacher_id: '',
                    course_name: '',
                },
                addLessonForm: {
                    date: '',
                    teacher_timezone: @json(config('app.timezone')),
                    student_timezone: '',
                    teacher_start_time: '09:00',
                    teacher_end_time: '10:00',
                    student_start_time: '',
                    student_end_time: '',
                    use_manual_time_diff: false,
                    student_id: '',
                    teacher_id: '',
                    course_name: '',
                },
                selectedEvent: {
                    id: null,
                    student: '',
                    teacher: '',
                    course_name: '',
                    timezone: '',
                    displayDate: '',
                    displayTime: '',
                    studentTime: '',
                    status: 'scheduled',
                },
                errors: [],
                csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                exportUrl,
                cancelUrl,
                absentUrl,
                attendedUrl,
                exportPreset: 'month',
                customRange: {
                    start: '',
                    end: '',
                },
                teacherLookup: teachers || {},

                get teacherLabel() {
                    if (this.form.teacher_id) {
                        return this.teacherLookup[this.form.teacher_id] ?? 'Unassigned';
                    }

                    return this.selectedEvent.teacher || 'Unassigned';
                },

                init() {
                    this.calendarEl = this.$root.querySelector('[data-calendar]');

                    window.addEventListener('timetable:event-click', (event) => {
                        this.populateEvent(event.detail);
                    });

                    window.addEventListener('timetable:date-click', (event) => {
                        this.openAddLessonModal(event.detail);
                    });
                },

                populateEvent(detail) {
                    this.errors = [];
                    const timezone = detail.extendedProps.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone;
                    const dateFormatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'full', timeZone: timezone });
                    const timeFormatter = new Intl.DateTimeFormat(undefined, { timeStyle: 'short', timeZone: timezone });
                    const teacherId = detail.extendedProps.teacher_id ? String(detail.extendedProps.teacher_id) : '';
                    const teacherName = teacherId
                        ? this.teacherLookup[teacherId] ?? detail.extendedProps.teacher
                        : detail.extendedProps.teacher;
                    const studentTime = detail.extendedProps.student_time_display || '';

                    this.selectedEvent = {
                        id: detail.id,
                        student: detail.extendedProps.student,
                        teacher: teacherName,
                        course_name: detail.extendedProps.course_name,
                        timezone,
                        displayDate: dateFormatter.format(new Date(detail.start)),
                        displayTime: `${timeFormatter.format(new Date(detail.start))} – ${timeFormatter.format(new Date(detail.end))}`,
                        studentTime,
                        status: detail.extendedProps.status || 'scheduled',
                    };

                    this.form = {
                        date: detail.start.slice(0, 10),
                        start_time: detail.start.slice(11, 16),
                        end_time: detail.end.slice(11, 16),
                        teacher_id: teacherId,
                        course_name: detail.extendedProps.course_name,
                    };

                    this.eventModalOpen = true;
                },

                async updateEvent() {
                    if (!this.selectedEvent.id) return;

                    try {
                        const response = await fetch(
                            this.calendarEl.dataset.updateUrlTemplate.replace('__EVENT__', this.selectedEvent.id),
                            {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify(this.form),
                            }
                        );

                        const data = await response.json();

                        if (!response.ok) {
                            this.errors = Object.values(data.errors || { error: [data.message || 'Unable to update event.'] }).flat();
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('timetable:event-updated', { detail: data }));
                        this.showToast('Event updated');
                        this.closeEventModal();
                    } catch (error) {
                        this.errors = ['Unexpected error updating the event.'];
                        console.error(error);
                    }
                },

                async deleteEvent() {
                    if (!this.selectedEvent.id || !confirm('Delete this event?')) {
                        return;
                    }

                    try {
                        const response = await fetch(
                            this.calendarEl.dataset.destroyUrlTemplate.replace('__EVENT__', this.selectedEvent.id),
                            {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            }
                        );

                        if (!response.ok) {
                            this.errors = ['Unable to delete the event.'];
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('timetable:event-removed', { detail: { id: this.selectedEvent.id } }));
                        this.showToast('Event deleted');
                        this.closeEventModal();
                    } catch (error) {
                        this.errors = ['Unexpected error deleting the event.'];
                        console.error(error);
                    }
                },

                openAddLessonModal(detail) {
                    this.errors = [];
                    const clickedDate = detail.date || detail.dateTime;
                    const dateStr = clickedDate.slice(0, 10);
                    
                    this.addLessonForm = {
                        date: dateStr,
                        teacher_timezone: @json(config('app.timezone')),
                        student_timezone: '',
                        teacher_start_time: '09:00',
                        teacher_end_time: '10:00',
                        student_start_time: '',
                        student_end_time: '',
                        use_manual_time_diff: false,
                        student_id: this.filters.student_id || '',
                        teacher_id: this.filters.teacher_id || '',
                        course_name: '',
                    };
                    
                    this.addLessonModalOpen = true;
                },

                calculateStudentTimes() {
                    if (this.addLessonForm.use_manual_time_diff) {
                        // Don't auto-calculate if manual mode is enabled
                        return;
                    }

                    if (!this.addLessonForm.teacher_timezone || !this.addLessonForm.student_timezone) {
                        // Clear student times if timezones are not set
                        this.addLessonForm.student_start_time = '';
                        this.addLessonForm.student_end_time = '';
                        return;
                    }

                    if (!this.addLessonForm.teacher_start_time || !this.addLessonForm.teacher_end_time || !this.addLessonForm.date) {
                        return;
                    }

                    try {
                        // Parse date and time components
                        const [year, month, day] = this.addLessonForm.date.split('-');
                        const [startHour, startMin] = this.addLessonForm.teacher_start_time.split(':');
                        const [endHour, endMin] = this.addLessonForm.teacher_end_time.split(':');

                        // Create date strings in ISO format (assumed to be in teacher timezone)
                        const teacherStartISO = `${year}-${month}-${day}T${startHour.padStart(2, '0')}:${startMin.padStart(2, '0')}:00`;
                        const teacherEndISO = `${year}-${month}-${day}T${endHour.padStart(2, '0')}:${endMin.padStart(2, '0')}:00`;

                        // Convert: teacher timezone -> UTC -> student timezone
                        // Step 1: Create a date object representing the time in teacher timezone
                        // We do this by creating a UTC date and adjusting for teacher timezone offset
                        const teacherStartUTC = this.getUTCForTimezoneTime(teacherStartISO, this.addLessonForm.teacher_timezone);
                        const teacherEndUTC = this.getUTCForTimezoneTime(teacherEndISO, this.addLessonForm.teacher_timezone);

                        // Step 2: Format UTC dates in student timezone
                        const studentStartTime = this.formatTimeInTimezone(teacherStartUTC, this.addLessonForm.student_timezone);
                        const studentEndTime = this.formatTimeInTimezone(teacherEndUTC, this.addLessonForm.student_timezone);

                        this.addLessonForm.student_start_time = studentStartTime;
                        this.addLessonForm.student_end_time = studentEndTime;
                    } catch (error) {
                        console.error('Error calculating student times:', error);
                        this.addLessonForm.student_start_time = '';
                        this.addLessonForm.student_end_time = '';
                    }
                },

                getUTCForTimezoneTime(dateTimeStr, timezone) {
                    // Parse the date-time string
                    const [datePart, timePart] = dateTimeStr.split('T');
                    const [year, month, day] = datePart.split('-');
                    const [hour, minute] = timePart.split(':');

                    // Create a date object in UTC
                    const utcDate = new Date(Date.UTC(year, month - 1, day, hour, minute, 0));

                    // Get what this UTC time displays as in the target timezone
                    const formatter = new Intl.DateTimeFormat('en-US', {
                        timeZone: timezone,
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                    });

                    const parts = formatter.formatToParts(utcDate);
                    const tzYear = parseInt(parts.find(p => p.type === 'year').value);
                    const tzMonth = parseInt(parts.find(p => p.type === 'month').value);
                    const tzDay = parseInt(parts.find(p => p.type === 'day').value);
                    const tzHour = parseInt(parts.find(p => p.type === 'hour').value);
                    const tzMinute = parseInt(parts.find(p => p.type === 'minute').value);

                    // Create a date representing the timezone time in UTC
                    const tzDateUTC = new Date(Date.UTC(tzYear, tzMonth - 1, tzDay, tzHour, tzMinute, 0));

                    // Calculate offset
                    const offset = utcDate.getTime() - tzDateUTC.getTime();

                    // Return the UTC date adjusted to represent the timezone time
                    return new Date(utcDate.getTime() - offset);
                },

                formatTimeInTimezone(date, timezone) {
                    const formatter = new Intl.DateTimeFormat('en-US', {
                        timeZone: timezone,
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false,
                    });

                    const parts = formatter.formatToParts(date);
                    const hour = parts.find(p => p.type === 'hour').value;
                    const minute = parts.find(p => p.type === 'minute').value;

                    return `${hour}:${minute}`;
                },

                closeAddLessonModal() {
                    this.addLessonModalOpen = false;
                    this.errors = [];
                },

                async createEvent() {
                    try {
                        // Prepare form data - ensure times are in HH:mm format and boolean is correct
                        const formData = {
                            date: this.addLessonForm.date,
                            teacher_timezone: this.addLessonForm.teacher_timezone,
                            student_timezone: this.addLessonForm.student_timezone || null,
                            teacher_start_time: this.addLessonForm.teacher_start_time,
                            teacher_end_time: this.addLessonForm.teacher_end_time,
                            student_start_time: this.addLessonForm.student_start_time || null,
                            student_end_time: this.addLessonForm.student_end_time || null,
                            use_manual_time_diff: this.addLessonForm.use_manual_time_diff,
                            student_id: this.addLessonForm.student_id,
                            teacher_id: this.addLessonForm.teacher_id,
                            course_name: this.addLessonForm.course_name,
                        };

                        const response = await fetch(
                            '{{ route('timetables.events.store') }}',
                            {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify(formData),
                            }
                        );

                        const data = await response.json();

                        if (!response.ok) {
                            // Handle validation errors
                            if (data.errors) {
                                const errorMessages = [];
                                Object.keys(data.errors).forEach(key => {
                                    if (Array.isArray(data.errors[key])) {
                                        data.errors[key].forEach(err => {
                                            // Filter out undefined variable errors
                                            if (!err.toLowerCase().includes('undefined variable')) {
                                                errorMessages.push(err);
                                            }
                                        });
                                    } else {
                                        if (!String(data.errors[key]).toLowerCase().includes('undefined variable')) {
                                            errorMessages.push(data.errors[key]);
                                        }
                                    }
                                });
                                this.errors = errorMessages;
                            } else {
                                const message = data.message || 'Unable to create event.';
                                if (!message.toLowerCase().includes('undefined variable')) {
                                    this.errors = [message];
                                } else {
                                    this.errors = [];
                                }
                            }
                            return;
                        }

                        window.dispatchEvent(new CustomEvent('timetable:event-updated', { detail: data }));
                        this.showToast('Lesson added successfully');
                        this.closeAddLessonModal();
                    } catch (error) {
                        console.error('Error creating event:', error);
                        this.errors = ['Unexpected error creating the event. Please try again.'];
                    }
                },

                closeEventModal() {
                    this.eventModalOpen = false;
                },

                async handleStatusAction(action) {
                    if (!this.selectedEvent.id) return;

                    const actionMessages = {
                        cancel: 'Are you sure you want to cancel this lesson?',
                        absent: 'Mark this lesson as absent?',
                        attended: 'Mark this lesson as attended?',
                    };

                    const successMessages = {
                        cancel: 'Lesson cancelled successfully.',
                        absent: 'Lesson marked as absent.',
                        attended: 'Lesson marked as attended.',
                    };

                    const urlMap = {
                        cancel: this.cancelUrl,
                        absent: this.absentUrl,
                        attended: this.attendedUrl,
                    };

                    if (!confirm(actionMessages[action])) {
                        return;
                    }

                    try {
                        const url = urlMap[action].replace('__EVENT__', this.selectedEvent.id);
                        const formData = new FormData();
                        formData.append('_token', this.csrf);
                        formData.append('_method', 'POST');

                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        });

                        // Handle both success (200) and redirect (302) responses
                        if (!response.ok && response.status !== 302) {
                            try {
                                const data = await response.json();
                                this.errors = [data.message || `Unable to ${action} the lesson.`];
                            } catch (e) {
                                this.errors = [`Unable to ${action} the lesson.`];
                            }
                            return;
                        }

                        // Update the status in the modal immediately
                        this.selectedEvent.status = action === 'cancel' ? 'cancelled' : action;
                        
                        // Refresh the calendar to show updated status
                        window.dispatchEvent(new CustomEvent('timetable:refresh'));
                        this.showToast(successMessages[action]);
                        // Keep modal open so user can see the status change
                    } catch (error) {
                        this.errors = [`Unexpected error ${action}ing the lesson.`];
                        console.error(error);
                    }
                },

                showToast(message) {
                    clearTimeout(this.toast.timeout);
                    this.toast.message = message;
                    this.toast.visible = true;
                    this.toast.timeout = setTimeout(() => {
                        this.toast.visible = false;
                    }, 2500);
                },

                handleExport() {
                    if (this.filters.student_id || this.filters.teacher_id) {
                        this.submitExportWithFilters();
                    } else {
                        this.exportPreset = 'month';
                        this.customRange = { start: '', end: '' };
                        this.exportModalOpen = true;
                    }
                },

                submitExport() {
                    const params = new URLSearchParams();
                    params.set('preset', this.exportPreset);
                    if (this.exportPreset === 'custom') {
                        if (this.customRange.start) {
                            params.set('custom_start', this.customRange.start);
                        }
                        if (this.customRange.end) {
                            params.set('custom_end', this.customRange.end);
                        }
                    }

                    window.location = `${this.exportUrl}?${params.toString()}`;
                    this.exportModalOpen = false;
                },

                submitExportWithFilters() {
                    const params = new URLSearchParams();
                    if (this.filters.student_id) params.set('student_id', this.filters.student_id);
                    if (this.filters.teacher_id) params.set('teacher_id', this.filters.teacher_id);

                    const viewStart = this.calendarEl.dataset.viewStart;
                    const viewEnd = this.calendarEl.dataset.viewEnd;
                    if (viewStart && viewEnd) {
                        params.set('start', viewStart);
                        params.set('end', viewEnd);
                    }

                    window.location = `${this.exportUrl}?${params.toString()}`;
                },
            }));
        });
    </script>
</x-app-layout>

