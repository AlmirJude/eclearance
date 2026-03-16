<div>
    <!-- Header Section -->
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Staff') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage Staffs') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('staff.add') }}" class="px-3 py-2 text-xs text-white bg-green-600 dark:bg-green-700 rounded hover:bg-green-700 dark:hover:bg-green-800">
            Add Staff
        </a>
        <button wire:click="openImportModal" class="px-3 py-2 text-xs text-white bg-indigo-600 dark:bg-indigo-700 rounded hover:bg-indigo-700 dark:hover:bg-indigo-800">
            Import Staff
        </button>
        <button wire:click="downloadSampleCsv" class="px-3 py-2 text-xs text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
            Download Template
        </button>
    </div>

    <!-- Success Message -->
    @session('success')
        <div class="mb-4 mt-6 rounded-lg bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 flex items-center justify-between" role="alert">
            <div class="flex items-center gap-2">
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-700 dark:text-green-300 hover:text-green-900 dark:hover:text-green-100">&times;</button>
        </div>
    @endsession

    <!-- Error Message -->
    @session('error')
        <div class="mb-4 mt-6 rounded-lg bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 flex items-center justify-between" role="alert">
            <div class="flex items-center gap-2">
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-700 dark:text-red-300 hover:text-red-900 dark:hover:text-red-100">&times;</button>
        </div>
    @endsession

    <!-- Search Input -->
    <div class="mb-4 mt-4">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by employee ID, name, department, type, role, or email..."
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
        >
    </div>

    <!-- Staff Table -->
    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white dark:bg-gray-800">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700 dark:text-gray-200">
            <thead class="bg-gray-100 dark:bg-gray-900 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">User ID</th>
                    <th scope="col" class="px-6 py-3">Name</th>
                    <th scope="col" class="px-6 py-3">Department</th>
                    <th scope="col" class="px-6 py-3">Employee Type</th>
                    <th scope="col" class="px-6 py-3">Role</th>
                    <th scope="col" class="px-6 py-3 w-80">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($staffs as $staff)
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-2">{{ $staff->employee_id }}</td>
                        <td class="px-6 py-2">{{ $staff->fullname }}</td>
                        <td class="px-6 py-2">{{ $staff->department }}</td>
                        <td class="px-6 py-2 capitalize">{{ $staff->employee_type ?? '—' }}</td>
                        <td class="px-6 py-2 capitalize">{{ $staff->user->role }}</td>

                        <td class="px-6 py-2 space-x-1">
                            <button wire:click='view({{ $staff->id }})' class="px-3 py-2 text-xs text-white bg-green-600 dark:bg-green-700 rounded hover:bg-green-700 dark:hover:bg-green-800">View</button>
                            <a href="{{ route('staff.edit', $staff->user_id) }}" class="px-3 py-2 text-xs text-white bg-blue-600 dark:bg-blue-700 rounded hover:bg-blue-700 dark:hover:bg-blue-800">Edit</a>
                            <button wire:click='confirmDelete({{ $staff->id }})' class="px-3 py-2 text-xs text-white bg-red-600 dark:bg-red-700 rounded hover:bg-red-700 dark:hover:bg-red-800">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div> 

    {{-- Import CSV Modal --}}
    <flux:modal name="import-staff-csv-modal" class="min-w-[32rem] bg-white dark:bg-gray-800" wire:model="showImportModal">
        <div class="space-y-6 text-gray-900 dark:text-gray-100">
            <div>
                <flux:heading size="lg">Import Staff</flux:heading>
                <flux:text class="mt-1 text-gray-500 dark:text-gray-400 text-sm">
                    Upload a <strong>CSV</strong> (.csv) or <strong>Excel</strong> (.xlsx, .xls) file with columns:
                    <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded text-xs">employee_id, email, password, first_name, last_name, department, position</code>.
                    <code>department</code> and <code>position</code> are optional. Role is automatically set to <strong>staff</strong>.
                    Employee type is auto-detected from the ID: <code>HED</code>/<code>BED</code> → teaching, <code>NT</code> → non-teaching.
                </flux:text>
            </div>

            @if(!$importResults)
                {{-- Upload form --}}
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

                <div class="flex gap-2 justify-end">
                    <flux:button variant="ghost" wire:click="closeImportModal">Cancel</flux:button>
                    <flux:button variant="primary" wire:click="importCsv" wire:loading.attr="disabled" wire:target="importCsv">
                        <span wire:loading.remove wire:target="importCsv">Import</span>
                        <span wire:loading wire:target="importCsv">Importing...</span>
                    </flux:button>
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

                <div class="flex gap-2 justify-end">
                    <flux:button variant="ghost" wire:click="closeImportModal">Close</flux:button>
                    <flux:button variant="primary" wire:click="$set('importResults', null)">Import Another File</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

    {{-- Delete Modal --}}
    @if($selectedStaff)
        <flux:modal name="showDeleteModal" class="min-w-[22rem] bg-white dark:bg-gray-800" wire:model="showDeleteModal">
            <div class="space-y-6 text-gray-900 dark:text-gray-100">
                <div>
                    <flux:heading size="lg">Delete Staff?</flux:heading>
                    <flux:text class="mt-2 text-gray-600 dark:text-gray-400">
                        You're about to delete <strong class="text-gray-900 dark:text-white">{{ $selectedStaff->fullname }}</strong> ({{ $selectedStaff->employee_id }}).<br>
                        This action cannot be reversed.
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="ghost" wire:click="closeDeleteModal">Cancel</flux:button>
                    <flux:button wire:click="delete" variant="danger">Delete Staff</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- View Modal --}}
    @if($selectedStaff)
        <flux:modal name="view-staff-modal" variant="flyout" class="md:w-96 space-y-6 bg-white dark:bg-gray-800" wire:model="showViewModal">
            <div class="text-gray-900 dark:text-gray-100">
                <flux:heading size="lg">Staff Details</flux:heading>
                <flux:subheading class="text-gray-600 dark:text-gray-400">View staff information</flux:subheading>
            </div>

            <div class="space-y-4 text-gray-900 dark:text-gray-100">
                <div>
                    <flux:subheading class="text-xs text-gray-500 dark:text-gray-400">Employee ID</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->employee_id }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500 dark:text-gray-400">Full Name</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->fullname }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500 dark:text-gray-400">Email</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->user->email }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500 dark:text-gray-400">Department</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->department ?? 'N/A' }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500 dark:text-gray-400">Position</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->position ?? 'N/A' }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500 dark:text-gray-400">Employee Type</flux:subheading>
                    <p class="text-sm font-medium capitalize">{{ $selectedStaff->employee_type ?? 'N/A' }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500 dark:text-gray-400">Role</flux:subheading>
                    <p class="text-sm font-medium capitalize">{{ $selectedStaff->user->role }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500 dark:text-gray-400">Created At</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button variant="ghost" wire:click="closeModalViewStaff">Close</flux:button>
                <flux:button variant="primary" href="{{ route('staff.edit', $selectedStaff->user_id) }}">Edit</flux:button>
            </div>
        </flux:modal>
    @endif
</div>