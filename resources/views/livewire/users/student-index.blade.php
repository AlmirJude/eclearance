<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Students') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage Students') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="flex gap-2 flex-wrap">
        <a href="{{  route('add.students')  }}" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
            Add Student
        </a>
        <button wire:click="openImportModal" class="px-3 py-2 text-xs text-white bg-indigo-600 rounded hover:bg-indigo-700">
            Import Students
        </button>
        <button wire:click="downloadSampleCsv" class="px-3 py-2 text-xs text-gray-700 bg-gray-100 border border-gray-300 rounded hover:bg-gray-200">
            Download Template
        </button>
    </div>

        @session('success')
            <div class="mb-4 mt-6 rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3 flex items-center justify-between" role="alert">
                <div class="flex items-center gap-2">
                    <span>{{ session('success')}}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-white-700 hover:text-white-900">&times;</button>
            </div>
        @endsession

    <div class="mb-4 mt-4">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by student ID, name, department, year level, or email..."
            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-2">
        <select
            wire:model.live="departmentFilter"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >
            <option value="">All Departments</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }}</option>
            @endforeach
        </select>

        <select
            wire:model.live="yearLevelFilter"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >
            <option value="">All Year Levels</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
            <option value="5">5th Year</option>
            <option value="6">6th Year</option>
        </select>
    </div>

    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase font-semibold text-gray-600">
                <tr>
                    <th scope="col" class="px-6 py-3">User ID</th>
                    <th scope="col" class="px-6 py-3">Name</th>
                    <th scope="col" class="px-6 py-3">Department</th>
                    <th scope="col" class="px-6 py-3">Year Level</th>
                    <th scope="col" class="px-6 py-3 w-80">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($students as $student)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-2">{{$student -> student_id}}</td>
                        <td class="px-6 py-2">{{$student -> fullname}}</td>
                        <td class="px-6 py-2">{{$student -> department_name}}</td>
                        <td class="px-6 py-2">{{$student -> year_level}}</td>

                        <td class="px-6 py-2 ">
                        <button wire:click='view({{$student->id}})' class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">View</button>
                        <a href="{{route("edit.students", $student->user_id)}}" class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">Edit</a>
                        <button wire:click='confirmDelete({{$student->id}})' class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">Delete</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
        </table>
    </div>

    {{-- Import CSV Modal --}}
    <flux:modal name="import-csv-modal" class="min-w-[32rem]" wire:model="showImportModal">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Import Students</flux:heading>
                <flux:text class="mt-1 text-gray-500 text-sm">
                    Upload a <strong>CSV</strong> (.csv) or <strong>Excel</strong> (.xlsx, .xls) file with columns:
                    <code class="bg-gray-100 px-1 rounded text-xs">student_id, email, password, first_name, last_name, department, year_level</code>.
                    The <strong>department</strong> column accepts the abbreviation (e.g. BSCS) or full name.
                </flux:text>
            </div>

            @if(!$importResults)
                {{-- Upload form --}}
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

                <div class="flex gap-2 justify-end">
                    <flux:button variant="ghost" wire:click="closeImportModal">Close</flux:button>
                    <flux:button variant="primary" wire:click="$set('importResults', null)">Import Another File</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

    {{-- Delete Modal --}}
    @if($selectedStudent)
        <flux:modal name="showDeleteModal" class="min-w-[22rem]" wire:model="showDeleteModal">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Staff?</flux:heading>
                    <flux:text class="mt-2">
                        You're about to delete <strong>{{ $selectedStudent->fullname }}</strong> ({{ $selectedStudent->student_id }}).<br>
                        This action cannot be reversed.
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="ghost" wire:click="closeDeleteModal">Cancel</flux:button>
                    <flux:button wire:click="delete" variant="danger">Delete Student</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif



    {{-- View Modal --}}
    @if($selectedStudent)
        <flux:modal name="view-student-modal" variant="flyout" class="md:w-96 space-y-6" wire:model="showViewModal">
            <div>
                <flux:heading size="lg">Staff Details</flux:heading>
                <flux:subheading>View staff information</flux:subheading>
            </div>

            <div class="space-y-4">
                <div>
                    <flux:subheading class="text-xs text-gray-500">Employee ID</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStudent->student_id }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Full Name</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStudent->fullname }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Email</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStudent->user->email }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Department</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStudent->department_name ?? 'N/A' }}</p>
                </div>

                <div>
                    <flux:subheading class="text-xs text-gray-500">Year Level</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStudent->year_level ?? 'N/A' }}</p>
                </div>




                <flux:separator variant="subtle" />
                <div>
                    <flux:subheading class="text-xs text-gray-500">Created At</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStudent->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button variant="ghost" wire:click="closeModalViewStaff">Close</flux:button>
                <flux:button variant="primary" href="{{ route('edit.students', $selectedStudent->user_id) }}">Edit</flux:button>
            </div>
        </flux:modal>
    @endif
</div>
