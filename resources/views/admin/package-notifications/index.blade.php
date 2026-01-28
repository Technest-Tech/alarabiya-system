<x-app-layout pageTitle="Package Notifications">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Package Notifications</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Students who have completed their packages</p>
            </div>
            <form class="mt-4 sm:mt-0 flex items-center gap-3" method="GET" action="{{ route('admin.package-notifications.index') }}">
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" 
                           id="search-input"
                           name="search" 
                           value="{{ $search ?? '' }}" 
                           placeholder="Search by student name..." 
                           class="w-full px-4 py-2 pl-10 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                    Search
                </button>
                @if($search ?? '')
                    <a href="{{ route('admin.package-notifications.index') }}" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Notifications Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($completedPackages as $package)
                <div id="package-{{ $package->id }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-all duration-200">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-semibold text-lg">
                                {{ strtoupper(substr($package->student->name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $package->student->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $package->student->whatsapp_number }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                            Completed
                        </span>
                    </div>

                    <!-- Teacher Info -->
                    <div class="mb-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Teacher</p>
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ $package->student->teacher?->user?->name ?? 'Unassigned' }}
                        </p>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Package</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $package->package_hours }} hrs</p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Hours Used</p>
                            <p class="mt-1 text-xl font-bold text-indigo-600 dark:text-indigo-400">
                                {{ number_format($package->hours_used_decimal, 2) }} hrs
                            </p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Cost</p>
                            <p class="mt-1 text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ $package->student->currency ?? 'USD' }} {{ number_format($package->package_hours * $package->student->hourly_rate, 2) }}</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>Usage</span>
                            <span>{{ $package->package_hours > 0 ? round(($package->hours_used_decimal / $package->package_hours) * 100) : 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-2 rounded-full transition-all duration-300" style="width: {{ $package->package_hours > 0 ? min(100, ($package->hours_used_decimal / $package->package_hours) * 100) : 0 }}%"></div>
                        </div>
                    </div>

                    <!-- Package Info -->
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Completed:</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $package->completed_at ? $package->completed_at->format('M d, Y') : '—' }}
                            </span>
                        </div>
                        @if($package->completed_at)
                            <div class="text-xs text-gray-500 dark:text-gray-400 text-right">
                                {{ $package->completed_at->diffForHumans() }}
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Pending Lessons:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $package->student->pending_lessons_count > 0 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $package->student->pending_lessons_count }} pending
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button 
                            type="button" 
                            class="mark-paid-btn w-full inline-flex items-center justify-center px-4 py-2 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-green-700 dark:hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-green-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" 
                            style="background-color: #16a34a;"
                            data-package-id="{{ $package->id }}"
                            data-student-name="{{ $package->student->name }}"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="btn-text">Mark as Paid</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No completed packages</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All students have active packages or have already been paid.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/40 px-4 py-6">
        <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/20">
                        <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Confirm Action</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" id="confirmMessage">Mark this package as paid and create a new package? This will activate all pending lessons.</p>
                    </div>
                </div>
                <button type="button" onclick="closeConfirmModal()" class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4 flex gap-3">
                <button
                    type="button"
                    onclick="closeConfirmModal()"
                    class="flex-1 inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    id="confirmOkBtn"
                    class="flex-1 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span id="confirmBtnText">Confirm</span>
                    <svg id="confirmSpinner" class="hidden ml-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/40 px-4 py-6">
        <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Error</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400" id="errorMessage">An error occurred. Please try again.</p>
                    </div>
                </div>
                <button type="button" onclick="closeErrorModal()" class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4">
                <button
                    type="button"
                    onclick="closeErrorModal()"
                    class="w-full inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    OK
                </button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/40 px-4 py-6">
        <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Success</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400" id="successMessage">Package marked as paid and renewed successfully.</p>
                    </div>
                </div>
                <button type="button" onclick="closeSuccessModal()" class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4">
                <button
                    type="button"
                    onclick="closeSuccessModal()"
                    class="w-full inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    OK
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentPackageId = null;
        let currentButton = null;
        let currentBtnText = null;
        let originalBtnText = null;

        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.mark-paid-btn');
            
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Prevent multiple clicks
                    if (this.disabled) {
                        return;
                    }
                    
                    currentPackageId = this.dataset.packageId;
                    currentButton = this;
                    currentBtnText = this.querySelector('.btn-text');
                    originalBtnText = currentBtnText.textContent;
                    
                    // Show confirmation modal
                    showConfirmModal();
                });
            });

            // Handle confirm button click
            const confirmOkBtn = document.getElementById('confirmOkBtn');
            if (confirmOkBtn) {
                confirmOkBtn.addEventListener('click', function() {
                    if (!currentPackageId || this.disabled) {
                        return;
                    }
                    
                    // Disable confirm button and show spinner
                    this.disabled = true;
                    const btnText = document.getElementById('confirmBtnText');
                    const spinner = document.getElementById('confirmSpinner');
                    if (btnText) btnText.textContent = 'Processing...';
                    if (spinner) spinner.classList.remove('hidden');
                    
                    // Close confirmation modal
                    closeConfirmModal();
                    
                    // Disable original button
                    if (currentButton) {
                        currentButton.disabled = true;
                        if (currentBtnText) {
                            currentBtnText.textContent = 'Processing...';
                        }
                    }
                    
                    // Process the request
                    processMarkAsPaid();
                });
            }
        });
        
        function showConfirmModal() {
            const modal = document.getElementById('confirmModal');
            const messageEl = document.getElementById('confirmMessage');
            if (messageEl && currentButton) {
                const studentName = currentButton.dataset.studentName;
                messageEl.textContent = `Mark ${studentName}'s package as paid and create a new package? This will activate all pending lessons.`;
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function closeConfirmModal() {
            const modal = document.getElementById('confirmModal');
            const confirmOkBtn = document.getElementById('confirmOkBtn');
            const btnText = document.getElementById('confirmBtnText');
            const spinner = document.getElementById('confirmSpinner');
            
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            
            // Reset confirm button state
            if (confirmOkBtn) confirmOkBtn.disabled = false;
            if (btnText) btnText.textContent = 'Confirm';
            if (spinner) spinner.classList.add('hidden');
        }
        
        function processMarkAsPaid() {
            if (!currentPackageId) {
                return;
            }
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (!csrfToken) {
                console.error('CSRF token not found');
                showErrorModal('CSRF token not found. Please refresh the page and try again.');
                resetButtonState();
                return;
            }
            
            // Make AJAX request
            const markPaidUrl = `{{ route('admin.package-notifications.mark-paid', ':id') }}`.replace(':id', currentPackageId);
            
            fetch(markPaidUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch {
                            throw new Error(text || 'Server error');
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove the package card with animation
                    const packageCard = document.getElementById('package-' + currentPackageId);
                    if (packageCard) {
                        packageCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        packageCard.style.opacity = '0';
                        packageCard.style.transform = 'scale(0.95)';
                        
                        setTimeout(() => {
                            packageCard.remove();
                            
                            // Check if no packages left
                            const remainingPackages = document.querySelectorAll('[id^="package-"]');
                            if (remainingPackages.length === 0) {
                                // Reload page to show empty state
                                window.location.reload();
                            }
                        }, 300);
                    }
                    
                    // Show success modal
                    showSuccessModal(data.message || 'Package marked as paid and renewed successfully.');
                    
                    // Reset state
                    currentPackageId = null;
                    currentButton = null;
                    currentBtnText = null;
                    originalBtnText = null;
                } else {
                    throw new Error(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('An error occurred: ' + (error.message || 'Please try again'));
                resetButtonState();
            });
        }
        
        function resetButtonState() {
            if (currentButton) {
                currentButton.disabled = false;
            }
            if (currentBtnText && originalBtnText) {
                currentBtnText.textContent = originalBtnText;
            }
            
            const confirmOkBtn = document.getElementById('confirmOkBtn');
            const btnText = document.getElementById('confirmBtnText');
            const spinner = document.getElementById('confirmSpinner');
            
            if (confirmOkBtn) confirmOkBtn.disabled = false;
            if (btnText) btnText.textContent = 'Confirm';
            if (spinner) spinner.classList.add('hidden');
        }
        
        function showSuccessModal(message) {
            const modal = document.getElementById('successModal');
            const messageEl = document.getElementById('successMessage');
            if (messageEl) {
                messageEl.textContent = message;
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function showErrorModal(message) {
            const modal = document.getElementById('errorModal');
            const messageEl = document.getElementById('errorMessage');
            if (messageEl) {
                messageEl.textContent = message;
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function closeErrorModal() {
            const modal = document.getElementById('errorModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>

</x-app-layout>

