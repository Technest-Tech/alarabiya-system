@php
    use Illuminate\Support\Str;
@endphp

<x-app-layout pageTitle="Families">
    <div class="space-y-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Family Accounts</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Group students under a family, track joint billings, and share summaries.
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.families.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Family
                </a>
            </div>
        </div>

        @if ($families->isEmpty())
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h8m-8 4h6M5 20h14a2 2 0 002-2V7a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2v13a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No families yet</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Create a family to group students and consolidate billing summaries.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach ($families as $family)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-6 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $family->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $family->whatsapp_number ?? 'No WhatsApp number' }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                    {{ $family->students_count }} {{ Str::plural('student', $family->students_count) }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-between">
                            <a href="{{ route('admin.families.show', $family) }}"
                               class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                                View Details
                            </a>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.families.edit', $family) }}"
                                   class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    Edit
                                </a>
                                <form action="{{ route('admin.families.destroy', $family) }}" method="POST" onsubmit="return confirm('Delete this family?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>


