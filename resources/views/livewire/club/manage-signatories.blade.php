<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Club Signatories') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ $club->name }} - Manage officers who can sign clearances</flux:subheading>
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
        <a href="{{ route('club.index') }}" class="px-3 py-2 text-xs text-white bg-gray-600 rounded hover:bg-gray-700">
            Back to Clubs
        </a>
        <button wire:click="openAddModal" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
            Add Signatory
        </button>
    </div>

    {{-- Signatories Table --}}
    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white dark:bg-gray-800">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700 dark:text-gray-200">
            <thead class="bg-gray-100 dark:bg-gray-900 text-xs uppercase font-semibold text-gray-600 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Member</th>
                    <th scope="col" class="px-6 py-3">Position</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($signatories as $signatory)
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-2">
                            {{ $signatory->user->fullname ?? 'N/A' }}
                            <br><span class="text-xs text-gray-500 dark:text-gray-400">
                                @if($signatory->user && $signatory->user->role === 'student')
                                    Student ID: {{ $signatory->user->studentDetail->student_id ?? '' }}
                                @else
                                    Employee ID: {{ $signatory->user->staffDetail->employee_id ?? '' }}
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-2">
                            <span class="px-2 py-1 text-xs rounded
                                {{ $signatory->position === 'moderator' ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300' : '' }}
                                {{ $signatory->position === 'president' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' : '' }}
                                {{ $signatory->position === 'treasurer' ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : '' }}">
                                {{ ucfirst($signatory->position) }}
                            </span>
                        </td>
                        <td class="px-6 py-2">
                            @if($signatory->is_active)
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded">Active</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-2 space-x-1">
                            <button 
                                wire:click="openEditModal({{ $signatory->id }})" 
                                class="px-3 py-2 text-xs text-white bg-blue-600 dark:bg-blue-700 rounded hover:bg-blue-700 dark:hover:bg-blue-800">
                                Edit
                            </button>
                            <button 
                                wire:click="toggleActive({{ $signatory->id }})" 
                                class="px-3 py-2 text-xs text-white bg-yellow-600 dark:bg-yellow-700 rounded hover:bg-yellow-700 dark:hover:bg-yellow-800">
                                {{ $signatory->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button 
                                wire:click="remove({{ $signatory->id }})" 
                                wire:confirm="Are you sure you want to remove this signatory?" 
                                class="px-3 py-2 text-xs text-white bg-red-600 dark:bg-red-700 rounded hover:bg-red-700 dark:hover:bg-red-800">
                                Remove
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            No signatories assigned yet. Click "Add Signatory" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add/Edit Modal --}}
    @if($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $editingId ? 'Edit' : 'Add' }} Signatory</h3>
                    <button wire:click="$set('showAddModal', false)" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                    @if(!$editingId)
                        {{-- Member Selection --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Member *</label>
                            
                            {{-- Search Input --}}
                            <div class="relative mb-3">
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.300ms="userSearch" 
                                    placeholder="Search by name or ID..."
                                    class="w-full px-4 py-2.5 pl-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400">
                                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>

                            {{-- User List --}}
                            <div class="border border-gray-300 dark:border-gray-700 rounded-lg max-h-64 overflow-y-auto bg-gray-50 dark:bg-gray-900">
                                @forelse($availableUsers as $user)
                                    <label class="flex items-center gap-3 p-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition">
                                        <input 
                                            type="radio" 
                                            wire:model="user_id" 
                                            value="{{ $user->id }}"
                                            class="w-4 h-4 text-blue-600 dark:text-blue-400 border-gray-300 dark:border-gray-600 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                                {{ $user->fullname }}
                                                <span class="ml-1 px-1.5 py-0.5 text-xs rounded 
                                                    {{ $user->role === 'student' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                                    {{ ucfirst($user->role) }}
                                                </span>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                @if($user->role === 'student')
                                                    Student ID: {{ $user->studentDetail->student_id ?? 'N/A' }}
                                                    @if($user->studentDetail && $user->studentDetail->year_level)
                                                        • Year {{ $user->studentDetail->year_level }}
                                                    @endif
                                                @else
                                                    Employee ID: {{ $user->staffDetail->employee_id ?? 'N/A' }}
                                                    @if($user->staffDetail && $user->staffDetail->position)
                                                        • {{ $user->staffDetail->position }}
                                                    @endif
                                                @endif
                                            </p>
                                        </div>
                                    </label>
                                @empty
                                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <p class="text-sm">No members found</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Try adjusting your search</p>
                                    </div>
                                @endforelse
                            </div>
                            @error('user_id') <span class="text-red-500 dark:text-red-400 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    {{-- Position --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Position *</label>
                        <select 
                            wire:model="position"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            @foreach($positionOptions as $opt)
                                <option value="{{ $opt }}" class="dark:bg-gray-700">{{ ucfirst($opt) }}</option>
                            @endforeach
                        </select>
                        @error('position') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Status --}}
                    <div class="flex items-center gap-2">
                        <input 
                            type="checkbox" 
                            wire:model="is_active" 
                            id="is_active"
                            class="w-4 h-4 text-blue-600 dark:text-blue-400 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700">
                        <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex gap-2 justify-end pt-4">
                        <button 
                            type="button"
                            wire:click="$set('showAddModal', false)" 
                            class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 text-sm text-white bg-blue-600 dark:bg-blue-700 rounded hover:bg-blue-700 dark:hover:bg-blue-800">
                            {{ $editingId ? 'Update' : 'Add' }} Signatory
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
    