@php
    use Illuminate\Support\Str;

    $timetablesPayload = $timetables->map(function ($timetable) {
        $dayTimes = $timetable->day_times ?? [];
        $formattedDayTimes = [];
        if (!empty($dayTimes) && is_array($dayTimes)) {
            foreach ($dayTimes as $day => $times) {
                $formattedDayTimes[$day] = [
                    'start_time' => isset($times['start_time']) ? Str::of($times['start_time'])->substr(0, 5)->value() : '',
                    'end_time' => isset($times['end_time']) ? Str::of($times['end_time'])->substr(0, 5)->value() : '',
                ];
            }
        }
        
        return [
            'id' => $timetable->id,
            'student_id' => $timetable->student_id,
            'teacher_id' => $timetable->teacher_id,
            'course_name' => $timetable->course_name,
            'timezone' => $timetable->timezone,
            'teacher_timezone' => $timetable->teacher_timezone,
            'start_time' => Str::of($timetable->start_time)->substr(0, 5)->value(),
            'end_time' => Str::of($timetable->end_time)->substr(0, 5)->value(),
            'day_times' => $formattedDayTimes,
            'use_per_day_times' => !empty($formattedDayTimes),
            'start_date' => optional($timetable->start_date)->format('Y-m-d'),
            'end_date' => optional($timetable->end_date)->format('Y-m-d'),
            'days_of_week' => $timetable->days_of_week ?? [],
            'time_difference_hours' => $timetable->time_difference_hours,
            'use_manual_time_diff' => $timetable->use_manual_time_diff ?? false,
            'student_time_from' => $timetable->student_time_from,
            'student_time_to' => $timetable->student_time_to,
            'update_url' => route('timetables.update', $timetable),
        ];
    });
@endphp

