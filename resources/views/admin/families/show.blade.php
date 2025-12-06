<x-app-layout pageTitle="Family Details">
    <div class="space-y-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $family->name }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    WhatsApp: {{ $family->whatsapp_number ?? 'Not provided' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.families.edit', $family) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Edit Family
                </a>
                <a href="{{ route('admin.families.index') }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Back to Families
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex flex-col gap-3">
                    <form method="GET" class="flex items-center gap-3">
                        <div>
                            <label for="month" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Month</label>
                            <input type="month" id="month" name="month" value="{{ $month }}"
                                   class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                        </div>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                            Apply
                        </button>
                    </form>
                    @if ($availableMonths->isNotEmpty())
                        <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-semibold uppercase tracking-wide">Available:</span>
                            @foreach ($availableMonths as $available)
                                <a href="{{ route('admin.families.show', ['family' => $family->id, 'month' => $available]) }}"
                                   class="px-2 py-1 rounded-full border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $available === $month ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $available)->isoFormat('MMM YYYY') }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    @if ($whatsappLink)
                        <a href="{{ $whatsappLink }}" target="_blank"
                           class="inline-flex items-center px-4 py-2 border border-green-500 text-green-600 dark:text-green-400 rounded-lg text-sm font-semibold hover:bg-green-50 dark:hover:bg-green-900/10 transition-colors">
                            Send WhatsApp Summary
                        </a>
                    @endif
                    <a href="{{ route('admin.families.report', ['family' => $family->id, 'month' => $month]) }}"
                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:from-purple-700 hover:to-indigo-700 transition-colors">
                        Download PDF
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($summary['currencyTotals'] as $currency => $amount)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/40">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total ({{ $currency }})</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($amount, 2) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Across all students</p>
                    </div>
                @endforeach
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">WhatsApp Preview</label>
                <textarea rows="3" readonly class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">{{ $summary['message'] }}</textarea>
            </div>
        </div>

        <!-- Family Members Classes Section -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Family Members Classes</h3>
            </div>

            @php
                $currentYear = now()->year;
            @endphp

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <form method="GET" class="grid gap-4 md:grid-cols-4">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <div class="flex flex-col">
                        <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Month</label>
                        <select
                            name="class_month"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                        >
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected($classFilters['month'] == $m)>
                                    {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Year</label>
                        <select
                            name="class_year"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                        >
                            @for ($y = $currentYear - 2; $y <= $currentYear + 2; $y++)
                                <option value="{{ $y }}" @selected($classFilters['year'] == $y)>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Student</label>
                        <select
                            name="class_student_id"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                        >
                            <option value="">All students</option>
                            @foreach ($familyStudents as $student)
                                <option value="{{ $student->id }}" @selected($classFilters['student_id'] == $student->id)>
                                    {{ $student->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col">
                        <label class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Teacher</label>
                        <select
                            name="class_teacher_id"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
                        >
                            <option value="">All teachers</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected($classFilters['teacher_id'] == $teacher->id)>
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
                            href="{{ route('admin.families.show', ['family' => $family->id, 'month' => $month]) }}"
                            class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Reset Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Classes Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Teacher</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Course</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($events as $event)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $event['start_at']->format('M d, Y') }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $event['start_at']->format('l') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $event['time'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $event['student'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $event['student_timezone'] }} • {{ $event['student_time'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $event['teacher'] ?? '—' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $event['teacher_timezone'] }} • {{ $event['teacher_time'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ $event['course_name'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($event['status'] === 'scheduled') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300
                                            @elseif($event['status'] === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300
                                            @elseif($event['status'] === 'rescheduled') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @endif">
                                            {{ ucfirst($event['status']) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No classes scheduled</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No classes found for the selected filters.</p>
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
        </div>

        @if ($summary['students']->isEmpty())
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-12 text-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No students assigned</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Assign students to this family to view billing summaries.
                </p>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($summary['students'] as $studentSummary)
                    @php
                        $student = $studentSummary['student'];
                        $automatic = $studentSummary['automatic_billing'];
                        $manual = $studentSummary['manual_billing'];
                    @endphp
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $student->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Teacher: {{ $student->teacher?->user?->name ?? 'Unassigned' }}
                                </p>
                            </div>
                            <div class="flex gap-3">
                                <div class="text-sm">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Total</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $studentSummary['currency'] }} {{ number_format($studentSummary['total'], 2) }}
                                    </p>
                                </div>
                                @if ($automatic)
                                    <div class="text-sm">
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Automatic</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ number_format($studentSummary['lesson_total'], 2) }}
                                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium {{ $automatic->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' }}">
                                                {{ ucfirst($automatic->status) }}
                                            </span>
                                        </p>
                                    </div>
                                @endif
                                @if ($manual)
                                    <div class="text-sm">
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Manual</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ number_format($studentSummary['manual_total'], 2) }}
                                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium {{ $manual->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' }}">
                                                {{ ucfirst($manual->status) }}
                                            </span>
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="px-6 py-5 space-y-5">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">Lesson Breakdown</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                                            <tr>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wide">Date</th>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wide">Lesson</th>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wide">Teacher</th>
                                                <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wide">Duration</th>
                                                <th class="px-4 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wide">Rate</th>
                                                <th class="px-4 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wide">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @forelse ($studentSummary['lesson_rows'] as $row)
                                                <tr>
                                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $row['date'] }}</td>
                                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $row['title'] }}</td>
                                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $row['teacher'] ?? '—' }}</td>
                                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $row['duration_label'] }}</td>
                                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-200">
                                                        {{ $studentSummary['currency'] }} {{ number_format($row['hourly_rate'], 2) }}
                                                    </td>
                                                    <td class="px-4 py-2 text-right text-gray-900 dark:text-white font-semibold">
                                                        {{ $studentSummary['currency'] }} {{ number_format($row['amount'], 2) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                                        No lessons logged for this month.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @if ($studentSummary['manual_entries']->isNotEmpty())
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">Manual Adjustments</h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                            <thead class="bg-gray-50 dark:bg-gray-900/40">
                                                <tr>
                                                    <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wide">Description</th>
                                                    <th class="px-4 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase text-xs tracking-wide">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach ($studentSummary['manual_entries'] as $entry)
                                                    <tr>
                                                        <td class="px-4 py-2 text-gray-700 dark:text-gray-200">{{ $entry['description'] ?? 'Manual adjustment' }}</td>
                                                        <td class="px-4 py-2 text-right text-gray-900 dark:text-white font-semibold">
                                                            {{ $studentSummary['currency'] }} {{ number_format($entry['amount'], 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                <tr>
                                                    <td class="px-4 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Manual Total</td>
                                                    <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-white">
                                                        {{ $studentSummary['currency'] }} {{ number_format($studentSummary['manual_total'], 2) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>


