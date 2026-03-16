<div class="p-6">
    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl">{{ $office->name }}</flux:heading>
        <p class="text-gray-600 mt-2">Office Overview & Clearance Analytics</p>
        @if($office->is_required)
            <span class="inline-flex items-center px-3 py-1 mt-2 rounded-full text-sm font-medium bg-red-100 text-red-800">
                Required for Clearance
            </span>
        @endif
    </div>

    <flux:separator class="my-6" />

    @if(!$activePeriod)
        <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-yellow-400 dark:text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-yellow-800 dark:text-yellow-300">No Active Clearance Period</h3>
            <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">There is no active clearance period. Analytics will be available once a period is activated.</p>
        </div>
    @else
        {{-- Active Period Info --}}
        <div class="mb-6 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-blue-500 dark:text-blue-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-blue-800 dark:text-blue-300 font-medium">Active Period: {{ $activePeriod->name }}</span>
                <span class="text-blue-600 dark:text-blue-400 text-sm ml-2">({{ $activePeriod->semester }} - {{ $activePeriod->academic_year }})</span>
            </div>
        </div>

        {{-- Department Filter --}}
        @if(count($departments) > 0)
            <div class="mb-6 flex items-center gap-4">
                <label for="departmentFilter" class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter by Department:</label>
                <select 
                    id="departmentFilter" 
                    wire:model.live="departmentFilter" 
                    class="rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                >
                    <option value="" class="dark:bg-gray-700">All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" class="dark:bg-gray-700">{{ $department->name }}</option>
                    @endforeach
                </select>
                @if($departmentFilter)
                    <button 
                        wire:click="$set('departmentFilter', '')" 
                        class="text-sm text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 underline"
                    >
                        Clear Filter
                    </button>
                @endif
            </div>
        @endif

        {{-- Overall Completion Card --}}
        <div class="mb-8 bg-gradient-to-r from-teal-500 to-cyan-600 dark:from-teal-700 dark:to-cyan-800 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium opacity-90">
                        @if($departmentFilter)
                            {{ $departments->firstWhere('id', $departmentFilter)?->name }} - Clearance Completion
                        @else
                            Overall Clearance Completion
                        @endif
                    </h3>
                    <div class="mt-2 flex items-baseline">
                        <span class="text-5xl font-bold">{{ $completionPercentage }}%</span>
                        <span class="ml-2 text-lg opacity-75">of students completed</span>
                    </div>
                    <p class="mt-2 opacity-75">{{ $completedCount }} out of {{ $totalStudents }} students</p>
                </div>
                <div class="hidden md:block">
                    <svg class="h-24 w-24 opacity-25" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
            
            {{-- Progress Bar --}}
            <div class="mt-4">
                <div class="w-full bg-white/20 rounded-full h-3">
                    <div class="bg-white rounded-full h-3 transition-all duration-500" style="width: {{ $completionPercentage }}%"></div>
                </div>
            </div>
        </div>

        {{-- Status Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-4 border border-green-200 dark:border-green-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-10 w-10 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-green-800 dark:text-green-300">Completed</p>
                        <p class="text-3xl font-bold text-green-900 dark:text-green-200">{{ $completedCount }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-10 w-10 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-300">In Progress</p>
                        <p class="text-3xl font-bold text-blue-900 dark:text-blue-200">{{ $inProgressCount }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/30 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-10 w-10 text-yellow-500 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Pending</p>
                        <p class="text-3xl font-bold text-yellow-900 dark:text-yellow-200">{{ $pendingCount }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">No Request</p>
                        <p class="text-3xl font-bold text-gray-800 dark:text-gray-200">{{ $noRequestCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Year Level Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Clearance Completion by Year Level</h4>
            </div>
            
            @if(count($yearLevelStats) === 0)
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    @if($departmentFilter)
                        <p>No students from this department.</p>
                    @else
                        <p>No students found.</p>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Year Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Students</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">In Progress</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pending</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">No Request</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($yearLevelStats as $stat)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-teal-100 dark:bg-teal-900/50 text-teal-800 dark:text-teal-300">
                                            Year {{ $stat['year_level'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $stat['total'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $stat['completed'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ $stat['in_progress'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-yellow-600 dark:text-yellow-400">{{ $stat['pending'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['no_request'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                                <div class="h-2 rounded-full transition-all duration-300
                                                    {{ $stat['percentage'] >= 75 ? 'bg-green-500 dark:bg-green-400' : ($stat['percentage'] >= 50 ? 'bg-yellow-500 dark:bg-yellow-400' : 'bg-red-500 dark:bg-red-400') }}" 
                                                    style="width: {{ $stat['percentage'] }}%">
                                                </div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $stat['percentage'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        
        {{-- Quick Actions --}}
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('office.signatories', $officeId) }}" 
            class="inline-flex items-center px-4 py-2 bg-teal-600 dark:bg-teal-700 text-white rounded-lg hover:bg-teal-700 dark:hover:bg-teal-800 transition">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Manage Signatories
            </a>
            <a href="{{ route('office.requirements', $officeId) }}" 
            class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 text-white rounded-lg hover:bg-gray-700 dark:hover:bg-gray-800 transition">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Manage Requirements
            </a>
        </div>
    @endif
</div>
