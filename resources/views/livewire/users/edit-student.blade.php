<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Edit Students') }}</flux:heading>
        <flux:separator variant="subtle" />
    </div>
    
    <form wire:submit.prevent="save" class="space-y-4">
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

        {{-- Account Information Section --}}
        <div class="space-y-4">
            <flux:heading size="lg">Account Information</flux:heading>
            
            <flux:input wire:model.live='student_id' label="Student ID" placeholder="Student ID (will be used as login ID)" inputmode="numeric" pattern="[0-9-]*" maxlength="6" oninput="this.value = this.value.replace(/[^0-9-]/g, '').slice(0, 6)" />
            @error('student_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            
            <flux:input wire:model='email' label="Email" type="email" placeholder="Email" />
            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            
            <flux:input wire:model='password' label="Password" type="password" placeholder="Password" />
            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            
            <flux:input wire:model='confirmPassword' label="Confirm Password" type="password" placeholder="Confirm Password" />
            @error('confirmPassword') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <flux:separator class="mt-10"/>

        {{-- Student Details Section --}}
        <div class="space-y-4">
            <flux:heading size="lg">Student Details</flux:heading>
            
            <flux:input wire:model='first_name' label="First Name" placeholder="First Name" />
            @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            
            <flux:input wire:model='last_name' label="Last Name" placeholder="Last Name" />
            @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            
            {{-- Course/Department Select --}}
            <div>
                <flux:label>Course/Department</flux:label>
                <select wire:model='department_id' class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <ption value="">Select Course/Department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            
            {{-- Year Level Select --}}
            <div>
                <flux:label>Year Level</flux:label>
                <select wire:model='year_level' 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                    <option value="5">5th Year</option>
                </select>
                @error('year_level') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="pt-4">
            <flux:button variant='primary' type="submit" class="w-full">
                Add Student
            </flux:button>
        </div>
    </form>
</div>