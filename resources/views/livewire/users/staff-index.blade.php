<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Staff') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage Staffs') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <a href="{{ route('staff.add') }}" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
        Add Staff
    </a>  

    @session('success')
        <div class="mb-4 mt-6 rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3 flex items-center justify-between" role="alert">
            <div class="flex items-center gap-2">
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-white-700 hover:text-white-900">&times;</button>
        </div>
    @endsession

    @session('error')
        <div class="mb-4 mt-6 rounded-lg bg-red-100 border border-red-300 text-red-800 px-4 py-3 flex items-center justify-between" role="alert">
            <div class="flex items-center gap-2">
                <span>{{ session('error') }}</span>
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
                    <th scope="col" class="px-6 py-3">Role</th>
                    <th scope="col" class="px-6 py-3 w-80">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($staffs as $staff)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-2">{{ $staff->employee_id }}</td>
                        <td class="px-6 py-2">{{ $staff->fullname }}</td>
                        <td class="px-6 py-2">{{ $staff->department }}</td>
                        <td class="px-6 py-2">{{ $staff->user->role }}</td>

                        <td class="px-6 py-2">
                            <button wire:click='view({{ $staff->id }})' class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">View</button>
                            <a href="{{ route('staff.edit', $staff->user_id) }}" class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">Edit</a>
                            <button wire:click='confirmDelete({{ $staff->id }})' class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div> 

    {{-- Delete Modal --}}
    @if($selectedStaff)
        <flux:modal name="showDeleteModal" class="min-w-[22rem]" wire:model="showDeleteModal">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Staff?</flux:heading>
                    <flux:text class="mt-2">
                        You're about to delete <strong>{{ $selectedStaff->fullname }}</strong> ({{ $selectedStaff->employee_id }}).<br>
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
        <flux:modal name="view-staff-modal" variant="flyout" class="md:w-96 space-y-6" wire:model="showViewModal">
            <div>
                <flux:heading size="lg">Staff Details</flux:heading>
                <flux:subheading>View staff information</flux:subheading>
            </div>

            <div class="space-y-4">
                <div>
                    <flux:subheading class="text-xs text-gray-500">Employee ID</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->employee_id }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Full Name</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->fullname }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Email</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->user->email }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Department</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->department ?? 'N/A' }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Position</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedStaff->position ?? 'N/A' }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Role</flux:subheading>
                    <p class="text-sm font-medium capitalize">{{ $selectedStaff->user->role }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Created At</flux:subheading>
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