<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Student Government Organizations</flux:heading>
        <p class="text-gray-600 mt-2">Manage student government organizations and their officers</p>
    </div>

    <flux:separator class="my-6" />

    @if(session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4">
        <flux:button wire:click="openModal()" variant="primary">Create Student Government</flux:button>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adviser</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Officers</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($studentGovernments as $sg)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $sg->name }}</div>
                            @if($sg->abbreviation)
                                <div class="text-sm text-gray-500">{{ $sg->abbreviation }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $sg->academic_year }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $sg->departmentName }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $sg->adviserName }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $sg->officers->count() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($sg->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('student-government.officers', $sg->id) }}" class="text-blue-600 hover:text-blue-900">Manage Officers</a>
                            <flux:button wire:click="openModal({{ $sg->id }})" size="sm" variant="ghost">Edit</flux:button>
                            <flux:button wire:click="toggleActive({{ $sg->id }})" size="sm" variant="ghost">
                                {{ $sg->is_active ? 'Deactivate' : 'Activate' }}
                            </flux:button>
                            <flux:button wire:click="delete({{ $sg->id }})" wire:confirm="Are you sure you want to delete this student government?" size="sm" variant="danger">Delete</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            No student government organizations found. Click "Create Student Government" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $studentGovernments->links() }}
    </div>

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <flux:heading size="lg" class="mb-4">{{ $editingId ? 'Edit Student Government' : 'Create Student Government' }}</flux:heading>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                            <flux:input wire:model="name" placeholder="e.g., Supreme Student Government" class="w-full" />
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Abbreviation</label>
                            <flux:input wire:model="abbreviation" placeholder="e.g., SSG" class="w-full" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year *</label>
                            <flux:input wire:model="academicYear" placeholder="e.g., 2024-2025" class="w-full" />
                            @error('academicYear') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department (leave empty for university-wide)</label>
                            <select wire:model="departmentId" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">University-wide</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search Adviser</label>
                            <flux:input 
                                wire:model.live.debounce.300ms="staffSearch" 
                                placeholder="Search by name or employee ID..."
                                class="w-full"
                            />
                        </div>

                        @if(count($availableStaff) > 0)
                            <div class="border rounded-lg p-4 max-h-48 overflow-y-auto">
                                <p class="text-sm font-medium text-gray-700 mb-2">Select an adviser:</p>
                                <div class="space-y-2">
                                    @foreach($availableStaff as $staff)
                                        <label class="flex items-start p-3 border rounded-lg hover:bg-gray-50 cursor-pointer {{ $adviserId == $staff->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                            <input 
                                                type="radio" 
                                                wire:model="adviserId" 
                                                value="{{ $staff->id }}"
                                                class="mt-1 mr-3"
                                            >
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900">{{ $staff->first_name }} {{ $staff->last_name }}</div>
                                                <div class="text-sm text-gray-500">{{ $staff->employee_id }}</div>
                                                @if($staff->position)
                                                    <div class="text-sm text-gray-500">{{ $staff->position }}</div>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea wire:model="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Brief description of the organization..."></textarea>
                        </div>

                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                wire:model="isActive" 
                                id="isActive"
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                            >
                            <label for="isActive" class="ml-2 block text-sm text-gray-900">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <flux:button wire:click="showModal = false" variant="ghost">Cancel</flux:button>
                        <flux:button wire:click="save" variant="primary">Save</flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
