<div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
    <div class="xl:col-span-2 space-y-8">
        @include('admin.billings.partials.table', [
            'billingsByStatus' => $billingsByStatus,
            'statusLabels' => $statusLabels,
            'type' => 'manual',
        ])
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm p-8 hover:shadow-lg transition-all duration-300">
        <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-inner">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Manual Billing</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add custom charges or adjustments</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.billings.manual.store') }}" class="space-y-6">
            @csrf
            <div class="space-y-2">
                <label for="student_id" class="text-sm font-bold text-gray-700 dark:text-gray-300">Student</label>
                <select id="student_id" name="student_id" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-shadow shadow-sm">
                    <option value="">Select a student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                            {{ $student->name }}
                        </option>
                    @endforeach
                </select>
                @error('student_id')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="space-y-2">
                    <label for="month" class="text-sm font-bold text-gray-700 dark:text-gray-300">Billing Month</label>
                    <input type="month" id="month" name="month" value="{{ old('month', now()->format('Y-m')) }}" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-shadow shadow-sm">
                    @error('month')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="currency" class="text-sm font-bold text-gray-700 dark:text-gray-300">Currency</label>
                    <input type="text" id="currency" name="currency" value="{{ old('currency', strtoupper(config('app.currency', 'USD'))) }}" maxlength="3" required class="w-full uppercase rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-shadow shadow-sm">
                    @error('currency')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-2">
                <label for="total_amount" class="text-sm font-bold text-gray-700 dark:text-gray-300">Total Amount</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" id="total_amount" name="total_amount" value="{{ old('total_amount') }}" step="0.01" min="0" required class="w-full pl-7 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-shadow shadow-sm">
                </div>
                @error('total_amount')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="description" class="text-sm font-bold text-gray-700 dark:text-gray-300">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-shadow shadow-sm resize-none" placeholder="Add optional notes or invoice details...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <label class="inline-flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300 cursor-pointer group">
                    <input type="checkbox" name="mark_as_paid" value="1" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 transition-all cursor-pointer" @checked(old('mark_as_paid'))>
                    <span class="font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Mark as paid immediately</span>
                </label>

                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-indigo-700 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-indigo-500/30 transition-all transform hover:-translate-y-0.5">
                    Save Billing
                </button>
            </div>
        </form>
    </div>
</div>


