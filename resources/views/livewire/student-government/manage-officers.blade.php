<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl" class="mb-2">{{ $studentGovernment->name }} - Officers</flux:heading>
        <p class="text-sm text-gray-600">Academic Year: {{ $studentGovernment->academic_year }}</p>
        @if($studentGovernment->department_id)
            <p class="text-sm text-gray-600">Department: {{ $studentGovernment->departmentName }}</p>
        @endif
    </div>

    <flux:separator class="my-6" />

    @if (session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-4">
        <flux:button wire:click="openModal()" variant="primary">Add Officer</flux:button>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year Levels</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Can Sign</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($officers as $officer)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $officer->first_name }} {{ $officer->last_name }}</div>
                            <div class="text-sm text-gray-500">{{ $officer->student_id }}</div>
                            <div class="text-sm text-gray-500">Year {{ $officer->year_level }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $officer->position }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @php
                                    $yearLevels = json_decode($officer->year_levels ?? '[]', true);
                                @endphp
                                @if(empty($yearLevels))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        All Years
                                    </span>
                                @else
                                    @foreach($yearLevels as $level)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Year {{ $level }}
                                        </span>
                                    @endforeach
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($officer->can_sign)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Yes
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    No
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($officer->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <flux:button wire:click="openModal({{ $officer->id }})" size="sm" variant="ghost">Edit</flux:button>
                            <flux:button wire:click="toggleActive({{ $officer->id }})" size="sm" variant="ghost">
                                {{ $officer->is_active ? 'Deactivate' : 'Activate' }}
                            </flux:button>
                            <flux:button wire:click="remove({{ $officer->id }})" wire:confirm="Are you sure you want to remove this officer?" size="sm" variant="danger">Remove</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                            No officers found. Click "Add Officer" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal --}}
    @if($showModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <flux:heading size="lg" class="mb-4">{{ $editingOfficerId ? 'Edit Officer' : 'Add Officer' }}</flux:heading>

                    <div class="space-y-4">
                        {{-- Student Search --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search Student *</label>
                            <input 
                                type="text"
                                wire:model.live.debounce.300ms="studentSearch" 
                                placeholder="Search by student ID or name..."
                                class="w-full border-gray-300 rounded-md shadow-sm"
                            />
                        </div>

                        {{-- Student Selection --}}
                        @if(count($availableStudents) > 0)
                            <div class="border rounded-lg p-4 max-h-60 overflow-y-auto">
                                <p class="text-sm font-medium text-gray-700 mb-2">Select a student:</p>
                                <div class="space-y-2">
                                    @foreach($availableStudents as $student)
                                        <label class="flex items-start p-3 border rounded-lg hover:bg-gray-50 cursor-pointer {{ $selectedStudentId == $student->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                            <input 
                                                type="radio" 
                                                wire:model.live="selectedStudentId" 
                                                value="{{ $student->id }}"
                                                class="mt-1 mr-3"
                                            >
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900">{{ $student->fullname }}</div>
                                                <div class="text-sm text-gray-500">{{ $student->studentDetail->student_id }}</div>
                                                <div class="text-sm text-gray-500">
                                                    Year {{ $student->studentDetail->year_level }}
                                                    @if($student->studentDetail->department)
                                                        • {{ $student->studentDetail->department->name }}
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Position --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Position *</label>
                            <input 
                                type="text"
                                wire:model="position" 
                                placeholder="e.g., President, Vice President, Secretary..."
                                class="w-full border-gray-300 rounded-md shadow-sm"
                            />
                            @error('position') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Can Sign --}}
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                wire:model="canSign" 
                                id="canSign"
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                            >
                            <label for="canSign" class="ml-2 block text-sm text-gray-900">
                                Can sign clearances
                            </label>
                        </div>

                        {{-- Year Levels --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Year Levels (leave empty for all years)</label>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach([1, 2, 3, 4] as $level)
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            wire:model="selectedYearLevels" 
                                            value="{{ $level }}"
                                            class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                                        >
                                        <span class="ml-2 text-sm text-gray-900">Year {{ $level }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        @error('selectedStudentId') 
                            <div class="text-red-500 text-sm">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button wire:click="$set('showModal', false)" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button wire:click="save" type="button" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Save Officer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
