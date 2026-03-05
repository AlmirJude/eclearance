<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">Submit Requirements - {{ $entityName }}</flux:heading>
            <p class="text-gray-600 mt-2">Upload required documents for {{ $entityType }} clearance</p>
        </div>
        <flux:button href="{{ route('clearance.student') }}" variant="ghost">← Back to My Clearance</flux:button>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <flux:separator class="my-6" />

    {{-- Requirements List --}}
    @if(count($requirements) === 0)
        <div class="bg-green-50 rounded-lg p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-4 text-green-700 font-medium">No requirements needed!</p>
            <p class="text-green-600 text-sm">This {{ strtolower($entityType) }} has no document requirements for you.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($requirements as $requirement)
                @php
                    $status = $this->getSubmissionStatus($requirement->id);
                    $submission = $submissions[$requirement->id] ?? null;
                @endphp
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h4 class="text-lg font-medium text-gray-900">{{ $requirement->name }}</h4>
                                @if($requirement->is_required)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Required</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Optional</span>
                                @endif
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                    {{ $requirement->type === 'document' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $requirement->type === 'form' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $requirement->type === 'payment' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $requirement->type === 'other' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ ucfirst($requirement->type) }}
                                </span>
                            </div>
                            @if($requirement->description)
                                <p class="mt-2 text-sm text-gray-600">{{ $requirement->description }}</p>
                            @endif

                            {{-- Submission Status --}}
                            @if($status !== 'not_submitted')
                                <div class="mt-3 p-3 rounded-lg 
                                    {{ $status === 'approved' ? 'bg-green-50' : '' }}
                                    {{ $status === 'pending' ? 'bg-yellow-50' : '' }}
                                    {{ $status === 'rejected' ? 'bg-red-50' : '' }}">
                                    <div class="flex items-center gap-2">
                                        @if($status === 'approved')
                                            <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-sm font-medium text-green-700">Approved</span>
                                        @elseif($status === 'pending')
                                            <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-sm font-medium text-yellow-700">Pending Review</span>
                                        @elseif($status === 'rejected')
                                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            <span class="text-sm font-medium text-red-700">Rejected</span>
                                        @endif
                                    </div>
                                    @if($submission && $submission['file_path'])
                                        <a href="{{ Storage::url($submission['file_path']) }}" target="_blank" class="mt-2 text-sm text-blue-600 hover:underline inline-flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            View Submitted File
                                        </a>
                                    @endif
                                    @if($status === 'rejected' && $submission && $submission['review_remarks'])
                                        <p class="mt-2 text-sm text-red-600">
                                            <strong>Reason:</strong> {{ $submission['review_remarks'] }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="ml-4">
                            @if($status === 'not_submitted' || $status === 'rejected')
                                <flux:button wire:click="openUploadModal({{ $requirement->id }})" variant="primary" size="sm">
                                    {{ $status === 'rejected' ? 'Resubmit' : 'Submit' }}
                                </flux:button>
                            @elseif($status === 'pending')
                                <flux:button wire:click="openUploadModal({{ $requirement->id }})" variant="ghost" size="sm">
                                    Update
                                </flux:button>
                            @else
                                <span class="text-green-600 text-sm">✓ Complete</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Upload Modal --}}
    @if($showModal && $selectedRequirement)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Submit: {{ $selectedRequirement->name }}
                    </h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    @if($selectedRequirement->description)
                        <div class="bg-blue-50 rounded p-3 text-sm text-blue-700">
                            {{ $selectedRequirement->description }}
                        </div>
                    @endif

                    @if(in_array($selectedRequirement->type, ['document', 'form']))
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload File *</label>
                            <input type="file" wire:model="uploadFile" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
                            <p class="mt-1 text-xs text-gray-500">Accepted: PDF, JPG, PNG, DOC, DOCX (max 10MB)</p>
                            @error('uploadFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            
                            <div wire:loading wire:target="uploadFile" class="mt-2 text-sm text-blue-600">
                                Uploading...
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea wire:model="notes" rows="3" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2" placeholder="Add any additional notes or comments..."></textarea>
                        @error('notes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                    <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
                    <flux:button wire:click="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">Submit</span>
                        <span wire:loading wire:target="submit">Submitting...</span>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
