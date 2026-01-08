<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Add Students') }}</flux:heading>
        <flux:separator variant="subtle" />
    </div>
    
    <form wire:submit.prevent="submit" class="space-y-4">
        @if (session()->has('error'))
            <div class="p-4 mb-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Account Information Section --}}
        <div class="space-y-4">
            <flux:heading size="lg">Account Information</flux:heading>            
            <flux:input wire:model='employee_id' label="Employee ID" placeholder="Employee ID (will be used as login ID)" />            
            <flux:input wire:model='email' label="Email" type="email" placeholder="Email" />            
            <flux:input wire:model='password' label="Password" type="password" placeholder="Password" />           
            <flux:input wire:model='confirmPassword' label="Confirm Password" type="password" placeholder="Confirm Password" />
        </div>

        <flux:separator class="mt-10"/>

        {{-- Student Details Section --}}
        <div class="space-y-4">
            <flux:heading size="lg">Employee Details</flux:heading>
            
            <flux:input wire:model='first_name' label="First Name" placeholder="First Name" />
            <flux:input wire:model='last_name' label="Last Name" placeholder="Last Name" />       
            <flux:input wire:model='department' label="Department" placeholder="Department" />
            <flux:input wire:model='position' label="Position" placeholder="Position" />
        </div>

        <div class="pt-4">
            <flux:button variant='primary' type="submit" class="w-full">
                Add Employee/Staff
            </flux:button>
        </div>
    </form>
</div>