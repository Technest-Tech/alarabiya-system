<x-app-layout pageTitle="Edit Teacher">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Teacher Information</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update teacher account details</p>
            </div>

            <form action="{{ route('teachers.update', $teacher) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Personal Information -->
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
                            value="{{ old('name', $teacher->user->name) }}" 
                            required
                            class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                id="email"
                                value="{{ old('email', $teacher->user->email) }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                New Password <span class="text-gray-500">(Leave blank to keep current)</span>
                            </label>
                            <input 
                                type="password" 
                                name="password" 
                                id="password"
                                minlength="6"
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                                placeholder="Enter new password"
                            >
                            @error('password')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-country-selector 
                            name="country_code" 
                            :value="old('country_code', $teacher->user->country_code)" 
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
                                value="{{ old('whatsapp_number', $teacher->user->whatsapp_number) }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            >
                            @error('whatsapp_number')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

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
                                value="{{ old('hourly_rate', $teacher->user->hourly_rate) }}" 
                                required
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            >
                            @error('hourly_rate')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif
                    </div>

                    @if(!Auth::user()->isSupport())
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
                            <option value="EGP" {{ old('currency', $teacher->currency ?? 'EGP') === 'EGP' ? 'selected' : '' }}>EGP</option>
                            <option value="USD" {{ old('currency', $teacher->currency ?? 'EGP') === 'USD' ? 'selected' : '' }}>USD</option>
                        </select>
                        @error('currency')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif
                    </div>
                </div>

                <!-- Teacher Stats -->
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">
                    <h4 class="text-sm font-medium text-indigo-900 dark:text-indigo-200 mb-3">Teacher Statistics</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-indigo-600 dark:text-indigo-400">Assigned Students:</span>
                            <span class="ml-2 font-semibold text-indigo-900 dark:text-indigo-100">{{ $teacher->students()->count() }}</span>
                        </div>
                        <div>
                            <span class="text-indigo-600 dark:text-indigo-400">Total Lessons:</span>
                            <span class="ml-2 font-semibold text-indigo-900 dark:text-indigo-100">{{ $teacher->lessons()->count() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('teachers.index') }}" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-medium rounded-lg shadow-sm hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                        Update Teacher
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

