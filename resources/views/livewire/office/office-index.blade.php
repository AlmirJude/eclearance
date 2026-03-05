<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Offices') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage all your Offices') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <button wire:click="openAddModal" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
        Add Office
    </button>

    @if(session('success'))
        <div class="mb-4 mt-4 rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3 flex items-center justify-between">
            <span>{{ session('success') }}</span>
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
                    <th scope="col" class="px-6 py-3">Office Name</th>
                    <th scope="col" class="px-6 py-3">Manager</th>
                    <th scope="col" class="px-6 py-3">Required</th>
                    <th scope="col" class="px-6 py-3 w-96">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($offices as $office)
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="px-6 py-2">{{ $office->name }}</td>
                    <td class="px-6 py-2">{{ $office->manager_name }}</td>
                    <td class="px-6 py-2">
                        @if($office->is_required)
                            <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Yes</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-500">No</span>
                        @endif
                    </td>
                    <td class="px-6 py-2">
                        <button wire:click="openShowModal({{ $office->id }})" class="px-3 py-2 text-xs text-white bg-gray-600 rounded hover:bg-gray-700">Show</button>
                        <button wire:click="openEditModal({{ $office->id }})" class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">Edit</button>
                        <a href="{{ route('office.signatories', $office->id) }}" class="px-3 py-2 text-xs text-white bg-purple-600 rounded hover:bg-purple-700">Signatories</a>
                        <a href="{{ route('office.requirements', $office->id) }}" class="px-3 py-2 text-xs text-white bg-teal-600 rounded hover:bg-teal-700">Requirements</a>
                        <button wire:click="confirmDelete({{ $office->id }})" class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">Delete</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Add Office Modal ─────────────────────────────────────────────────── --}}
    <flux:modal name="add-office-modal" class="min-w-[36rem]" wire:model="showAddModal">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Add Office</flux:heading>
                <flux:subheading>Fill in the details for the new office.</flux:subheading>
            </div>

            <div>
                <flux:input wire:model.live="name" label="Office Name" placeholder="e.g. Registrar's Office" />
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>


            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model.live="is_required" id="is_required_add" class="rounded" />
                <label for="is_required_add" class="text-sm text-gray-700">Required for clearance</label>
            </div>

            {{-- Manager search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Head / Manager</label>
                @if($manager_id)
                    <div class="flex items-center justify-between rounded-lg border border-green-300 bg-green-50 px-3 py-2">
                        <span class="text-sm font-medium text-green-800">{{ $managerSearch }}</span>
                        <button wire:click="clearManager" class="text-xs text-red-500 hover:text-red-700 ml-3">Remove</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="managerSearch"
                        placeholder="Search by name or ID..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />

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
                                    <button type="button"
                                        wire:click="selectManager({{ $user->id }}, '{{ addslashes($label) }}')"
                                        class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 transition">
                                        <span class="font-medium text-gray-800">{{ $label }}</span>
                                        <span class="ml-2 text-xs text-gray-400 capitalize">{{ $user->role }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <flux:button variant="ghost" wire:click="closeAddModal">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveNewOffice"
                    wire:loading.attr="disabled" wire:target="saveNewOffice">
                    <span wire:loading.remove wire:target="saveNewOffice">Save Office</span>
                    <span wire:loading wire:target="saveNewOffice">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ── Show (View) Office Modal ─────────────────────────────────────────── --}}
    @if($selectedOffice)
        <flux:modal name="view-office-modal" variant="flyout" class="md:w-96 space-y-6" wire:model="showViewModal">
            <div>
                <flux:heading size="lg">Office Details</flux:heading>
                <flux:subheading>View office information</flux:subheading>
            </div>

            <div class="space-y-4">
                <div>
                    <flux:subheading class="text-xs text-gray-500">Office Name</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedOffice->name }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Head / Manager</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedOffice->manager_name }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Required for Clearance</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedOffice->is_required ? 'Yes' : 'No' }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Created At</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedOffice->created_at->format('M d, Y h:i A') }}</p>
                </div>

                <div>
                    <flux:subheading class="text-xs text-gray-500">Last Updated</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedOffice->updated_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button variant="ghost" wire:click="closeShowModal">Close</flux:button>
                <flux:button variant="primary" wire:click="openEditModal({{ $selectedOffice->id }}); closeShowModal()">Edit</flux:button>
            </div>
        </flux:modal>
    @endif

    {{-- ── Edit Office Modal ───────────────────────────────────────────────── --}}
    <flux:modal name="edit-office-modal" class="min-w-[36rem]" wire:model="showEditModal">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Edit Office</flux:heading>
                <flux:subheading>Update the office details.</flux:subheading>
            </div>

            <div>
                <flux:input wire:model.live="editName" label="Office Name" placeholder="e.g. Registrar's Office" />
                @error('editName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model.live="editIsRequired" id="is_required_edit" class="rounded" />
                <label for="is_required_edit" class="text-sm text-gray-700">Required for clearance</label>
            </div>

            {{-- Edit manager search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Head / Manager</label>
                @if($editManagerId)
                    <div class="flex items-center justify-between rounded-lg border border-green-300 bg-green-50 px-3 py-2">
                        <span class="text-sm font-medium text-green-800">{{ $editManagerSearch }}</span>
                        <button wire:click="clearEditManager" class="text-xs text-red-500 hover:text-red-700 ml-3">Remove</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="editManagerSearch"
                        placeholder="Search by name or ID..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />

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
                                    <button type="button"
                                        wire:click="selectEditManager({{ $user->id }}, '{{ addslashes($label) }}')"
                                        class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 transition">
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
                <flux:button variant="primary" wire:click="saveEditOffice"
                    wire:loading.attr="disabled" wire:target="saveEditOffice">
                    <span wire:loading.remove wire:target="saveEditOffice">Save Changes</span>
                    <span wire:loading wire:target="saveEditOffice">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ── Delete Office Modal ─────────────────────────────────────────────── --}}
    @if($officeToDelete)
        <flux:modal name="delete-office-modal" class="min-w-[22rem]" wire:model="showDeleteModal">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Office?</flux:heading>
                    <flux:text class="mt-2">
                        You're about to delete <strong>{{ $officeToDelete->name }}</strong>.<br>
                        This action cannot be reversed.
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="ghost" wire:click="closeDeleteModal">Cancel</flux:button>
                    <flux:button wire:click="delete" variant="danger">Delete Office</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
