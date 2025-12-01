<div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
    <div class="xl:col-span-2 space-y-8">
        @include('admin.billings.partials.table', [
            'billingsByStatus' => $billingsByStatus,
            'statusLabels' => $statusLabels,
            'type' => 'manual',
        ])
    </div>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create Manual Billing</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Add one-off charges or adjustments for a student with custom amounts.
        </p>

        <form method="POST" action="{{ route('admin.billings.manual.store') }}" class="mt-6 space-y-5">
            @csrf
            <div class="space-y-1">
                <label for="student_id" class="text-sm font-medium text-gray-700 dark:text-gray-300">Student</label>
                <select id="student_id" name="student_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label for="month" class="text-sm font-medium text-gray-700 dark:text-gray-300">Billing Month</label>
                    <input type="month" id="month" name="month" value="{{ old('month', now()->format('Y-m')) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                    @error('month')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label for="currency" class="text-sm font-medium text-gray-700 dark:text-gray-300">Currency</label>
                    <input type="text" id="currency" name="currency" value="{{ old('currency', strtoupper(config('app.currency', 'USD'))) }}" maxlength="3" required class="w-full uppercase rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                    @error('currency')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-1">
                <label for="total_amount" class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Amount</label>
                <input type="number" id="total_amount" name="total_amount" value="{{ old('total_amount') }}" step="0.01" min="0" required class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm">
                @error('total_amount')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="description" class="text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm" placeholder="Add optional notes or invoice details...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="mark_as_paid" value="1" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800" @checked(old('mark_as_paid'))>
                    Mark as paid immediately
                </label>

                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    Save Manual Billing
                </button>
            </div>
        </form>
    </div>
</div>


