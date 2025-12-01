<x-app-layout pageTitle="Edit Lesson">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Lesson</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update lesson details</p>
            </div>

            <form action="{{ route('lessons.update', $lesson) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Student & Teacher Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Student <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="student_id" 
                            id="student_id"
                            required
                            class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        >
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" {{ old('student_id', $lesson->student_id) == $student->id ? 'selected' : '' }}>
                                    {{ $student->name }} (Remaining: {{ $student->remaining_hours }}h)
                                </option>
                            @endforeach
                        </select>
                        @error('student_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Teacher <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="teacher_id" 
                            id="teacher_id"
                            required
                            class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        >
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('teacher_id', $lesson->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('teacher_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Lesson Details -->
                <div class="space-y-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Lesson Details</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="hours" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Hours <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="hours" 
                                id="hours"
                                min="0" 
                                max="8" 
                                value="{{ old('hours', intdiv($lesson->duration_minutes, 60)) }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            >
                            @error('hours')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="minutes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Minutes <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="minutes" 
                                id="minutes"
                                min="0" 
                                max="59" 
                                value="{{ old('minutes', $lesson->duration_minutes % 60) }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            >
                            @error('minutes')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="date" 
                                name="date" 
                                id="date"
                                value="{{ old('date', $lesson->date) }}" 
                                required
                                max="{{ now()->toDateString() }}"
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            >
                            @error('date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="status" 
                            id="status"
                            required
                            class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        >
                            @foreach(['attended' => 'Attended', 'absent_student' => 'Absent Student', 'absent_teacher' => 'Absent Teacher', 'cancelled_student' => 'Cancelled Student', 'cancelled_teacher' => 'Cancelled Teacher', 'trial' => 'Trial'] as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $lesson->status ?? 'attended') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('lessons.index') }}" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-medium rounded-lg shadow-sm hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                        Update Lesson
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>

