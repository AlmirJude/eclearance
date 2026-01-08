<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Students') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage Students') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <a href="{{  route("add.students")  }}" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
        Add Student
    </a>    

        @session('success')
            <div class="mb-4 mt-6 rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3 flex items-center justify-between" role="alert">
                <div class="flex items-center gap-2">
                    <span>{{ session('success')}}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-white-700 hover:text-white-900">&times;</button>
            </div>
        @endsession

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
                        <td class="px-6 py-2">{{$student -> department}}</td>
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
                    <p class="text-sm font-medium">{{ $selectedStudent->department ?? 'N/A' }}</p>
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
