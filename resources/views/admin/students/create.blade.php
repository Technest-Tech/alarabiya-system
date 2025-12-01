<x-app-layout pageTitle="Add New Student">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Student Information</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Fill in the details to create a new student account</p>
            </div>

            <form action="{{ route('students.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Personal Information Section -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Personal Information</h3>
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name"
                            value="{{ old('name') }}" 
                            required
                            class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            placeholder="Enter student full name"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-country-selector 
                            name="country_code" 
                            :value="old('country_code', 'AE')" 
                            phoneInputId="whatsapp_number" 
                        />
                        @error('country_code')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div>
                            <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                WhatsApp Number <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="whatsapp_number" 
                                id="whatsapp_number"
                                value="{{ old('whatsapp_number') }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                                placeholder="+9715XXXXXXXX"
                            >
                            @error('whatsapp_number')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Package & Payment Section -->
                <div class="space-y-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Package & Payment</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="package_hours_total" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Package Hours <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="package_hours_total" 
                                id="package_hours_total"
                                min="1" 
                                value="{{ old('package_hours_total', 20) }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            >
                            @error('package_hours_total')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Payment Method <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="payment_method" 
                                id="payment_method"
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            >
                                @foreach(['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'credit_card' => 'Credit Card', 'paypal' => 'PayPal', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('payment_method') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        @if(!Auth::user()->isSupport())
                        <div>
                            <label for="hourly_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Hourly Rate ($) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="hourly_rate" 
                                id="hourly_rate"
                                step="0.01" 
                                min="0" 
                                value="{{ old('hourly_rate', 50) }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            >
                            @error('hourly_rate')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif
                    </div>

                    <div>
                        <label for="assigned_teacher_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Assigned Teacher
                        </label>
                        <select 
                            name="assigned_teacher_id" 
                            id="assigned_teacher_id"
                            class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        >
                            <option value="">Unassigned</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('assigned_teacher_id') == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_teacher_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('students.index') }}" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-medium rounded-lg shadow-sm hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                        Create Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
