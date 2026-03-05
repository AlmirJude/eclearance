<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Departments') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage all your departments') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <button wire:click="openAddModal" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
        Add Department
    </button>

    @if(session('message'))
        <div class="mb-4 mt-4 rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3 flex items-center justify-between">
            <span>{{ session('message') }}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 mt-4 rounded-lg bg-red-100 border border-red-300 text-red-800 px-4 py-3 flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        </div>
    @endif

    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase font-semibold text-gray-600">
                <tr>
                    <th scope="col" class="px-6 py-3">Department Name</th>
                    <th scope="col" class="px-6 py-3">Abbreviation</th>
                    <th scope="col" class="px-6 py-3">Dean / Program Head</th>
                    <th scope="col" class="px-6 py-3 w-80">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($departments as $department)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-2">{{$department->name}}</td>
                        <td class="px-6 py-2">{{$department->Abbreviation}}</td>
                        <td class="px-6 py-2">{{$department->manager_name}}</td>
                        <td class="px-6 py-2">
                            <button wire:click="openShowModal({{ $department->id }})" class="px-3 py-2 text-xs text-white bg-gray-600 rounded hover:bg-gray-700">Show</button>
                            <button wire:click="openEditModal({{ $department->id }})" class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">Edit</button>
                            <a href="{{route('department.signatories', $department->id)}}" class="px-3 py-2 text-xs text-white bg-purple-600 rounded hover:bg-purple-700">Signatories</a>
                            <button wire:click="confirmDelete({{ $department->id }})" class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Add Department Modal --}}
    <flux:modal name="add-department-modal" class="min-w-[36rem]" wire:model="showAddModal">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Add Department</flux:heading>
                <flux:subheading>Fill in the details for the new department.</flux:subheading>
            </div>

            {{-- Department Name --}}
            <div>
                <flux:input wire:model.live="name" label="Department Name" placeholder="e.g. Computer Science" />
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Abbreviation --}}
            <div>
                <flux:input wire:model.live="abbreviation" label="Abbreviation" placeholder="e.g. BSCS" />
                @error('abbreviation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <flux:input wire:model.live="description" label="Description" placeholder="e.g. Bachelor of Science in Computer Science" />
            </div>

            {{-- Dean / Program Head search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dean / Program Head</label>

                @if($manager_id)
                    {{-- Selected state --}}
                    <div class="flex items-center justify-between rounded-lg border border-green-300 bg-green-50 px-3 py-2">
                        <span class="text-sm font-medium text-green-800">{{ $managerSearch }}</span>
                        <button wire:click="clearManager" class="text-xs text-red-500 hover:text-red-700 ml-3">Remove</button>
                    </div>
                @else
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="managerSearch"
                        placeholder="Search by name or ID..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    />

                    @if($managerSearch !== '' && $managerResults->isEmpty())
                        <p class="mt-1 text-xs text-gray-400">No staff/admin found matching "{{ $managerSearch }}".</p>
                    @endif

                    @if($managerResults->isNotEmpty())
                        <ul class="mt-1 max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow divide-y divide-gray-100">
                            @foreach($managerResults as $user)
                                @php
                                    $label = $user->staffDetail
                                        ? $user->staffDetail->fullname . ' (' . $user->user_id . ')'
                                        : $user->email . ' (' . $user->user_id . ')';
                                @endphp
                                <li>
                                    <button
                                        type="button"
                                        wire:click="selectManager({{ $user->id }}, '{{ addslashes($label) }}')"
                                        class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 transition"
                                    >
                                        <span class="font-medium text-gray-800">{{ $label }}</span>
                                        <span class="ml-2 text-xs text-gray-400 capitalize">{{ $user->role }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
                @error('manager_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <flux:button variant="ghost" wire:click="closeAddModal">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveNewDepartment"
                    wire:loading.attr="disabled" wire:target="saveNewDepartment">
                    <span wire:loading.remove wire:target="saveNewDepartment">Save Department</span>
                    <span wire:loading wire:target="saveNewDepartment">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Department Modal --}}
    @if($selectedDepartmentToDelete)
        <flux:modal name="delete-department-modal" class="min-w-[22rem]" wire:model="showDeleteModal">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Department?</flux:heading>
                    <flux:text class="mt-2">
                        You're about to delete <strong>{{ $selectedDepartmentToDelete->name }}</strong> ({{ $selectedDepartmentToDelete->Abbreviation }}).<br>
                        This action cannot be reversed.
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="ghost" wire:click="closeDeleteModal">Cancel</flux:button>
                    <flux:button wire:click="delete" variant="danger">Delete Department</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Show (View) Department Modal --}}
    @if($selectedDepartment)
        <flux:modal name="view-department-modal" variant="flyout" class="md:w-96 space-y-6" wire:model="showViewModal">
            <div>
                <flux:heading size="lg">Department Details</flux:heading>
                <flux:subheading>View department information</flux:subheading>
            </div>

            <div class="space-y-4">
                <div>
                    <flux:subheading class="text-xs text-gray-500">Department Name</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedDepartment->name }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Abbreviation</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedDepartment->Abbreviation ?? 'N/A' }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Description</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedDepartment->description ?: 'N/A' }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Dean / Program Head</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedDepartment->manager_name }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Created At</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedDepartment->created_at->format('M d, Y h:i A') }}</p>
                </div>

                <div>
                    <flux:subheading class="text-xs text-gray-500">Last Updated</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedDepartment->updated_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button variant="ghost" wire:click="closeShowModal">Close</flux:button>
                <flux:button variant="primary" wire:click="openEditModal({{ $selectedDepartment->id }}); closeShowModal()">Edit</flux:button>
            </div>
        </flux:modal>
    @endif

    {{-- Edit Department Modal --}}
    <flux:modal name="edit-department-modal" class="min-w-[36rem]" wire:model="showEditModal">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Edit Department</flux:heading>
                <flux:subheading>Update the department details.</flux:subheading>
            </div>

            {{-- Department Name --}}
            <div>
                <flux:input wire:model.live="editName" label="Department Name" placeholder="e.g. Computer Science" />
                @error('editName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Abbreviation --}}
            <div>
                <flux:input wire:model.live="editAbbreviation" label="Abbreviation" placeholder="e.g. BSCS" />
                @error('editAbbreviation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <flux:input wire:model.live="editDescription" label="Description" placeholder="e.g. Bachelor of Science in Computer Science" />
            </div>

            {{-- Dean / Program Head search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dean / Program Head</label>

                @if($editManagerId)
                    <div class="flex items-center justify-between rounded-lg border border-green-300 bg-green-50 px-3 py-2">
                        <span class="text-sm font-medium text-green-800">{{ $editManagerSearch }}</span>
                        <button wire:click="clearEditManager" class="text-xs text-red-500 hover:text-red-700 ml-3">Remove</button>
                    </div>
                @else
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="editManagerSearch"
                        placeholder="Search by name or ID..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    />

                    @if($editManagerSearch !== '' && $editManagerResults->isEmpty())
                        <p class="mt-1 text-xs text-gray-400">No staff/admin found matching "{{ $editManagerSearch }}".</p>
                    @endif

                    @if($editManagerResults->isNotEmpty())
                        <ul class="mt-1 max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow divide-y divide-gray-100">
                            @foreach($editManagerResults as $user)
                                @php
                                    $label = $user->staffDetail
                                        ? $user->staffDetail->fullname . ' (' . $user->user_id . ')'
                                        : $user->email . ' (' . $user->user_id . ')';
                                @endphp
                                <li>
                                    <button
                                        type="button"
                                        wire:click="selectEditManager({{ $user->id }}, '{{ addslashes($label) }}')"
                                        class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 transition"
                                    >
                                        <span class="font-medium text-gray-800">{{ $label }}</span>
                                        <span class="ml-2 text-xs text-gray-400 capitalize">{{ $user->role }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
                @error('editManagerId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <flux:button variant="ghost" wire:click="closeEditModal">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveEditDepartment"
                    wire:loading.attr="disabled" wire:target="saveEditDepartment">
                    <span wire:loading.remove wire:target="saveEditDepartment">Save Changes</span>
                    <span wire:loading wire:target="saveEditDepartment">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

</div>