<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Clubs') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your all clubs') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <button wire:click="openAddModal" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
        Add Club
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
                    <th scope="col" class="px-6 py-3">Club Name</th>
                    <th scope="col" class="px-6 py-3">Abbreviation</th>
                    <th scope="col" class="px-6 py-3">Type</th>
                    <th scope="col" class="px-6 py-3">Moderator</th>
                    <th scope="col" class="px-6 py-3 w-96">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($clubs as $club)
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="px-6 py-2">{{ $club->name }}</td>
                    <td class="px-6 py-2">{{ $club->Abbreviation ?? '—' }}</td>
                    <td class="px-6 py-2 capitalize">{{ str_replace('_', ' ', $club->type) }}</td>
                    <td class="px-6 py-2">{{ $club->moderator_name }}</td>
                    <td class="px-6 py-2">
                        <button wire:click="openShowModal({{ $club->id }})" class="px-3 py-2 text-xs text-white bg-gray-600 rounded hover:bg-gray-700">Show</button>
                        <a href="{{ route('club.members', $club->id) }}" class="px-3 py-2 text-xs text-white bg-indigo-600 rounded hover:bg-indigo-700">Members</a>
                        <a href="{{ route('club.signatories', $club->id) }}" class="px-3 py-2 text-xs text-white bg-purple-600 rounded hover:bg-purple-700">Signatories</a>
                        <a href="{{ route('club.requirements', $club->id) }}" class="px-3 py-2 text-xs text-white bg-teal-600 rounded hover:bg-teal-700">Requirements</a>
                        <button wire:click="openEditModal({{ $club->id }})" class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">Edit</button>
                        <button wire:click="confirmDelete({{ $club->id }})" class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">Delete</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Add Club Modal ───────────────────────────────────────────────────── --}}
    <flux:modal name="add-club-modal" class="min-w-[36rem]" wire:model="showAddModal">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Add Club</flux:heading>
                <flux:subheading>Fill in the details for the new club.</flux:subheading>
            </div>

            <div>
                <flux:input wire:model.live="name" label="Club Name" placeholder="e.g. Computer Society" />
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <flux:input wire:model.live="abbreviation" label="Abbreviation" placeholder="e.g. CompSoc" />
                @error('abbreviation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select wire:model.live="type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="academic">Academic</option>
                    <option value="religious">Religious</option>
                    <option value="socio_civic">Socio-Civic</option>
                </select>
                @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <flux:input wire:model.live="description" label="Description" placeholder="Brief description of the club" />
            </div>

            {{-- Moderator search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Moderator</label>
                @if($moderator_id)
                    <div class="flex items-center justify-between rounded-lg border border-green-300 bg-green-50 px-3 py-2">
                        <span class="text-sm font-medium text-green-800">{{ $moderatorSearch }}</span>
                        <button wire:click="clearModerator" class="text-xs text-red-500 hover:text-red-700 ml-3">Remove</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="moderatorSearch"
                        placeholder="Search by name or ID..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />

                    @if($moderatorSearch !== '' && $moderatorResults->isEmpty())
                        <p class="mt-1 text-xs text-gray-400">No staff/admin found matching "{{ $moderatorSearch }}".</p>
                    @endif

                    @if($moderatorResults->isNotEmpty())
                        <ul class="mt-1 max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow divide-y divide-gray-100">
                            @foreach($moderatorResults as $user)
                                @php
                                    $label = $user->staffDetail
                                        ? $user->staffDetail->fullname . ' (' . $user->user_id . ')'
                                        : $user->email . ' (' . $user->user_id . ')';
                                @endphp
                                <li>
                                    <button type="button"
                                        wire:click="selectModerator({{ $user->id }}, '{{ addslashes($label) }}')"
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
                <flux:button variant="primary" wire:click="saveNewClub"
                    wire:loading.attr="disabled" wire:target="saveNewClub">
                    <span wire:loading.remove wire:target="saveNewClub">Save Club</span>
                    <span wire:loading wire:target="saveNewClub">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ── Show (View) Club Modal ───────────────────────────────────────────── --}}
    @if($selectedClub)
        <flux:modal name="view-club-modal" variant="flyout" class="md:w-96 space-y-6" wire:model="showViewModal">
            <div>
                <flux:heading size="lg">Club Details</flux:heading>
                <flux:subheading>View club information</flux:subheading>
            </div>

            <div class="space-y-4">
                <div>
                    <flux:subheading class="text-xs text-gray-500">Club Name</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedClub->name }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Abbreviation</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedClub->Abbreviation ?: '—' }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Type</flux:subheading>
                    <p class="text-sm font-medium capitalize">{{ str_replace('_', ' ', $selectedClub->type) }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Description</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedClub->description ?: '—' }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Moderator</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedClub->moderator_name }}</p>
                </div>

                <flux:separator variant="subtle" />

                <div>
                    <flux:subheading class="text-xs text-gray-500">Created At</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedClub->created_at->format('M d, Y h:i A') }}</p>
                </div>

                <div>
                    <flux:subheading class="text-xs text-gray-500">Last Updated</flux:subheading>
                    <p class="text-sm font-medium">{{ $selectedClub->updated_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button variant="ghost" wire:click="closeShowModal">Close</flux:button>
                <flux:button variant="primary" wire:click="openEditModal({{ $selectedClub->id }}); closeShowModal()">Edit</flux:button>
            </div>
        </flux:modal>
    @endif

    {{-- ── Edit Club Modal ─────────────────────────────────────────────────── --}}
    <flux:modal name="edit-club-modal" class="min-w-[36rem]" wire:model="showEditModal">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Edit Club</flux:heading>
                <flux:subheading>Update the club details.</flux:subheading>
            </div>

            <div>
                <flux:input wire:model.live="editName" label="Club Name" placeholder="e.g. Computer Society" />
                @error('editName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <flux:input wire:model.live="editAbbreviation" label="Abbreviation" placeholder="e.g. CompSoc" />
                @error('editAbbreviation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select wire:model.live="editType" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="academic">Academic</option>
                    <option value="religious">Religious</option>
                    <option value="socio_civic">Socio-Civic</option>
                </select>
                @error('editType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <flux:input wire:model.live="editDescription" label="Description" placeholder="Brief description of the club" />
            </div>

            {{-- Edit moderator search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Moderator</label>
                @if($editModeratorId)
                    <div class="flex items-center justify-between rounded-lg border border-green-300 bg-green-50 px-3 py-2">
                        <span class="text-sm font-medium text-green-800">{{ $editModeratorSearch }}</span>
                        <button wire:click="clearEditModerator" class="text-xs text-red-500 hover:text-red-700 ml-3">Remove</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="editModeratorSearch"
                        placeholder="Search by name or ID..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />

                    @if($editModeratorSearch !== '' && $editModeratorResults->isEmpty())
                        <p class="mt-1 text-xs text-gray-400">No staff/admin found matching "{{ $editModeratorSearch }}".</p>
                    @endif

                    @if($editModeratorResults->isNotEmpty())
                        <ul class="mt-1 max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow divide-y divide-gray-100">
                            @foreach($editModeratorResults as $user)
                                @php
                                    $label = $user->staffDetail
                                        ? $user->staffDetail->fullname . ' (' . $user->user_id . ')'
                                        : $user->email . ' (' . $user->user_id . ')';
                                @endphp
                                <li>
                                    <button type="button"
                                        wire:click="selectEditModerator({{ $user->id }}, '{{ addslashes($label) }}')"
                                        class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 transition">
                                        <span class="font-medium text-gray-800">{{ $label }}</span>
                                        <span class="ml-2 text-xs text-gray-400 capitalize">{{ $user->role }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
                @error('editModeratorId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <flux:button variant="ghost" wire:click="closeEditModal">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveEditClub"
                    wire:loading.attr="disabled" wire:target="saveEditClub">
                    <span wire:loading.remove wire:target="saveEditClub">Save Changes</span>
                    <span wire:loading wire:target="saveEditClub">Saving...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ── Delete Club Modal ───────────────────────────────────────────────── --}}
    @if($clubToDelete)
        <flux:modal name="delete-club-modal" class="min-w-[22rem]" wire:model="showDeleteModal">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Club?</flux:heading>
                    <flux:text class="mt-2">
                        You're about to delete <strong>{{ $clubToDelete->name }}</strong>
                        @if($clubToDelete->Abbreviation) ({{ $clubToDelete->Abbreviation }}) @endif.<br>
                        This action cannot be reversed.
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="ghost" wire:click="closeDeleteModal">Cancel</flux:button>
                    <flux:button wire:click="delete" variant="danger">Delete Club</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
