<x-app-layout pageTitle="Edit Student">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Student Information</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update student details and package information</p>
            </div>

            <form action="{{ route('students.update', $student) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

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
                            value="{{ old('name', $student->name) }}" 
                            required
                            class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <x-country-selector 
                            name="country_code" 
                            :value="old('country_code', $student->country_code)" 
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
                                value="{{ old('whatsapp_number', $student->whatsapp_number) }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
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

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label for="package_hours_total" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Package Hours <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="package_hours_total" 
                                id="package_hours_total"
                                min="1" 
                                value="{{ old('package_hours_total', $student->package_hours_total) }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
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
                                    <option value="{{ $value }}" {{ old('payment_method', $student->payment_method) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Currency <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="currency" 
                                id="currency"
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            >
                                @php
                                    $currencies = [
                                        'AED' => 'درهم اماراتي (AED)',
                                        'USD' => 'دولار (USD)',
                                        'GBP' => 'جنيه استرليني (GBP)',
                                        'INR' => 'روبية هندية (INR)',
                                        'EGP' => 'الجنيه المصري (EGP)',
                                        'EUR' => 'يورو (EUR)',
                                        'SAR' => 'ريال سعودي (SAR)',
                                        'KWD' => 'دينار كويتي (KWD)',
                                        'QAR' => 'ريال قطري (QAR)',
                                        'JPY' => 'ين ياباني (JPY)',
                                        'CAD' => 'دولار كندي (CAD)',
                                        'AUD' => 'دولار استرالي (AUD)',
                                    ];
                                @endphp
                                @foreach($currencies as $code => $label)
                                    <option value="{{ $code }}" {{ old('currency', $student->currency ?? 'USD') === $code ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('currency')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
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
                                @foreach(['active' => 'Active', 'disabled' => 'Disabled'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', $student->status) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        @if(!Auth::user()->isSupport())
                        <div>
                            <label for="hourly_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Hourly Rate <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="hourly_rate" 
                                id="hourly_rate"
                                step="0.01" 
                                min="0" 
                                value="{{ old('hourly_rate', $student->hourly_rate) }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
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
                                <option value="{{ $teacher->id }}" {{ old('assigned_teacher_id', $student->assigned_teacher_id) == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_teacher_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if(!Auth::user()->isSupport())
                <!-- Package Info Display -->
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">
                    <h4 class="text-sm font-medium text-indigo-900 dark:text-indigo-200 mb-2">Current Package Status</h4>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-indigo-600 dark:text-indigo-400">Taken:</span>
                            <span class="ml-2 font-semibold text-indigo-900 dark:text-indigo-100">{{ $student->taken_hours }}h</span>
                        </div>
                        <div>
                            <span class="text-indigo-600 dark:text-indigo-400">Remaining:</span>
                            <span class="ml-2 font-semibold text-indigo-900 dark:text-indigo-100">{{ $student->remaining_hours }}h</span>
                        </div>
                        <div>
                            <span class="text-indigo-600 dark:text-indigo-400">Total Cost:</span>
                            <span class="ml-2 font-semibold text-indigo-900 dark:text-indigo-100">{{ $student->currency ?? 'USD' }} {{ number_format($student->package_hours_total * $student->hourly_rate, 2) }}</span>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('students.index') }}" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-medium rounded-lg shadow-sm hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                        Update Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
