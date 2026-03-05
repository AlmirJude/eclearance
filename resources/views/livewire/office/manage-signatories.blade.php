<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Office Signatories') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ $office->name }} - Manage staff who can sign clearances</flux:subheading>
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
        <a href="{{ route('office.index') }}" class="px-3 py-2 text-xs text-white bg-gray-600 rounded hover:bg-gray-700">
            Back to Offices
        </a>
        <button wire:click="openAddModal" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
            Add Signatory
        </button>
    </div>

    {{-- Signatories Table --}}
    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase font-semibold text-gray-600">
                <tr>
                    <th scope="col" class="px-6 py-3">Staff Member</th>
                    <th scope="col" class="px-6 py-3">Title/Position</th>
                    <th scope="col" class="px-6 py-3">Departments</th>
                    <th scope="col" class="px-6 py-3">Year Levels</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($signatories as $signatory)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-2">
                            {{ $signatory->user->fullname ?? 'N/A' }}
                            <br><span class="text-xs text-gray-500">{{ $signatory->user->staffDetail->employee_id ?? '' }}</span>
                        </td>
                        <td class="px-6 py-2">{{ $signatory->title }}</td>
                        <td class="px-6 py-2">
                            @if($signatory->departments)
                                @foreach($signatory->departments as $deptId)
                                    @php
                                        $dept = $allDepartments->firstWhere('id', $deptId);
                                    @endphp
                                    @if($dept)
                                        <span class="inline-block px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded mr-1 mb-1">{{ $dept->Abbreviation }}</span>
                                    @endif
                                @endforeach
                            @else
                                All
                            @endif
                        </td>
                        <td class="px-6 py-2">
                            @if($signatory->year_levels)
                                @php
                                    $levels = $signatory->year_levels;
                                    sort($levels);
                                @endphp
                                @foreach($levels as $level)
                                    <span class="inline-block px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded mr-1 mb-1">Year {{ $level }}</span>
                                @endforeach
                            @else
                                All
                            @endif
                        </td>
                        <td class="px-6 py-2">
                            @if($signatory->is_active)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Active</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-2 space-x-1">
                            <button 
                                wire:click="openEditModal({{ $signatory->id }})" 
                                class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">
                                Edit
                            </button>
                            <button 
                                wire:click="toggleActive({{ $signatory->id }})" 
                                class="px-3 py-2 text-xs text-white bg-yellow-600 rounded hover:bg-yellow-700">
                                {{ $signatory->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button 
                                wire:click="remove({{ $signatory->id }})" 
                                wire:confirm="Are you sure you want to remove this signatory?" 
                                class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">
                                Remove
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No signatories assigned yet. Click "Add Signatory" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add/Edit Modal --}}
    @if($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">{{ $editingId ? 'Edit' : 'Add' }} Signatory</h3>
                    <button wire:click="$set('showAddModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                    @if(!$editingId)
                        {{-- Staff Member --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Staff Member *</label>
                            
                            {{-- Search Input --}}
                            <div class="relative mb-3">
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.300ms="staffSearch" 
                                    placeholder="Search by name or employee ID..."
                                    class="w-full px-4 py-2.5 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>

                            {{-- Staff List --}}
                            <div class="border border-gray-300 rounded-lg max-h-64 overflow-y-auto bg-gray-50">
                                @forelse($availableStaff as $staff)
                                    <label class="flex items-center gap-3 p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-200 last:border-b-0 transition">
                                        <input 
                                            type="radio" 
                                            wire:model="user_id" 
                                            value="{{ $staff->id }}"
                                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">
                                                {{ $staff->fullname }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Employee ID: {{ $staff->staffDetail->employee_id ?? 'N/A' }}
                                                @if($staff->staffDetail && $staff->staffDetail->position)
                                                    • {{ $staff->staffDetail->position }}
                                                @endif
                                            </p>
                                        </div>
                                    </label>
                                @empty
                                    <div class="p-8 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <p class="text-sm">No staff members found</p>
                                        <p class="text-xs text-gray-400 mt-1">Try adjusting your search</p>
                                    </div>
                                @endforelse
                            </div>
                            @error('user_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    {{-- Title/Position --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title/Position *</label>
                        <input 
                            type="text" 
                            wire:model="title" 
                            placeholder="e.g., SDO Head, Registrar, Librarian"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Departments --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Departments (Optional - leave empty for all departments)</label>
                        <div class="border border-gray-300 rounded-lg max-h-40 overflow-y-auto bg-gray-50 p-2">
                            @foreach($allDepartments as $dept)
                                <label class="flex items-center gap-2 p-2 hover:bg-blue-50 rounded cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        wire:model="departments" 
                                        value="{{ $dept->id }}"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm">{{ $dept->name }} ({{ $dept->Abbreviation }})</span>
                                </label>
                            @endforeach
                        </div>
                        @error('departments') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Year Levels --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year Levels (Optional - leave empty for all years)</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach([1, 2, 3, 4, 5, 6] as $level)
                                <label class="flex items-center gap-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        wire:model="year_levels" 
                                        value="{{ $level }}"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm">Year {{ $level }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('year_levels') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Status --}}
                    <div class="flex items-center gap-2">
                        <input 
                            type="checkbox" 
                            wire:model="is_active" 
                            id="is_active"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex gap-2 justify-end pt-4">
                        <button 
                            type="button"
                            wire:click="$set('showAddModal', false)" 
                            class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 text-sm text-white bg-blue-600 rounded hover:bg-blue-700">
                            {{ $editingId ? 'Update' : 'Add' }} Signatory
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
