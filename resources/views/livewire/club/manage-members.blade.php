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
        <button wire:click="$set('showAddModal', true)" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
            Add Member
        </button>
        <button wire:click="openImportModal" class="px-3 py-2 text-xs text-white bg-indigo-600 rounded hover:bg-indigo-700">
            Import Members
        </button>
        <button wire:click="downloadSampleCsv" class="px-3 py-2 text-xs text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200">
            Download Template
        </button>
    </div>

    {{-- Members Count --}}
    <div class="mb-4">
        <p class="text-sm text-gray-600">Total Members: <span class="font-semibold">{{ $members->count() }}</span></p>
    </div>

    {{-- Members Table --}}
    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase font-semibold text-gray-600">
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
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-2">{{ $member->studentDetail->student_id ?? 'N/A' }}</td>
                        <td class="px-6 py-2">{{ $member->fullname }}</td>
                        <td class="px-6 py-2">{{ $member->studentDetail->department_name ?? 'N/A' }}</td>
                        <td class="px-6 py-2">{{ $member->studentDetail->year_level ?? 'N/A' }}</td>
                        <td class="px-6 py-2">
                            <button 
                                wire:click='removeMember({{ $member->id }})' 
                                wire:confirm='Are you sure you want to remove this member from the club?' 
                                class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">
                                Remove
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No members yet. Click "Add Member" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add Member Modal --}}
    @if($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Add New Member</h3>
                    <button wire:click="$set('showAddModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="addMember">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Student</label>
                        <select 
                            wire:model="selectedStudent" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Student --</option>
                            @foreach($availableStudents as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->studentDetail->student_id ?? 'N/A' }} - {{ $student->fullname }} 
                                    ({{ $student->studentDetail->department->abbreviation ?? 'N/A' }} - Year {{ $student->studentDetail->year_level ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        @error('selectedStudent') 
                            <span class="text-red-500 text-sm">{{ $message }}</span> 
                        @enderror
                    </div>

                    <div class="flex gap-2 justify-end">
                        <button 
                            type="button"
                            wire:click="$set('showAddModal', false)" 
                            class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 text-sm text-white bg-blue-600 rounded hover:bg-blue-700">
                            Add Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Import Members Modal --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Import Members</h3>
                    <button wire:click="closeImportModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-500 mb-4">
                    Upload a <strong>CSV</strong> or <strong>Excel</strong> file with a single column:
                    <code class="bg-gray-100 px-1 rounded text-xs">student_id</code>.
                    Students not yet in this club will be added automatically.
                </p>

                @if(!$importResults)
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CSV or Excel File</label>
                            <input type="file" wire:model="csvFile" accept=".csv,.txt,.xlsx,.xls"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                            @error('csvFile')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div wire:loading wire:target="csvFile" class="mt-1 text-xs text-gray-400">Uploading...</div>
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end mt-4">
                        <button type="button" wire:click="closeImportModal"
                                class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="button" wire:click="importCsv"
                                wire:loading.attr="disabled" wire:target="importCsv"
                                class="px-4 py-2 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="importCsv">Import</span>
                            <span wire:loading wire:target="importCsv">Importing...</span>
                        </button>
                    </div>
                @else
                    {{-- Results --}}
                    <div class="space-y-3">
                        <div class="flex gap-4">
                            <div class="flex-1 rounded-lg bg-green-50 border border-green-200 p-3 text-center">
                                <p class="text-2xl font-bold text-green-700">{{ $importResults['imported'] }}</p>
                                <p class="text-xs text-green-600">Imported</p>
                            </div>
                            <div class="flex-1 rounded-lg bg-red-50 border border-red-200 p-3 text-center">
                                <p class="text-2xl font-bold text-red-700">{{ $importResults['skipped'] }}</p>
                                <p class="text-xs text-red-600">Skipped</p>
                            </div>
                        </div>

                        @if(!empty($importResults['errors']))
                            <div class="max-h-48 overflow-y-auto rounded-lg bg-red-50 border border-red-200 p-3 space-y-1">
                                <p class="text-xs font-semibold text-red-700 mb-2">Errors / Skipped Rows</p>
                                @foreach($importResults['errors'] as $err)
                                    <p class="text-xs text-red-600">{{ $err }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-2 justify-end mt-4">
                        <button type="button" wire:click="closeImportModal"
                                class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                            Close
                        </button>
                        <button type="button" wire:click="$set('importResults', null)"
                                class="px-4 py-2 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                            Import Another File
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
