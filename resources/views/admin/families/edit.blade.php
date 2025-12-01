<x-app-layout pageTitle="Edit Family">
    <div class="max-w-4xl mx-auto space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Family</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Update family details or reassign students.
                </p>
            </div>
            <a href="{{ route('admin.families.show', $family) }}"
               class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                Back to Family
            </a>
        </div>

        <form method="POST" action="{{ route('admin.families.update', $family) }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Family Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $family->name) }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                @error('name')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">WhatsApp Number</label>
                <input type="text" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $family->whatsapp_number) }}"
                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                @error('whatsapp_number')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="student_ids" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assign Students</label>
                <select id="student_ids" name="student_ids[]" multiple size="8"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    @php
                        $assigned = old('student_ids', $family->students->pluck('id')->toArray());
                    @endphp
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected(in_array($student->id, $assigned))>
                            {{ $student->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hold Cmd/Ctrl to select multiple students.</p>
                @error('student_ids')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.families.show', $family) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    Update Family
                </button>
            </div>
        </form>
    </div>
</x-app-layout>


