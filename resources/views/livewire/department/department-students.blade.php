<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Students') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage Students') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>
        @session('success')
            <div class="mb-4 mt-6 rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3 flex items-center justify-between" role="alert">
                <div class="flex items-center gap-2">
                    <span>{{ session('success')}}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-white-700 hover:text-white-900">&times;</button>
            </div>
        @endsession

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.300ms="searchQuery"
                   placeholder="Search name or student ID..."
                   class="pl-8 pr-3 py-1.5 text-sm rounded-lg border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-64" />
            <svg class="absolute left-2.5 top-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        <select wire:model.live="yearLevelFilter"
                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            <option value="">All Year Levels</option>
            @foreach($availableYearLevels as $year)
                <option value="{{ $year }}">Year {{ $year }}</option>
            @endforeach
        </select>

        @if($searchQuery || $yearLevelFilter !== '')
            <button wire:click="clearFilters" class="text-sm text-blue-600 hover:text-blue-800 underline">
                Clear Filters
            </button>
        @endif
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
                @forelse ($students as $student)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-2">{{$student -> student_id}}</td>
                        <td class="px-6 py-2">{{$student -> fullname}}</td>
                        <td class="px-6 py-2">{{$student -> department_name ?? 'N/A'}}</td>
                        <td class="px-6 py-2">{{$student -> year_level}}</td>

                        <td class="px-6 py-2 ">
                            @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                                <button wire:click='confirmDelete({{$student->id}})' class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">Delete</button>
                                <a href="{{route("edit.students", $student->user_id)}}" class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">Edit</a>
                            @endif  
                        <button wire:click='view({{$student->id}})' class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">View</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                            No students found for the selected filters.
                        </td>
                    </tr>
                @endforelse
                </tbody>
        </table>
    </div>

    {{-- View Modal --}}
    @if($selectedStudent)
        <flux:modal name="view-student-modal" variant="flyout" class="md:w-96 space-y-6" wire:model="showViewModal">
            <div>
                <flux:heading size="lg">Student Details</flux:heading>
                <flux:subheading>View student information</flux:subheading>
            </div>

            <div class="space-y-4">
                <div>
                    <flux:subheading class="text-xs text-gray-500">Student ID</flux:subheading>
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
                <flux:button variant="ghost" wire:click="closeModalViewStudent">Close</flux:button>
                <flux:button variant="primary" href="{{ route('edit.students', $selectedStudent->user_id) }}">Edit</flux:button>
            </div>
        </flux:modal>
    @endif
</div>
