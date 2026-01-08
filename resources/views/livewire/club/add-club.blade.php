<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Add Club') }}</flux:heading>
        <flux:separator variant="subtle" />
    </div>

    <form wire:submit.prevent="submit" class="space-y-4">
        {{-- Flash Messages --}}
        @if (session()->has('success'))
            <div class="p-4 mb-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-700">{{ session('success') }}</p>
            </div>
        @endif
        
        @if (session()->has('error'))
            <div class="p-4 mb-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Club Information Section --}}
        <div class="space-y-4">
            <flux:heading size="lg">Club Information</flux:heading>
            
            <flux:input wire:model='name' label="Club Name" placeholder="Club Name" />
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            
            <flux:label>Club Type</flux:label>
                <select wire:model='type' class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="academic">Academic</option>
                    <option value="religious">Religious</option>
                    <option value="socio_civic">Socio-Civic</option>
                </select>
            @error('type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror 

            <flux:input wire:model='description' label="Description" placeholder="Description" />
            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

            <flux:input wire:model='abbreviation' label="Abbreviation" placeholder="Abbreviation" />
            @error('abbreviation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

            <flux:select wire:model='moderator_id' label="Moderator" placeholder="Select Moderator">
                <option value="">Select Moderator</option>
                @foreach ($availableModerators as $moderator)
                    <option value="{{ $moderator->id }}">{{ $moderator->fullname}}, {{$moderator->department}}</option>
                @endforeach
            </flux:select>
            @error('moderator_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="pt-4">
            <flux:button variant='primary' type="submit" class="w-full">
                Add Club
            </flux:button>
        </div>
    </form>
</div>
