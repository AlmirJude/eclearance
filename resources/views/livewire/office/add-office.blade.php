<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Add Office') }}</flux:heading>
        <flux:separator variant="subtle" />
    </div>
    
    <form wire:submit.prevent="save" class="space-y-4">
        @if (session()->has('error'))
            <div class="p-4 mb-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Office Information Section --}}
        <div class="space-y-4">
            <flux:heading size="lg">Office Information</flux:heading>            
            <flux:input wire:model='name' label="Office Name" placeholder="Office Name" />            
            <flux:select wire:model='manager_id' label="Dean / Program Head" placeholder="Select Dean / Program Head">
                <option value="">Select Dean / Program Head</option>
                @foreach ($availableAdmins as $admin)
                    <option value="{{ $admin->id }}">{{ $admin->fullname}}, {{$admin->department}}</option>
                @endforeach
            </flux:select>
        </div>

        <flux:separator class="mt-10"/>


        <div class="pt-4">
            <flux:button variant='primary' type="submit" class="w-full">
                Add Department
            </flux:button>
        </div>
    </form>
</div>