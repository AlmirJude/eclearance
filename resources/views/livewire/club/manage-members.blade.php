<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Manage Club Members') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ $club->name }} ({{ $club->Abbreviation }})</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="p-4 mb-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-700">{{ session('success') }}</p>
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="p-4 mb-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex gap-2 mb-4">
        <a href="{{ route('club.index') }}" class="px-3 py-2 text-xs text-white bg-gray-600 rounded hover:bg-gray-700">
            Back to Clubs
        </a>
        <button wire:click="openAddModal" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
            Add Member
        </button>
        <button wire:click="openImportModal" class="px-3 py-2 text-xs text-white bg-indigo-600 rounded hover:bg-indigo-700">
            Import Members
        </button>
        <button wire:click="downloadSampleCsv" class="px-3 py-2 text-xs text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200">
            Download Template
        </button>
        <button
            wire:click="removeAllMembers"
            wire:confirm="Are you sure you want to remove ALL members from this club? This action cannot be undone."
            @disabled($members->isEmpty())
            class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
            Remove All Members
        </button>
    </div>

    {{-- Members Count --}}
    <div class="mb-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">Total Members: <span class="font-semibold dark:text-white">{{ $members->count() }}</span></p>
    </div>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center">
        <input
            type="text"
            wire:model.live.debounce.300ms="memberSearch"
            placeholder="Search by student ID, name, department, year level, or email..."
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 lg:flex-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
        >

        <select
            wire:model.live="memberDepartmentFilter"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 lg:w-72 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
        >
            <option value="" class="dark:bg-gray-700">All Departments</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" class="dark:bg-gray-700">
                    {{ $department->name }}{{ $department->abbreviation ? ' (' . $department->abbreviation . ')' : '' }}
                </option>
            @endforeach
        </select>

        <select
            wire:model.live="memberYearLevelFilter"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 lg:w-48 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
        >
            <option value="" class="dark:bg-gray-700">All Year Levels</option>
            <option value="1" class="dark:bg-gray-700">1st Year</option>
            <option value="2" class="dark:bg-gray-700">2nd Year</option>
            <option value="3" class="dark:bg-gray-700">3rd Year</option>
            <option value="4" class="dark:bg-gray-700">4th Year</option>
            <option value="5" class="dark:bg-gray-700">5th Year</option>
            <option value="6" class="dark:bg-gray-700">6th Year</option>
        </select>
    </div>

    {{-- Members Table --}}
    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white dark:bg-gray-800">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700 dark:text-gray-200">
            <thead class="bg-gray-100 dark:bg-gray-900 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Student ID</th>
                    <th scope="col" class="px-6 py-3">Name</th>
                    <th scope="col" class="px-6 py-3">Department</th>
                    <th scope="col" class="px-6 py-3">Year Level</th>
                    <th scope="col" class="px-6 py-3">Actions</th>
                </tr>   
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-2">{{ $member->studentDetail->student_id ?? 'N/A' }}</td>
                        <td class="px-6 py-2">{{ $member->fullname }}</td>
                        <td class="px-6 py-2">{{ $member->studentDetail->department_name ?? 'N/A' }}</td>
                        <td class="px-6 py-2">{{ $member->studentDetail->year_level ?? 'N/A' }}</td>
                        <td class="px-6 py-2">
                            <button 
                                wire:click='removeMember({{ $member->id }})' 
                                wire:confirm='Are you sure you want to remove this member from the club?' 
                                class="px-3 py-2 text-xs text-white bg-red-600 dark:bg-red-700 rounded hover:bg-red-700 dark:hover:bg-red-800">
                                Remove
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            No members yet. Click "Add Member" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add Member Modal --}}
    @if($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Add New Member</h3>
                    <button wire:click="closeAddModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="addMember" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Student *</label>

                        <div class="relative mb-3">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="studentSearch"
                                placeholder="Search by name, student ID, department, or year..."
                                class="w-full px-4 py-2.5 pl-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>

                        <div class="border border-gray-300 dark:border-gray-700 rounded-lg max-h-64 overflow-y-auto bg-gray-50 dark:bg-gray-900">
                            @forelse($availableStudents as $student)
                                <label class="flex items-center gap-3 p-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition">
                                    <input
                                        type="radio"
                                        wire:model="selectedStudent"
                                        value="{{ $student->id }}"
                                        class="w-4 h-4 text-blue-600 dark:text-blue-400 border-gray-300 dark:border-gray-600 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $student->fullname }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Student ID: {{ $student->studentDetail->student_id ?? 'N/A' }}
                                            @if($student->studentDetail && $student->studentDetail->department)
                                                • {{ $student->studentDetail->department->abbreviation ?? $student->studentDetail->department->name }}
                                            @endif
                                            @if($student->studentDetail && $student->studentDetail->year_level)
                                                • Year {{ $student->studentDetail->year_level }}
                                            @endif
                                        </p>
                                    </div>
                                </label>
                            @empty
                                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <p class="text-sm">No students found</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Try adjusting your search</p>
                                </div>
                            @endforelse
                        </div>

                        @error('selectedStudent')
                            <span class="text-red-500 dark:text-red-400 text-sm mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex gap-2 justify-end">
                        <button 
                            type="button"
                            wire:click="closeAddModal"
                            class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 text-sm text-white bg-blue-600 dark:bg-blue-700 rounded hover:bg-blue-700 dark:hover:bg-blue-800">
                            Add Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Import Members Modal --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Import Members</h3>
                    <button wire:click="closeImportModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Upload a <strong>CSV</strong> or <strong>Excel</strong> file with a single column:
                    <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">student_id</code>.
                    Students not yet in this club will be added automatically.
                </p>

                @if(!$importResults)
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CSV or Excel File</label>
                            <input type="file" wire:model="csvFile" accept=".csv,.txt,.xlsx,.xls"
                                class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800/30 bg-white dark:bg-gray-700" />
                            @error('csvFile')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <div wire:loading wire:target="csvFile" class="mt-1 text-xs text-gray-400 dark:text-gray-500">Uploading...</div>
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end mt-4">
                        <button type="button" wire:click="closeImportModal"
                                class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="button" wire:click="importCsv"
                                wire:loading.attr="disabled" wire:target="importCsv"
                                class="px-4 py-2 text-sm text-white bg-indigo-600 dark:bg-indigo-700 rounded hover:bg-indigo-700 dark:hover:bg-indigo-800 disabled:opacity-60">
                            <span wire:loading.remove wire:target="importCsv">Import</span>
                            <span wire:loading wire:target="importCsv">Importing...</span>
                        </button>
                    </div>
                @else
                    {{-- Results --}}
                    <div class="space-y-3">
                        <div class="flex gap-4">
                            <div class="flex-1 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 p-3 text-center">
                                <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $importResults['imported'] }}</p>
                                <p class="text-xs text-green-600 dark:text-green-400">Imported</p>
                            </div>
                            <div class="flex-1 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 p-3 text-center">
                                <p class="text-2xl font-bold text-red-700 dark:text-red-400">{{ $importResults['skipped'] }}</p>
                                <p class="text-xs text-red-600 dark:text-red-400">Skipped</p>
                            </div>
                        </div>

                        @if(!empty($importResults['errors']))
                            <div class="max-h-48 overflow-y-auto rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 p-3 space-y-1">
                                <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-2">Errors / Skipped Rows</p>
                                @foreach($importResults['errors'] as $err)
                                    <p class="text-xs text-red-600 dark:text-red-400">{{ $err }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-2 justify-end mt-4">
                        <button type="button" wire:click="closeImportModal"
                                class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                            Close
                        </button>
                        <button type="button" wire:click="$set('importResults', null)"
                                class="px-4 py-2 text-sm text-white bg-indigo-600 dark:bg-indigo-700 rounded hover:bg-indigo-700 dark:hover:bg-indigo-800">
                            Import Another File
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
