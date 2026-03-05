<div class="p-6">
    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl">{{ $department->name }}</flux:heading>
        <p class="text-gray-600 mt-2">Department Overview & Clearance Analytics</p>
    </div>

    <flux:separator class="my-6" />

    @if(!$activePeriod)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-yellow-800">No Active Clearance Period</h3>
            <p class="mt-2 text-sm text-yellow-600">There is no active clearance period. Analytics will be available once a period is activated.</p>
        </div>
    @else
        {{-- Active Period Info --}}
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-blue-800 font-medium">Active Period: {{ $activePeriod->name }}</span>
                <span class="text-blue-600 text-sm ml-2">({{ $activePeriod->semester }} - {{ $activePeriod->academic_year }})</span>
            </div>
        </div>

        {{-- Overall Completion Card --}}
        <div class="mb-8 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
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
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-10 w-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-green-800">Completed</p>
                        <p class="text-3xl font-bold text-green-900">{{ $completedCount }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-10 w-10 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-800">In Progress</p>
                        <p class="text-3xl font-bold text-blue-900">{{ $inProgressCount }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-10 w-10 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-yellow-800">Pending</p>
                        <p class="text-3xl font-bold text-yellow-900">{{ $pendingCount }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">No Request</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $noRequestCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Year Level Breakdown --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h4 class="text-lg font-semibold text-gray-900">Clearance Completion by Year Level</h4>
            </div>
            
            @if(count($yearLevelStats) === 0)
                <div class="p-6 text-center text-gray-500">
                    <p>No students in this department.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Students</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">In Progress</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Request</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($yearLevelStats as $stat)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                            Year {{ $stat['year_level'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        {{ $stat['total'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-green-600">{{ $stat['completed'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-blue-600">{{ $stat['in_progress'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-yellow-600">{{ $stat['pending'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-500">{{ $stat['no_request'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="h-2 rounded-full transition-all duration-300
                                                    {{ $stat['percentage'] >= 75 ? 'bg-green-500' : ($stat['percentage'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                                    style="width: {{ $stat['percentage'] }}%">
                                                </div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700">{{ $stat['percentage'] }}%</span>
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
            <a href="{{ route('department.students', $departmentId) }}" 
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
                View Students
            </a>
            <a href="{{ route('department.signatories', $departmentId) }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Manage Signatories
            </a>
        </div>
    @endif
</div>
