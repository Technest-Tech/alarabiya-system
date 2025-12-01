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
            teachers: {{ $teacherLookup->toJson(JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}
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
                        <div class="font-semibold" x-text="form.course_name || selectedEvent.course_name"></div>
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
            Alpine.data('calendarPage', ({ studentId, teacherId, exportUrl, teachers }) => ({
                eventModalOpen: false,
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
                selectedEvent: {
                    id: null,
                    student: '',
                    teacher: '',
                    course_name: '',
                    timezone: '',
                    displayDate: '',
                    displayTime: '',
                    studentTime: '',
                },
                errors: [],
                csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                exportUrl,
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

                closeEventModal() {
                    this.eventModalOpen = false;
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

