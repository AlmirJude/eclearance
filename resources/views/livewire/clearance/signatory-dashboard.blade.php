@use('Illuminate\Support\Facades\Storage')

<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Signatory Dashboard</flux:heading>
        <p class="text-gray-600 mt-2">Approve or reject student clearances</p>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <flux:separator class="my-6" />

    @if(count($signatoryEntities) === 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-yellow-800">No Signatory Role Assigned</h3>
            <p class="mt-2 text-sm text-yellow-600">You are not currently assigned as a signatory for any organization.</p>
        </div>
    @else
        {{-- Entity Selector Tabs --}}
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-4 overflow-x-auto" aria-label="Tabs">
                    @foreach($signatoryEntities as $index => $entity)
                        <button wire:click="selectEntity({{ $index }})"
                                class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm transition-colors
                                       {{ $selectedEntity && $selectedEntity['id'] === $entity['id'] && $selectedEntityType === $entity['type']
                                          ? 'border-blue-500 text-blue-600' 
                                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ $entity['name'] }}
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $entity['label'] }}
                            </span>
                        </button>
                    @endforeach
                </nav>
            </div>
        </div>

        @if($selectedEntity)
            {{-- Stats Cards (Clickable) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <button wire:click="filterByStatus('pending')" 
                        class="bg-yellow-50 rounded-lg p-4 border-2 transition-all hover:shadow-md text-left
                               {{ $statusFilter === 'pending' ? 'border-yellow-500 ring-2 ring-yellow-200' : 'border-yellow-200' }}">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-yellow-800">Pending</p>
                            <p class="text-2xl font-semibold text-yellow-900">{{ $pendingCount }}</p>
                        </div>
                    </div>
                </button>
                <button wire:click="filterByStatus('approved')"
                        class="bg-green-50 rounded-lg p-4 border-2 transition-all hover:shadow-md text-left
                               {{ $statusFilter === 'approved' ? 'border-green-500 ring-2 ring-green-200' : 'border-green-200' }}">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-green-800">Approved</p>
                            <p class="text-2xl font-semibold text-green-900">{{ $approvedCount }}</p>
                        </div>
                    </div>
                </button>
                <button wire:click="filterByStatus('rejected')"
                        class="bg-red-50 rounded-lg p-4 border-2 transition-all hover:shadow-md text-left
                               {{ $statusFilter === 'rejected' ? 'border-red-500 ring-2 ring-red-200' : 'border-red-200' }}">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-red-800">Rejected</p>
                            <p class="text-2xl font-semibold text-red-900">{{ $rejectedCount }}</p>
                        </div>
                    </div>
                </button>
            </div>

            {{-- Action Buttons --}}
            <div class="mb-6 flex flex-wrap gap-3">
                {{-- Manage Requirements Button (for Clubs, Offices, and Homeroom) --}}
                @if($selectedEntity && $selectedEntity['label'] === 'Club')
                    <a href="{{ route('club.requirements', ['id' => $selectedEntity['id']]) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Manage Requirements
                    </a>
                @elseif($selectedEntity && $selectedEntity['label'] === 'Office')
                    <a href="{{ route('office.requirements', ['id' => $selectedEntity['id']]) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Manage Requirements
                    </a>
                @elseif($selectedEntity && $selectedEntity['label'] === 'Homeroom')
                    <a href="{{ route('homeroom.requirements', ['id' => $selectedEntity['id']]) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Manage Requirements
                    </a>
                @endif
                
                {{-- Bulk Sign Button --}}
                @if($pendingCount > 0)
                    <button wire:click="openBulkSignModal"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Bulk Approve by Program & Year Level
                    </button>
                @endif
            </div>

            {{-- Filtered Items Table --}}
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center gap-4">
                    <h4 class="text-lg font-semibold text-gray-900">
                        {{ $statusFilter === 'pending' ? 'Pending Clearances' : ($statusFilter === 'approved' ? 'Approved Students' : 'Rejected Students') }}
                    </h4>
                    <div class="flex flex-wrap items-center gap-3 ml-auto">
                        {{-- Search --}}
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="searchQuery"
                                   placeholder="Search name or student ID..."
                                   class="pl-8 pr-3 py-1.5 text-sm rounded-lg border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-60" />
                            <svg class="absolute left-2.5 top-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        {{-- Requirements/Submissions filter --}}
                        <select wire:model.live="requirementsFilter"
                                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="all">All Submissions</option>
                            <option value="with_submissions">Has Attached Files</option>
                            <option value="without_submissions">No Attachments</option>
                        </select>
                        {{-- Department Filter --}}
                        @if(count($availableDepartments) > 1)
                            <select wire:model.live="departmentFilter"
                                    class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">All Departments</option>
                                @foreach($availableDepartments as $dept)
                                    <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                                @endforeach
                            </select>
                        @endif
                        {{-- Year Level Filter --}}
                        <select wire:model.live="yearLevelFilter"
                                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">All Year Levels</option>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                        {{-- Clear Filters --}}
                        @if($departmentFilter || $yearLevelFilter || $searchQuery || $requirementsFilter !== 'all')
                            <button wire:click="clearFilters"
                                    class="text-sm text-blue-600 hover:text-blue-800 underline">
                                Clear Filters
                            </button>
                        @endif
                    </div>
                </div>

                @if(count($filteredItems) === 0)
                    <div class="p-6 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2">No {{ $statusFilter }} clearances at the moment.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                                    @if($statusFilter === 'pending')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    @else
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Signed By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                    @endif
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($filteredItems as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $item['student_name'] }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ $item['student_id'] }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ $item['department'] ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ $item['year_level'] !== 'N/A' ? 'Year ' . $item['year_level'] : 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if(!empty($item['has_submissions']) && $item['has_submissions'])
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                                    &#128206; {{ $item['submissions_count'] }} file{{ $item['submissions_count'] !== 1 ? 's' : '' }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">&mdash;</span>
                                            @endif
                                        </td>
                                        @if($statusFilter === 'pending')
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($item['can_sign'])
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Ready to Sign
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Blocked
                                                    </span>
                                                @endif
                                            </td>
                                        @else
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $item['signed_by'] ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">{{ $item['signed_at'] ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-500 max-w-xs truncate" title="{{ $item['remarks'] }}">
                                                    {{ $item['remarks'] ?? '-' }}
                                                </div>
                                            </td>
                                        @endif
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button wire:click="viewSubmissions({{ $item['id'] }})"
                                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                                View Submissions
                                            </button>
                                            @if($statusFilter === 'pending')
                                                @if($item['can_sign'])
                                                    <button wire:click="openSignModal({{ $item['id'] }}, 'approve')" 
                                                            class="text-green-600 hover:text-green-900 mr-3">
                                                        Approve
                                                    </button>
                                                    <button wire:click="openSignModal({{ $item['id'] }}, 'reject')"
                                                            class="text-red-600 hover:text-red-900">
                                                        Reject
                                                    </button>
                                                @else
                                                    <span class="text-gray-400 text-xs">
                                                        Waiting for prerequisites
                                                    </span>
                                                @endif
                                            @elseif($statusFilter === 'rejected')
                                                <button wire:click="openSignModal({{ $item['id'] }}, 'approve')" 
                                                        class="text-green-600 hover:text-green-900">
                                                    Approve
                                                </button>
                                            @elseif($statusFilter === 'approved')
                                                <button wire:click="openSignModal({{ $item['id'] }}, 'reject')"
                                                        class="text-red-600 hover:text-red-900">
                                                    Decline
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- Sign/Reject Modal --}}
    @if($showSignModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $signAction === 'approve' ? 'Approve Clearance' : 'Reject Clearance' }}
                    </h3>
                </div>
                <div class="px-6 py-4">
                    @if($signAction === 'approve')
                        <p class="text-sm text-gray-600 mb-4">Are you sure you want to approve this clearance?</p>
                    @else
                        <p class="text-sm text-gray-600 mb-4">Please provide a reason for rejection:</p>
                    @endif
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Remarks {{ $signAction === 'reject' ? '(Required)' : '(Optional)' }}
                        </label>
                        <textarea wire:model="remarks" rows="3"
                                  class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter any additional remarks..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                    <flux:button wire:click="$set('showSignModal', false)" variant="ghost">
                        Cancel
                    </flux:button>
                    @if($signAction === 'approve')
                        <flux:button wire:click="sign" variant="primary" class="bg-green-600 hover:bg-green-700">
                            Approve
                        </flux:button>
                    @else
                        <flux:button wire:click="sign" variant="primary" class="bg-red-600 hover:bg-red-700">
                            Reject
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- View Submissions Modal --}}
    @if($showSubmissionsModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Submissions from {{ $viewingStudentName }}
                    </h3>
                </div>
                <div class="px-6 py-4 overflow-y-auto flex-1">
                    @if(count($studentSubmissions) === 0)
                        <div class="text-center text-gray-500 py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2">No requirements to submit for this clearance.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($studentSubmissions as $submission)
                                <div class="border rounded-lg p-4 {{ $submission['status'] === 'approved' ? 'bg-green-50 border-green-200' : ($submission['status'] === 'rejected' ? 'bg-red-50 border-red-200' : 'bg-white border-gray-200') }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">{{ $submission['requirement_name'] }}</h4>
                                            @if($submission['requirement_description'])
                                                <p class="text-sm text-gray-500 mt-1">{{ $submission['requirement_description'] }}</p>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $submission['status'] === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $submission['status'] === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $submission['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                            {{ ucfirst($submission['status']) }}
                                        </span>
                                    </div>
                                    
                                    @if($submission['notes'])
                                        <div class="mt-2 text-sm text-gray-600">
                                            <span class="font-medium">Student Notes:</span> {{ $submission['notes'] }}
                                        </div>
                                    @endif
                                    
                                    <div class="mt-3 flex items-center justify-between">
                                        <span class="text-xs text-gray-400">Submitted: {{ $submission['submitted_at'] }}</span>
                                        
                                        @if($submission['file_path'])
                                            <a href="{{ Storage::url($submission['file_path']) }}" 
                                               target="_blank"
                                               class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Download File
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400 italic">No file attached</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end rounded-b-lg">
                    <flux:button wire:click="$set('showSubmissionsModal', false)" variant="ghost">
                        Close
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk Sign Modal --}}
    @if($showBulkSignModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Bulk Approve Clearances
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Select programs and year levels to automatically approve all matching pending clearances.
                    </p>
                </div>
                <div class="px-6 py-4 space-y-5">
                    {{-- Department/Program Selection --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Programs / Departments</label>
                        @if(count($availableDepartments) > 0)
                            <div class="space-y-2 max-h-40 overflow-y-auto border rounded-lg p-3">
                                @foreach($availableDepartments as $dept)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" 
                                               wire:model.live="bulkDepartments" 
                                               value="{{ $dept['id'] }}"
                                               class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                        <span class="text-sm text-gray-700">{{ $dept['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 italic">No departments available.</p>
                        @endif
                    </div>
                    
                    {{-- Year Level Selection --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year Levels</label>
                        <div class="flex flex-wrap gap-3">
                            @foreach([1,2,3,4] as $yr)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" 
                                           wire:model.live="bulkYearLevels" 
                                           value="{{ $yr }}"
                                           class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                    <span class="text-sm text-gray-700">Year {{ $yr }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    
                    {{-- Match Count --}}
                    <div class="rounded-lg p-3 {{ $bulkMatchCount > 0 ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' }}">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 {{ $bulkMatchCount > 0 ? 'text-green-500' : 'text-gray-400' }} mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-sm font-medium {{ $bulkMatchCount > 0 ? 'text-green-800' : 'text-gray-600' }}">
                                {{ $bulkMatchCount }} pending clearance{{ $bulkMatchCount !== 1 ? 's' : '' }} will be approved
                            </span>
                        </div>
                    </div>
                    
                    {{-- Remarks --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Remarks (Optional)
                        </label>
                        <textarea wire:model="bulkRemarks" rows="2"
                                  class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                                  placeholder="e.g. Bulk approved for clearance period..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-between items-center rounded-b-lg">
                    <p class="text-xs text-gray-400">Only students whose prerequisites are met will be signed.</p>
                    <div class="flex gap-3">
                        <flux:button wire:click="$set('showBulkSignModal', false)" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button 
                            wire:click="bulkSign" 
                            variant="primary" 
                            class="bg-green-600 hover:bg-green-700"
                            :disabled="$bulkMatchCount === 0"
                        >
                            <span wire:loading.remove wire:target="bulkSign">Approve {{ $bulkMatchCount }} Student{{ $bulkMatchCount !== 1 ? 's' : '' }}</span>
                            <span wire:loading wire:target="bulkSign">Signing...</span>
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
