<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Homeroom Adviser Assignments') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage homeroom advisers for each department, year level, and section') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="p-4 mb-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-700">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex gap-2 mb-4">
        <button wire:click="openAddModal" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
            Add Homeroom Assignment
        </button>
    </div>

    {{-- Assignments Table --}}
    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase font-semibold text-gray-600">
                <tr>
                    <th scope="col" class="px-6 py-3">Adviser</th>
                    <th scope="col" class="px-6 py-3">Department</th>
                    <th scope="col" class="px-6 py-3">Year Level</th>
                    <th scope="col" class="px-6 py-3">Section</th>
                    <th scope="col" class="px-6 py-3">Academic Year</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assignments as $assignment)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-2">
                            {{ $assignment->adviser->fullname ?? 'N/A' }}
                            <br><span class="text-xs text-gray-500">{{ $assignment->adviser->staffDetail->employee_id ?? '' }}</span>
                        </td>
                        <td class="px-6 py-2">{{ $assignment->department->name ?? 'N/A' }}</td>
                        <td class="px-6 py-2">
                            @if($assignment->year_levels)
                                @php
                                    $levels = $assignment->year_levels;
                                    sort($levels);
                                @endphp
                                @foreach($levels as $level)
                                    <span class="inline-block px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded mr-1 mb-1">Year {{ $level }}</span>
                                @endforeach
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="px-6 py-2">{{ $assignment->section ?: 'All' }}</td>
                        <td class="px-6 py-2">{{ $assignment->academic_year }}</td>
                        <td class="px-6 py-2">
                            @if($assignment->is_active)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Active</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-2 space-x-1">
                            <button 
                                wire:click="openEditModal({{ $assignment->id }})" 
                                class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">
                                Edit
                            </button>
                            <button 
                                wire:click="toggleActive({{ $assignment->id }})" 
                                class="px-3 py-2 text-xs text-white bg-yellow-600 rounded hover:bg-yellow-700">
                                {{ $assignment->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button 
                                wire:click="confirmDelete({{ $assignment->id }})" 
                                class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No homeroom assignments yet. Click "Add Homeroom Assignment" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Add / Edit Homeroom Modal ─────────────────────────────────────────── --}}
    <flux:modal name="homeroom-modal" class="min-w-[38rem]" wire:model="showAddModal">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit' : 'Add' }} Homeroom Assignment</flux:heading>
                <flux:subheading>{{ $editingId ? 'Update the assignment details.' : 'Fill in the details for the new homeroom assignment.' }}</flux:subheading>
            </div>

            {{-- Adviser searchable picker --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Homeroom Adviser *</label>
                @if($adviser_id)
                    <div class="flex items-center justify-between rounded-lg border border-green-300 bg-green-50 px-3 py-2">
                        <span class="text-sm font-medium text-green-800">{{ $adviserSearch }}</span>
                        <button wire:click="clearAdviser" type="button" class="text-xs text-red-500 hover:text-red-700 ml-3">Remove</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="adviserSearch"
                        placeholder="Search by name or ID..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />

                    @if(strlen(trim($adviserSearch)) >= 1 && $adviserResults->isEmpty())
                        <p class="mt-1 text-xs text-gray-400">No staff/admin found matching "{{ $adviserSearch }}".</p>
                    @endif

                    @if($adviserResults->isNotEmpty())
                        <ul class="mt-1 max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow divide-y divide-gray-100">
                            @foreach($adviserResults as $user)
                                @php
                                    $label = $user->staffDetail
                                        ? ($user->staffDetail->fullname . ' (' . $user->user_id . ')')
                                        : ($user->email . ' (' . $user->user_id . ')');
                                @endphp
                                <li>
                                    <button type="button"
                                        wire:click="selectAdviser({{ $user->id }}, '{{ addslashes($label) }}')"
                                        class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 transition">
                                        <span class="font-medium text-gray-800">{{ $label }}</span>
                                        <span class="ml-2 text-xs text-gray-400 capitalize">{{ $user->role }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
                @error('adviser_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Department --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                <select wire:model="department_id"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="">-- Select Department --</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Year Levels --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year Levels * (select one or more)</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach([1, 2, 3, 4, 5, 6] as $level)
                        <label class="flex items-center gap-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" wire:model="year_levels" value="{{ $level }}"
                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <span class="text-sm">Year {{ $level }}</span>
                        </label>
                    @endforeach
                </div>
                @error('year_levels') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Section
            <div>
                <flux:input wire:model="section" label="Section (Optional)" placeholder="e.g. A, B (leave blank for all sections)" />
                @error('section') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div> --}}

            {{-- Academic Year --}}
            <div>
                <flux:input wire:model="academic_year" label="Academic Year *" placeholder="e.g. 2025-2026" />
                @error('academic_year') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Status --}}
            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" id="is_active_modal" class="rounded" />
                <label for="is_active_modal" class="text-sm text-gray-700">Active</label>
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <flux:button variant="ghost" wire:click="$set('showAddModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save"
                    wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }} Assignment</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ── Delete Confirmation Modal ────────────────────────────────────────── --}}
    @if($assignmentToDelete)
        <flux:modal name="delete-homeroom-modal" class="min-w-[22rem]" wire:model="showDeleteModal">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Assignment?</flux:heading>
                    <flux:text class="mt-2">
                        You're about to delete the homeroom assignment for
                        <strong>{{ $assignmentToDelete->adviser->staffDetail->fullname ?? $assignmentToDelete->adviser->email ?? 'this adviser' }}</strong>
                        in <strong>{{ $assignmentToDelete->department->name ?? 'N/A' }}</strong>.<br>
                        This action cannot be reversed.
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="ghost" wire:click="closeDeleteModal">Cancel</flux:button>
                    <flux:button variant="danger" wire:click="delete">Delete Assignment</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
