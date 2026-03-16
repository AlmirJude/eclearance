<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Requirements - {{ $office->name }}</flux:heading>
            <p class="text-gray-600 mt-2">Define what documents/forms students need to submit for clearance</p>
        </div>
        <div class="flex gap-2">
            <flux:button onclick="history.back()" variant="ghost">← Back</flux:button>
            <flux:button wire:click="openModal()" variant="primary">+ Add Requirement</flux:button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <flux:separator class="my-6" />

    {{-- Requirements List --}}
    @if(count($requirements) === 0)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-4 text-gray-600 dark:text-gray-400">No requirements defined yet.</p>
            <flux:button wire:click="openModal()" variant="primary" class="mt-4 dark:bg-blue-700 dark:hover:bg-blue-800">Add First Requirement</flux:button>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Required</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Scope</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($requirements as $req)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $req['name'] }}</div>
                                @if($req['description'])
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($req['description'], 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $req['type'] === 'document' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300' : '' }}
                                    {{ $req['type'] === 'form' ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300' : '' }}
                                    {{ $req['type'] === 'payment' ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300' : '' }}
                                    {{ $req['type'] === 'other' ? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' : '' }}">
                                    {{ ucfirst($req['type']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($req['is_required'])
                                    <span class="text-red-600 dark:text-red-400 text-sm font-medium">Required</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">Optional</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @php
                                    $yearLevels = $req['year_levels'] ?? [];
                                    $depts = $req['departments'] ?? [];
                                @endphp
                                @if(empty($yearLevels) && empty($depts))
                                    All Students
                                @else
                                    @if(!empty($yearLevels))
                                        Year {{ implode(', ', $yearLevels) }}
                                    @endif
                                    @if(!empty($depts))
                                        <br>{{ count($depts) }} dept(s)
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleActive({{ $req['id'] }})" class="text-sm">
                                    @if($req['is_active'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">Inactive</span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <button wire:click="openModal({{ $req['id'] }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">Edit</button>
                                <button wire:click="delete({{ $req['id'] }})" wire:confirm="Are you sure you want to delete this requirement?" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Add/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $editingId ? 'Edit Requirement' : 'Add Requirement' }}
                    </h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                        <input type="text" wire:model="name" class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400" placeholder="e.g., Library Clearance Form">
                        @error('name') <span class="text-red-500 dark:text-red-400 text-xs">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description" rows="2" class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400" placeholder="Describe what this requirement is for"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select wire:model="type" class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                @foreach($typeOptions as $opt)
                                    <option value="{{ $opt }}" class="dark:bg-gray-700">{{ ucfirst($opt) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center pt-6">
                            <input type="checkbox" wire:model="is_required" id="is_required" 
                                class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400">
                            <label for="is_required" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Required</label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year Levels (leave empty for all)</label>
                        <div class="flex flex-wrap gap-4">
                            @foreach($yearLevelOptions as $year)
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="year_levels" value="{{ $year }}" 
                                        class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Year {{ $year }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departments (leave empty for all)</label>
                        <div class="max-h-32 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded p-2 bg-gray-50 dark:bg-gray-900">
                            @foreach($allDepartments as $dept)
                                <label class="flex items-center py-1">
                                    <input type="checkbox" wire:model="departments" value="{{ $dept->id }}" 
                                        class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $dept->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-end space-x-3 rounded-b-lg">
                    <flux:button wire:click="$set('showModal', false)" variant="ghost" class="dark:text-gray-300 dark:hover:text-white">Cancel</flux:button>
                    <flux:button wire:click="save" variant="primary" class="dark:bg-blue-700 dark:hover:bg-blue-800">{{ $editingId ? 'Update' : 'Add' }}</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
