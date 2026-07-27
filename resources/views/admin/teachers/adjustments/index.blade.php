<x-app-layout pageTitle="Teacher Rewards & Deductions">
    <div class="space-y-8"
         x-data="{
            type: '{{ old('type', 'reward') }}',
            teacher: '{{ old('teacher_id', '') }}',
            currencies: {{ Illuminate\Support\Js::from($teachers->mapWithKeys(fn($t) => [$t->id => ($t->currency ?? 'EGP')])) }},
            get currency() { return this.currencies[this.teacher] || 'EGP'; }
         }">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Teacher Rewards &amp; Deductions</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Add a reward (bonus) or a deduction (penalty) to a teacher's monthly salary — each with an exact reason.
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <form method="GET" class="flex items-center gap-2">
                    <input type="month" name="month" value="{{ $month }}"
                           class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                        Apply
                    </button>
                </form>
                <a href="{{ route('admin.teacher-salaries.index', ['month' => $month]) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    View Salaries
                </a>
            </div>
        </div>

        {{-- Flash + validation messages --}}
        @if(session('status'))
            <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-800 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-800 dark:text-red-300">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($availableMonths->isNotEmpty())
            <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="font-semibold uppercase tracking-wide">Available:</span>
                @foreach ($availableMonths as $available)
                    <a href="{{ route('admin.teacher-adjustments.index', ['month' => $available]) }}"
                       class="px-2 py-1 rounded-full border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $available === $month ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                        {{ \Carbon\Carbon::createFromFormat('Y-m', $available)->isoFormat('MMM YYYY') }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Add form --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add for {{ $monthLabel }}</h3>
            <form method="POST" action="{{ route('admin.teacher-adjustments.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teacher <span class="text-red-500">*</span></label>
                        <select name="teacher_id" x-model="teacher" required
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="">Select teacher…</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->user?->name ?? 'Unassigned' }} ({{ $t->currency ?? 'EGP' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type <span class="text-red-500">*</span></label>
                        <div class="flex rounded-lg overflow-hidden border border-gray-300 dark:border-gray-700">
                            <button type="button" @click="type = 'reward'"
                                    :class="type === 'reward' ? 'bg-green-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300'"
                                    class="flex-1 px-3 py-2 text-sm font-semibold transition-colors">Reward</button>
                            <button type="button" @click="type = 'deduction'"
                                    :class="type === 'deduction' ? 'bg-red-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300'"
                                    class="flex-1 px-3 py-2 text-sm font-semibold transition-colors border-l border-gray-300 dark:border-gray-700">Deduction</button>
                        </div>
                        <input type="hidden" name="type" :value="type">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="amount" step="0.01" min="0.01" required
                                   value="{{ old('amount') }}"
                                   class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm pr-14">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-semibold text-gray-500 dark:text-gray-400" x-text="currency"></span>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">In the teacher's currency.</p>
                    </div>

                    <div class="flex items-end">
                        <button type="submit"
                                :class="type === 'deduction' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
                                class="w-full inline-flex items-center justify-center px-4 py-2 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <span x-text="type === 'deduction' ? 'Add Deduction' : 'Add Reward'"></span>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="2" required maxlength="1000"
                              placeholder="e.g. Late to 3 classes this week / Excellent student feedback"
                              class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">{{ old('reason') }}</textarea>
                </div>
            </form>
        </div>

        {{-- Totals --}}
        @if(!empty($totalsByCurrency))
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($totalsByCurrency as $currency => $totals)
                    @php $net = $totals['reward'] - $totals['deduction']; @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-5 bg-white dark:bg-gray-800">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $currency }} — {{ $monthLabel }}</p>
                        <div class="mt-2 flex items-baseline gap-4 text-sm">
                            <span class="text-green-600 dark:text-green-400 font-semibold">+{{ number_format($totals['reward'], 2) }} rewards</span>
                            <span class="text-red-600 dark:text-red-400 font-semibold">−{{ number_format($totals['deduction'], 2) }} deductions</span>
                        </div>
                        <p class="mt-1 text-lg font-bold {{ $net >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-600 dark:text-red-400' }}">
                            Net: {{ $net >= 0 ? '+' : '−' }}{{ number_format(abs($net), 2) }} {{ $currency }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- List --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Teacher</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Added by</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($adjustments as $adjustment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $adjustment->teacher->user?->name ?? 'Unassigned' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $adjustment->teacher->currency ?? 'EGP' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($adjustment->isReward())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">Reward</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">Deduction</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold {{ $adjustment->isReward() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $adjustment->isReward() ? '+' : '−' }}{{ number_format($adjustment->amount, 2) }} {{ $adjustment->teacher->currency ?? 'EGP' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 max-w-md">{{ $adjustment->reason }}</td>
                                <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $adjustment->creator?->name ?? '—' }}<br>
                                    {{ $adjustment->created_at?->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('admin.teacher-adjustments.destroy', $adjustment) }}" method="POST"
                                          onsubmit="return confirm('Remove this {{ $adjustment->type }}? The teacher\'s salary total will recalculate.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 transition-colors">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No rewards or deductions recorded for {{ $monthLabel }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
