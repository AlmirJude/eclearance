<div class="p-6">
    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl">{{ $department->name }}</flux:heading>
        <flux:subheading size="lg" class="mt-1">Department Overview & Clearance Analytics</flux:subheading>
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

        {{-- Overall Completion Card --}}
        <div class="mb-8 bg-gradient-to-r from-green-500 to-emerald-600 dark:from-green-700 dark:to-emerald-800 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium opacity-90">Overall Clearance Completion</h3>
                    <div class="mt-2 flex items-baseline">
                        <span class="text-5xl font-bold">{{ $completionPercentage }}%</span>
                        <span class="ml-2 text-lg opacity-75">of students completed</span>
                    </div>
                    <p class="mt-2 opacity-75">{{ $completedCount }} out of {{ $totalStudents }} students</p>
                </div>
                <div class="hidden md:block">
                    <svg class="h-24 w-24 opacity-25" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
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
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Clearance Completion by Year Level</h4>
                <button
                    type="button"
                    wire:click="exportDepartmentStudents"
                    class="inline-flex items-center px-3 py-1.5 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition"
                >
                    Download Whole Department Excel
                </button>
            </div>
            
            @if(count($yearLevelStats) === 0)
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    <p>No students in this department.</p>
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
                                <tr class="cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors"
                                    wire:click="selectYearLevel({{ $stat['year_level'] }})">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium
                                            {{ $selectedYearLevel === $stat['year_level'] ? 'bg-indigo-600 text-white' : 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300' }}">
                                            Year {{ $stat['year_level'] }}
                                            <svg class="w-3.5 h-3.5 transition-transform {{ $selectedYearLevel === $stat['year_level'] ? 'rotate-180' : '' }}"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
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
                                @if($selectedYearLevel === $stat['year_level'])
                                    <tr>
                                        <td colspan="7" class="px-0 pb-0 pt-0 bg-indigo-50 dark:bg-indigo-900/20">
                                            <div class="px-6 py-4">
                                                <div class="flex items-center justify-between gap-3 mb-3">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                                                        Year {{ $stat['year_level'] }} — All Students
                                                    </p>
                                                    <button
                                                        type="button"
                                                        wire:click="exportYearLevelStudents({{ $stat['year_level'] }})"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition"
                                                    >
                                                        Download Excel
                                                    </button>
                                                </div>
                                                @if(count($yearLevelDetails) === 0)
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">No students found in this year level.</p>
                                                @else
                                                    <table class="min-w-full text-sm">
                                                        <thead>
                                                            <tr class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                                                <th class="pr-6 py-2 text-left">Student</th>
                                                                <th class="pr-6 py-2 text-left">ID</th>
                                                                <th class="pr-6 py-2 text-left">Status</th>
                                                                <th class="pr-6 py-2 text-left">Dept. Signed At</th>
                                                                <th class="pr-6 py-2 text-left">Signed By</th>
                                                                <th class="py-2 text-left">Clearance Completed At</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-indigo-100 dark:divide-indigo-800">
                                                            @foreach($yearLevelDetails as $detail)
                                                                <tr class="hover:bg-indigo-100/50 dark:hover:bg-indigo-800/30">
                                                                    <td class="pr-6 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $detail['name'] }}</td>
                                                                    <td class="pr-6 py-2 text-gray-600 dark:text-gray-400">{{ $detail['student_id'] }}</td>
                                                                    <td class="pr-6 py-2">
                                                                        @if($detail['clearance_completed'])
                                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                                                                ✓ Completed
                                                                            </span>
                                                                        @elseif($detail['dept_signed'])
                                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                                                                ✓ Dept. Signed
                                                                            </span>
                                                                        @elseif($detail['request_status'] === 'in_progress' || $detail['request_status'] === 'pending')
                                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300">
                                                                                In Progress
                                                                            </span>
                                                                        @else
                                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                                                                No Request
                                                                            </span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="pr-6 py-2">
                                                                        @if($detail['dept_signed'])
                                                                            <span class="text-green-700 dark:text-green-400">{{ $detail['dept_signed_at'] ?? '—' }}</span>
                                                                        @else
                                                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="pr-6 py-2 text-gray-600 dark:text-gray-400">{{ $detail['signed_by'] ?? '—' }}</td>
                                                                    <td class="py-2 text-gray-600 dark:text-gray-400">{{ $detail['clearance_completed_at'] ?? '—' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        
        {{-- Quick Actions --}}
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('department.students', $departmentId) }}" 
            class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-700 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-800 transition">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
                View Students
            </a>
            <a href="{{ route('department.signatories', $departmentId) }}" 
            class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 text-white rounded-lg hover:bg-gray-700 dark:hover:bg-gray-800 transition">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Manage Signatories
            </a>
        </div>
    @endif
</div>
