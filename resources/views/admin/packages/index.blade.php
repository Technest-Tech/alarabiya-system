<x-app-layout pageTitle="Packages Overview">
    <div class="space-y-6">
        <!-- Header with Filters -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Student Packages</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">View and manage all student packages</p>
            </div>
            <form class="mt-4 sm:mt-0 flex items-center space-x-3">
                <select name="month" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}" @selected($m==$month)>{{ DateTime::createFromFormat('!m', $m)->format('F') }}</option>
                    @endfor
                </select>
                <select name="year" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @for($y=now()->year-2;$y<=now()->year+1;$y++)
                        <option value="{{ $y }}" @selected($y==$year)>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>

        <!-- Packages Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($students as $student)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold text-lg">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $student->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ optional($student->teacher->user)->name ?? 'Unassigned' }}
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $student->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ ucfirst($student->status) }}
                        </span>
                    </div>

                    @php
                        $currentPackage = $student->currentPackage;
                        $packageHours = $currentPackage ? $currentPackage->package_hours : $student->package_hours_total;
                        $takenHours = $currentPackage ? $currentPackage->hours_used_decimal : 0;
                        $remainingHours = $currentPackage ? $currentPackage->remaining_hours : max(0, $student->package_hours_total - $student->taken_hours);
                    @endphp
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Package</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $packageHours }} hrs</p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Taken</p>
                            <p class="mt-1 text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($takenHours, 1) }} hrs</p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Remaining</p>
                            <p class="mt-1 text-xl font-bold {{ $remainingHours > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ number_format($remainingHours, 1) }} hrs
                            </p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Cost</p>
                            <p class="mt-1 text-xl font-bold text-yellow-600 dark:text-yellow-400">${{ number_format($packageHours * $student->hourly_rate, 2) }}</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>Progress</span>
                            <span>{{ $packageHours > 0 ? round(($takenHours / $packageHours) * 100) : 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-2 rounded-full transition-all duration-300" style="width: {{ $packageHours > 0 ? min(100, ($takenHours / $packageHours) * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    <!-- Completed Packages Info -->
                    @php
                        $completedCount = $student->packages()->whereIn('status', ['completed', 'paid'])->count();
                    @endphp
                    @if($completedCount > 0)
                        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Completed Packages</p>
                                    <p class="mt-1 text-sm font-semibold text-blue-900 dark:text-blue-100">{{ $completedCount }} package{{ $completedCount > 1 ? 's' : '' }}</p>
                                </div>
                                <a href="{{ route('admin.packages.completed', $student) }}" 
                                   class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors whitespace-nowrap">
                                    View Details
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('admin.packages.report', ['student' => $student->id, 'month' => $month, 'year' => $year]) }}"
                           class="px-3 py-1.5 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg shadow-sm hover:from-purple-700 hover:to-indigo-700 transition-colors whitespace-nowrap">
                           Report
                        </a>
                        <a href="{{ route('students.edit', $student) }}" class="px-3 py-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors whitespace-nowrap">
                            Edit
                        </a>
                        <form action="{{ route('admin.students.toggle', $student) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-3 py-1.5 text-sm font-medium rounded-lg {{ $student->status === 'active' ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20' : 'text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20' }} transition-colors whitespace-nowrap">
                                {{ $student->status === 'active' ? 'Disable' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        @if($students->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No students found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new student.</p>
            </div>
        @endif
    </div>
</x-app-layout>