<x-app-layout pageTitle="Students Timetables">
    <div
        x-data="timetablePage({
            timetables: {{ $timetablesPayload->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }},
            storeUrl: '{{ route('timetables.store') }}'
        })"
        class="space-y-6"
    >
        <!-- Header -->
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">Students Timetables</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage recurring schedules, teachers, and availability across the academy.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('timetables.calendar') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                    </svg>
                    View Calendar
                </a>
                <button
                    type="button"
                    @click="openCreate"
                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 px-5 py-3 text-sm font-medium text-white shadow-md transition hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12M6 12h12" />
                    </svg>
                    Add Timetable
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:p-6">
            <form method="GET" class="grid gap-4 md:grid-cols-4">
                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Student</label>
                    <select
                        name="student_id"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    >
                        <option value="">All students</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected($filters['student_id'] === $student->id)>
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
                            <option value="{{ $teacher->id }}" @selected($filters['teacher_id'] === $teacher->id)>
                                {{ optional($teacher->user)->name ?? '—' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">From date</label>
                    <input
                        type="date"
                        name="starts_from"
                        value="{{ $filters['starts_from'] ? $filters['starts_from']->format('Y-m-d') : '' }}"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                    />
                </div>

                <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">To date</label>
                    <div class="flex gap-2">
                        <input
                            type="date"
                            name="ends_to"
                            value="{{ $filters['ends_to'] ? $filters['ends_to']->format('Y-m-d') : '' }}"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                        />
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                        >
                            Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Timetables Table -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Student
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Teacher
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Course
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Time
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Repeat Days
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Range
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse ($timetables as $timetable)
                            @php
                                $formatDisplayTime = function (?string $time): string {
                                    return $time ? \Carbon\Carbon::today()->setTimeFromTimeString($time)->format('g:i A') : '—';
                                };
                                $teacherStartTime = $formatDisplayTime($timetable->start_time);
                                $teacherEndTime = $formatDisplayTime($timetable->end_time);
                                $studentStartTime = $formatDisplayTime($timetable->student_time_from ?? $timetable->start_time);
                                $studentEndTime = $formatDisplayTime($timetable->student_time_to ?? $timetable->end_time);
                            @endphp
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                                            {{ Str::upper(Str::substr($timetable->student->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold">{{ $timetable->student->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $timetable->timezone ?? 'Timezone N/A' }} • {{ $studentStartTime }} – {{ $studentEndTime }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <div class="font-semibold">{{ $timetable->teacher?->user?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $timetable->teacher_timezone ?? 'Timezone N/A' }} • {{ $teacherStartTime }} – {{ $teacherEndTime }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $timetable->course_name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $teacherStartTime }} – {{ $teacherEndTime }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($timetable->days_of_week ?? [] as $day)
                                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
                                                {{ Str::ucfirst($day) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ optional($timetable->start_date)->format('M d, Y') }} – {{ optional($timetable->end_date)->format('M d, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @if($timetable->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300">
                                            Inactive
                                        </span>
                                        @if($timetable->deactivated_until)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Until: {{ $timetable->deactivated_until->format('M d, Y') }}
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-3">
                                        <button
                                            type="button"
                                            @click="openEdit({{ $timetable->id }})"
                                            class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-900/40 dark:text-indigo-200 dark:hover:bg-indigo-900/60"
                                        >
                                            Edit
                                        </button>
                                        @if($timetable->is_active)
                                            <form
                                                action="{{ route('timetables.deactivate', $timetable) }}"
                                                method="POST"
                                                onsubmit="return confirm('Deactivate this timetable? Future events will be deleted.')"
                                            >
                                                @csrf
                                                @method('POST')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-lg bg-yellow-50 px-3 py-1.5 text-xs font-semibold text-yellow-600 transition hover:bg-yellow-100 dark:bg-yellow-900/40 dark:text-yellow-300 dark:hover:bg-yellow-900/60"
                                                >
                                                    Deactivate
                                                </button>
                                            </form>
                                        @else
                                            <form
                                                action="{{ route('timetables.reactivate', $timetable) }}"
                                                method="POST"
                                            >
                                                @csrf
                                                @method('POST')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-lg bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-600 transition hover:bg-green-100 dark:bg-green-900/40 dark:text-green-300 dark:hover:bg-green-900/60"
                                                >
                                                    Reactivate
                                                </button>
                                            </form>
                                        @endif
                                        <form
                                            action="{{ route('timetables.destroy', $timetable) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this timetable and all related events?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-100 dark:bg-red-900/40 dark:text-red-300 dark:hover:bg-red-900/60"
                                            >
                                                Delete All
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-300">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2h-1V3a1 1 0 00-1-1h-2a1 1 0 00-1 1v2H9V3a1 1 0 00-1-1H6a1 1 0 00-1 1v2H4a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-base font-medium text-gray-900 dark:text-gray-100">No timetables yet</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start by creating a timetable for a student.</p>
                                    <div class="mt-6">
                                        <button
                                            type="button"
                                            @click="openCreate"
                                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                        >
                                            Add Timetable
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($timetables->hasPages())
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/40">
                    {{ $timetables->links() }}
                </div>
            @endif
        </div>

        <!-- Modal -->
        <div
            x-cloak
            x-show="modalOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 px-4 py-6"
        >
            <div
                x-show="modalOpen"
                x-transition
                class="relative flex w-full max-w-3xl max-h-[90vh] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white" x-text="isEdit ? 'Update Timetable' : 'Create Timetable'"></h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Choose the days, date range, and timezone to generate recurring sessions automatically.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200"
                        @click="closeModal"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form
                    :action="formAction"
                    method="POST"
                    class="flex-1 space-y-6 overflow-y-auto px-6 py-6"
                >
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="use_per_day_times" :value="form.use_per_day_times ? '1' : '0'">

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Student</label>
                            <select
                                name="student_id"
                                x-model="form.student_id"
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
                                name="teacher_id"
                                x-model="form.teacher_id"
                                class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                required
                            >
                                <option value="">Select teacher</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">
                                        {{ optional($teacher->user)->name ?? '—' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Course name</label>
                            <input
                                type="text"
                                name="course_name"
                                x-model="form.course_name"
                                placeholder="e.g. Quran recital"
                                class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                required
                            />
                        </div>

                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Teacher Timezone</label>
                            <select
                                name="teacher_timezone"
                                x-model="form.teacher_timezone"
                                class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                required
                            >
                                <option value="">Choose timezone</option>
                                @foreach ($timezoneOptions as $tz => $label)
                                    <option value="{{ $tz }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Student Timezone (Optional)</label>
                            <select
                                name="timezone"
                                x-model="form.timezone"
                                @change="updateStudentTimeCalculation()"
                                class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                            >
                                <option value="">Not specified (use manual)</option>
                                @foreach ($timezoneOptions as $tz => $label)
                                    <option value="{{ $tz }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col" x-show="!form.timezone">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Manual Time Difference (Hours)</label>
                            <input
                                type="number"
                                name="time_difference_hours"
                                x-model="form.time_difference_hours"
                                @input="updateStudentTimeCalculation()"
                                min="-12"
                                max="12"
                                step="1"
                                placeholder="e.g. -3, +5"
                                class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                            />
                        </div>

                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Calculated Student Start Time</label>
                            <input
                                type="text"
                                x-model="form.student_time_from_display"
                                readonly
                                class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
                            />
                        </div>

                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Calculated Student End Time</label>
                            <input
                                type="text"
                                x-model="form.student_time_to_display"
                                readonly
                                class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
                            />
                        </div>

                        <input type="hidden" name="use_manual_time_diff" x-model="form.use_manual_time_diff" />
                        <input type="hidden" name="student_time_from" x-model="form.student_time_from" />
                        <input type="hidden" name="student_time_to" x-model="form.student_time_to" />

                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Start date</label>
                            <input
                                type="date"
                                name="start_date"
                                x-model="form.start_date"
                                class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                required
                            />
                        </div>

                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">End date</label>
                            <input
                                type="date"
                                name="end_date"
                                x-model="form.end_date"
                                class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                required
                            />
                        </div>

                        <div class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Time Mode</label>
                            <div class="flex gap-4">
                                <label class="flex items-center cursor-pointer">
                                    <input
                                        type="radio"
                                        name="use_per_day_times"
                                        value="0"
                                        :checked="!form.use_per_day_times"
                                        @change="form.use_per_day_times = false; handleTimeModeChange()"
                                        class="mr-2"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Same time for all days</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input
                                        type="radio"
                                        name="use_per_day_times"
                                        value="1"
                                        :checked="form.use_per_day_times"
                                        @change="form.use_per_day_times = true; handleTimeModeChange()"
                                        class="mr-2"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Different time per day</span>
                                </label>
                            </div>
                        </div>

                        <div x-show="!form.use_per_day_times" class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Start time</label>
                            <input
                                type="time"
                                name="start_time"
                                x-model="form.start_time"
                                @input="updateStudentTimeCalculation()"
                                class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                :required="!form.use_per_day_times"
                            />
                        </div>

                        <div x-show="!form.use_per_day_times" class="flex flex-col">
                            <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">End time</label>
                            <input
                                type="time"
                                name="end_time"
                                x-model="form.end_time"
                                @input="updateStudentTimeCalculation()"
                                class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                :required="!form.use_per_day_times"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <label class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Repeated days</label>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                            @foreach (['sunday','monday','tuesday','wednesday','thursday','friday','saturday'] as $day)
                                <label class="cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="days_of_week[]"
                                        value="{{ $day }}"
                                        x-model="form.days_of_week"
                                        @change="handleDaysChange()"
                                        class="peer hidden"
                                    />
                                    <span class="flex items-center justify-center rounded-xl border border-gray-300 px-3 py-2 text-sm font-semibold uppercase tracking-wide text-gray-600 transition peer-checked:border-indigo-500 peer-checked:bg-indigo-600 peer-checked:text-white dark:border-gray-600 dark:text-gray-300 dark:peer-checked:bg-indigo-500">
                                        {{ Str::substr($day, 0, 3) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div x-show="form.use_per_day_times" class="flex flex-col space-y-4">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Time for each day</label>
                        <div class="grid gap-4 md:grid-cols-2">
                            <template x-for="day in ['sunday','monday','tuesday','wednesday','thursday','friday','saturday']" :key="day">
                                <div x-show="form.days_of_week.includes(day)" class="flex flex-col space-y-2 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300" x-text="day.charAt(0).toUpperCase() + day.slice(1)"></label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="flex flex-col">
                                            <label class="mb-1 text-xs text-gray-600 dark:text-gray-400">Start</label>
                                            <input
                                                type="time"
                                                :name="form.use_per_day_times ? 'day_times[' + day + '][start_time]' : ''"
                                                x-model="form.day_times[day].start_time"
                                                @input="updatePerDayStudentTime(day)"
                                                :required="form.use_per_day_times && form.days_of_week.includes(day)"
                                                :disabled="!form.use_per_day_times"
                                                class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                            />
                                        </div>
                                        <div class="flex flex-col">
                                            <label class="mb-1 text-xs text-gray-600 dark:text-gray-400">End</label>
                                            <input
                                                type="time"
                                                :name="form.use_per_day_times ? 'day_times[' + day + '][end_time]' : ''"
                                                x-model="form.day_times[day].end_time"
                                                @input="updatePerDayStudentTime(day)"
                                                :required="form.use_per_day_times && form.days_of_week.includes(day)"
                                                :disabled="!form.use_per_day_times"
                                                class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
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
                            <span x-text="isEdit ? 'Save changes' : 'Create timetable'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('timetablePage', ({ timetables, storeUrl }) => ({
                modalOpen: false,
                isEdit: false,
                formAction: storeUrl,
                form: {
                    id: null,
                    student_id: '',
                    teacher_id: '',
                    course_name: '',
                    timezone: '',
                    teacher_timezone: '',
                    start_date: '',
                    end_date: '',
                    start_time: '',
                    end_time: '',
                    use_per_day_times: false,
                    day_times: {
                        sunday: { start_time: '', end_time: '' },
                        monday: { start_time: '', end_time: '' },
                        tuesday: { start_time: '', end_time: '' },
                        wednesday: { start_time: '', end_time: '' },
                        thursday: { start_time: '', end_time: '' },
                        friday: { start_time: '', end_time: '' },
                        saturday: { start_time: '', end_time: '' },
                    },
                    time_difference_hours: null,
                    use_manual_time_diff: false,
                    student_time_from: '',
                    student_time_to: '',
                    student_time_from_display: '',
                    student_time_to_display: '',
                    days_of_week: [],
                },
                timetables,

                openCreate() {
                    this.isEdit = false;
                    this.formAction = storeUrl;
                    this.form = {
                        id: null,
                        student_id: '',
                        teacher_id: '',
                        course_name: '',
                        timezone: '',
                        teacher_timezone: '',
                        start_date: '',
                        end_date: '',
                        start_time: '',
                        end_time: '',
                        use_per_day_times: false,
                        day_times: {
                            sunday: { start_time: '', end_time: '' },
                            monday: { start_time: '', end_time: '' },
                            tuesday: { start_time: '', end_time: '' },
                            wednesday: { start_time: '', end_time: '' },
                            thursday: { start_time: '', end_time: '' },
                            friday: { start_time: '', end_time: '' },
                            saturday: { start_time: '', end_time: '' },
                        },
                        time_difference_hours: null,
                        use_manual_time_diff: false,
                        student_time_from: '',
                        student_time_to: '',
                        student_time_from_display: '',
                        student_time_to_display: '',
                        days_of_week: [],
                    };
                    this.modalOpen = true;
                },

                openEdit(id) {
                    const record = this.timetables.find(item => item.id === id);
                    if (!record) {
                        return;
                    }

                    this.isEdit = true;
                    this.formAction = record.update_url;
                    
                    // Initialize day_times object
                    const dayTimes = {};
                    const allDays = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
                    allDays.forEach(day => {
                        let startTime = record.day_times && record.day_times[day] ? record.day_times[day].start_time : '';
                        let endTime = record.day_times && record.day_times[day] ? record.day_times[day].end_time : '';
                        // Convert HH:mm:ss to HH:mm format if needed
                        if (startTime && startTime.length > 5) {
                            startTime = startTime.substring(0, 5);
                        }
                        if (endTime && endTime.length > 5) {
                            endTime = endTime.substring(0, 5);
                        }
                        dayTimes[day] = {
                            start_time: startTime,
                            end_time: endTime,
                        };
                    });

                    // If using per-day times, extract start_time and end_time from first day for fallback
                    let defaultStartTime = record.start_time || '';
                    let defaultEndTime = record.end_time || '';
                    
                    // Convert HH:mm:ss to HH:mm format if needed
                    if (defaultStartTime && defaultStartTime.length > 5) {
                        defaultStartTime = defaultStartTime.substring(0, 5);
                    }
                    if (defaultEndTime && defaultEndTime.length > 5) {
                        defaultEndTime = defaultEndTime.substring(0, 5);
                    }
                    
                    if (record.use_per_day_times && record.day_times) {
                        const firstDay = record.days_of_week && record.days_of_week.length > 0 ? record.days_of_week[0] : null;
                        if (firstDay && record.day_times[firstDay]) {
                            let dayStart = record.day_times[firstDay].start_time || defaultStartTime;
                            let dayEnd = record.day_times[firstDay].end_time || defaultEndTime;
                            // Convert HH:mm:ss to HH:mm format if needed
                            if (dayStart && dayStart.length > 5) {
                                dayStart = dayStart.substring(0, 5);
                            }
                            if (dayEnd && dayEnd.length > 5) {
                                dayEnd = dayEnd.substring(0, 5);
                            }
                            defaultStartTime = dayStart;
                            defaultEndTime = dayEnd;
                        }
                    }

                    this.form = {
                        id: record.id,
                        student_id: String(record.student_id),
                        teacher_id: String(record.teacher_id ?? ''),
                        course_name: record.course_name,
                        timezone: record.timezone || '',
                        teacher_timezone: record.teacher_timezone || '',
                        start_date: record.start_date,
                        end_date: record.end_date,
                        start_time: defaultStartTime,
                        end_time: defaultEndTime,
                        use_per_day_times: record.use_per_day_times || false,
                        day_times: dayTimes,
                        time_difference_hours: record.time_difference_hours || null,
                        use_manual_time_diff: record.use_manual_time_diff || false,
                        student_time_from: record.student_time_from || '',
                        student_time_to: record.student_time_to || '',
                        student_time_from_display: record.student_time_from ? this.formatTime(record.student_time_from) : '',
                        student_time_to_display: record.student_time_to ? this.formatTime(record.student_time_to) : '',
                        days_of_week: Array.isArray(record.days_of_week) ? [...record.days_of_week] : [],
                    };
                    
                    // Initialize day_times for selected days if using per-day times
                    if (this.form.use_per_day_times) {
                        this.initializeDayTimes();
                    }
                    
                    this.modalOpen = true;
                },

                updateStudentTimeCalculation() {
                    if (!this.form.start_time || !this.form.end_time) {
                        this.form.student_time_from_display = '';
                        this.form.student_time_to_display = '';
                        this.form.use_manual_time_diff = false;
                        return;
                    }

                    // Check if timezone is empty (null, undefined, or empty string) and time_difference_hours is set
                    const hasNoTimezone = !this.form.timezone || this.form.timezone === '';
                    const hasManualDiff = this.form.time_difference_hours !== null && this.form.time_difference_hours !== '';
                    
                    if (hasNoTimezone && hasManualDiff) {
                        // Manual time difference
                        this.form.use_manual_time_diff = true;
                        const start = this.addHours(this.form.start_time, this.form.time_difference_hours);
                        const end = this.addHours(this.form.end_time, this.form.time_difference_hours);
                        this.form.student_time_from = start;
                        this.form.student_time_to = end;
                        this.form.student_time_from_display = this.formatTime(start);
                        this.form.student_time_to_display = this.formatTime(end);
                    } else if (this.form.timezone && this.form.teacher_timezone) {
                        // Calculate based on timezone difference (simplified - would need server-side for accurate DST)
                        this.form.use_manual_time_diff = false;
                        // For now, show placeholder - actual calculation should be done server-side
                        this.form.student_time_from_display = 'Calculating...';
                        this.form.student_time_to_display = 'Calculating...';
                    } else {
                        this.form.use_manual_time_diff = false;
                        this.form.student_time_from_display = '';
                        this.form.student_time_to_display = '';
                    }
                },

                addHours(timeStr, hours) {
                    const [h, m] = timeStr.split(':').map(Number);
                    const totalMinutes = h * 60 + m + (hours * 60);
                    const newHours = Math.floor(totalMinutes / 60) % 24;
                    const newMinutes = totalMinutes % 60;
                    return `${String(newHours).padStart(2, '0')}:${String(newMinutes).padStart(2, '0')}:00`;
                },

                formatTime(timeStr) {
                    if (!timeStr) return '';
                    const [h, m] = timeStr.split(':').map(Number);
                    const period = h >= 12 ? 'PM' : 'AM';
                    const hour12 = h % 12 || 12;
                    return `${hour12}:${String(m).padStart(2, '0')} ${period}`;
                },

                handleTimeModeChange() {
                    // Ensure boolean value is set correctly
                    this.form.use_per_day_times = this.form.use_per_day_times === true || this.form.use_per_day_times === 'true' || this.form.use_per_day_times === 1;
                    
                    if (this.form.use_per_day_times) {
                        // Switching to per-day mode - initialize day_times for selected days
                        this.initializeDayTimes();
                    } else {
                        // Switching to single time mode - populate start_time and end_time from first day card
                        // The cards appear in week order, so find the first day in week order that is selected
                        const weekDays = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
                        let foundFirstDay = false;
                        
                        // Find the first day in week order that is selected and has times
                        for (const day of weekDays) {
                            if (this.form.days_of_week.includes(day) && 
                                this.form.day_times[day] && 
                                this.form.day_times[day].start_time && 
                                this.form.day_times[day].end_time) {
                                // Convert HH:mm:ss to HH:mm format if needed
                                let startTime = this.form.day_times[day].start_time;
                                let endTime = this.form.day_times[day].end_time;
                                
                                if (startTime && startTime.length > 5) {
                                    startTime = startTime.substring(0, 5);
                                }
                                if (endTime && endTime.length > 5) {
                                    endTime = endTime.substring(0, 5);
                                }
                                
                                this.form.start_time = startTime;
                                this.form.end_time = endTime;
                                foundFirstDay = true;
                                break; // Use the first day found (first card in UI)
                            }
                        }
                        
                        // Clear day_times values but keep structure to prevent errors
                        const allDays = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
                        allDays.forEach(day => {
                            if (this.form.day_times[day]) {
                                this.form.day_times[day].start_time = '';
                                this.form.day_times[day].end_time = '';
                            } else {
                                this.form.day_times[day] = { start_time: '', end_time: '' };
                            }
                        });
                    }
                },

                handleDaysChange() {
                    if (this.form.use_per_day_times) {
                        // Initialize times for newly selected days
                        this.initializeDayTimes();
                    }
                },

                initializeEmptyDayTimes() {
                    const allDays = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
                    const dayTimes = {};
                    allDays.forEach(day => {
                        dayTimes[day] = {
                            start_time: '',
                            end_time: '',
                        };
                    });
                    return dayTimes;
                },

                initializeDayTimes() {
                    const allDays = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
                    // Ensure all days have an entry
                    allDays.forEach(day => {
                        if (!this.form.day_times[day]) {
                            this.form.day_times[day] = {
                                start_time: '',
                                end_time: '',
                            };
                        }
                    });
                    // Initialize times for selected days
                    this.form.days_of_week.forEach(day => {
                        if (!this.form.day_times[day].start_time) {
                            this.form.day_times[day].start_time = this.form.start_time || '';
                        }
                        if (!this.form.day_times[day].end_time) {
                            this.form.day_times[day].end_time = this.form.end_time || '';
                        }
                    });
                },

                updatePerDayStudentTime(day) {
                    // This could be enhanced to calculate student times per day if needed
                    // For now, just trigger the general calculation
                    if (this.form.day_times[day] && this.form.day_times[day].start_time && this.form.day_times[day].end_time) {
                        // Per-day student time calculation could go here
                    }
                },

                closeModal() {
                    this.modalOpen = false;
                },
            }));
        });
    </script>
</x-app-layout>

