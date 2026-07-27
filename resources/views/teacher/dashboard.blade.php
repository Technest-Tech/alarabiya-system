<x-app-layout pageTitle="Teacher Dashboard">
    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Total Hours This Month -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Hours This Month</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format(\App\Models\Lesson::where('teacher_id', Auth::user()->teacher->id)->whereYear('date', now()->year)->whereMonth('date', now()->month)->sum('duration_minutes') / 60, 1) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ \App\Models\Lesson::where('teacher_id', Auth::user()->teacher->id)->whereYear('date', now()->year)->whereMonth('date', now()->month)->count() }} lessons
                        </p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/20">
                        <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Students -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">My Students</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ Auth::user()->teacher->students()->count() }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Assigned students</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/20">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Upcoming Lessons -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Upcoming</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ \App\Models\Lesson::where('teacher_id', Auth::user()->teacher->id)->where('date', '>=', today())->count() }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Scheduled lessons</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/20">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rewards & Deductions -->
        @if($recentAdjustments->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rewards &amp; Deductions</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Adjustments applied to your salary, with reasons.</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Net this month ({{ $currentMonthLabel }})</p>
                        <p class="text-xl font-bold {{ $currentNet >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $currentNet >= 0 ? '+' : '−' }}{{ number_format(abs($currentNet), 2) }} {{ $teacherCurrency }}
                        </p>
                        <p class="text-xs text-gray-400">
                            <span class="text-green-600 dark:text-green-400">+{{ number_format($currentRewards, 2) }}</span>
                            &nbsp;·&nbsp;
                            <span class="text-red-600 dark:text-red-400">−{{ number_format($currentDeductions, 2) }}</span>
                        </p>
                    </div>
                </div>

                @if($currentAdjustments->isNotEmpty())
                    <div class="space-y-2 mb-4">
                        @foreach($currentAdjustments as $adj)
                            <div class="flex items-start gap-3 p-3 rounded-lg border {{ $adj->isReward() ? 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/10' : 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/10' }}">
                                <span class="mt-0.5 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $adj->isReward() ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                    {{ $adj->isReward() ? 'Reward +' : 'Deduction −' }}{{ number_format($adj->amount, 2) }} {{ $teacherCurrency }}
                                </span>
                                <p class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $adj->reason }}</p>
                                <span class="text-xs text-gray-400 whitespace-nowrap">{{ $adj->created_at?->format('M d') }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">No rewards or deductions this month.</p>
                @endif

                @php $earlier = $recentAdjustments->filter(fn($a) => $a->month->format('Y-m') !== now()->format('Y-m')); @endphp
                @if($earlier->isNotEmpty())
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Earlier</p>
                        <div class="space-y-1">
                            @foreach($earlier as $adj)
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="font-semibold {{ $adj->isReward() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} whitespace-nowrap">
                                        {{ $adj->isReward() ? '+' : '−' }}{{ number_format($adj->amount, 2) }} {{ $teacherCurrency }}
                                    </span>
                                    <span class="flex-1 text-gray-600 dark:text-gray-400 truncate">{{ $adj->reason }}</span>
                                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ $adj->month->isoFormat('MMM YYYY') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- View Classes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">My Classes</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">View your classes with their status and your statistics.</p>
            <a href="{{ route('teacher.lessons.index') }}" class="inline-flex items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                View My Classes
            </a>
        </div>

        <!-- My Students -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My Students</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->teacher->students()->count() }} total</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach(Auth::user()->teacher->students()->take(6)->get() as $student)
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $student->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $student->remaining_hours }}h remaining</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $student->package_hours_total > 0 ? min(100, ($student->taken_hours / $student->package_hours_total) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if(Auth::user()->teacher->students()->count() === 0)
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No students assigned yet</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
