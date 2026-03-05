<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar stashable sticky class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
            
            {{-- Dashboard - Everyone --}}
            <flux:navlist.item icon="home" href="{{ route('dashboard') }}">
                Dashboard
            </flux:navlist.item>

            @if(in_array(auth()->user()->role, ['student']))
            <flux:navlist.item icon="document-text" href="{{ route('clearance.student') }}">
                My Clearances
            </flux:navlist.item>
            @endif


            @php
                $isSignatoryUser = in_array(auth()->user()->role, ['admin', 'staff'])
                    || auth()->user()->clubSignatories->isNotEmpty()
                    || auth()->user()->departmentSignatories->isNotEmpty()
                    || auth()->user()->officeSignatories->isNotEmpty()
                    || auth()->user()->studentGovernmentOfficerships()->where('can_sign', true)->where('is_active', true)->exists();
            @endphp
            @if($isSignatoryUser)
            <flux:navlist.item icon="document-check" href="{{ route('clearance.signatory') }}">
                Signatory Clearance Forms
            </flux:navlist.item>
            @endif

            <livewire:notification-bell />
            <flux:separator />

            {{-- Superadmin & Admin Only --}}
            @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                <flux:separator />
                
                <flux:navlist.group expandable heading="User Management" icon="users">
                    <flux:navlist.item href="{{ route('users.index') }}">All Users</flux:navlist.item>
                    <flux:navlist.item href="{{ route('student.index') }}">Students</flux:navlist.item>
                    <flux:navlist.item href="{{ route('staff.index') }}">Staff</flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group expandable heading="System Management" icon="cog">
                    <flux:navlist.item href="{{ route('department.index') }}">Departments</flux:navlist.item>
                    <flux:navlist.item href="{{ route('club.index') }}">Clubs</flux:navlist.item>
                    <flux:navlist.item href="{{ route('office.index') }}">Offices</flux:navlist.item>
                    <flux:navlist.item href="{{ route('homeroom.assignments') }}">Homeroom Advisers</flux:navlist.item>
                    <flux:navlist.item href="{{ route('student-government.index') }}">Student Government</flux:navlist.item>
                    <flux:navlist.item href="{{ route('clearance.periods') }}">Clearance Periods</flux:navlist.item>
                </flux:navlist.group>
            @endif

            {{-- Department Manager/Signatory Section --}}
            @php
                $managedDepartments = auth()->user()->managedDepartments;
                $departmentSignatories = auth()->user()->departmentSignatories;
                $allDepartments = $managedDepartments->merge($departmentSignatories)->unique('id');
            @endphp

            @if($allDepartments->isNotEmpty())
                <flux:separator />
                
                @foreach($allDepartments as $department)
                    <flux:navlist.group 
                        expandable 
                        heading="{{ $department->Abbreviation ?? $department->name }}" 
                        icon="academic-cap"
                    >
                        <flux:navlist.item href="{{ route('department.overview', $department->id) }}">
                            Overview
                        </flux:navlist.item>
                        
                        <flux:navlist.item href="{{ route('department.students', $department->id) }}">
                            Students
                        </flux:navlist.item>
                        
                        <flux:navlist.item href="{{ route('clearance.signatory', ['entityType' => 'department', 'entityId' => $department->id]) }}">
                            Clearances
                        </flux:navlist.item>
                        
                        @if($department->manager_id === auth()->id())
                            <flux:navlist.item href="{{ route('department.signatories', $department->id) }}">
                                Signatories
                            </flux:navlist.item>
                        @endif
                        
                        @if($department->manager_id === auth()->id())
                            <flux:navlist.item href="{{ route('department.edit', $department->id) }}">
                                Settings
                            </flux:navlist.item>
                        @endif
                    </flux:navlist.group>
                @endforeach
            @endif

            {{-- Club Moderator/Signatory Section --}}
            @php
                $managedClubs = auth()->user()->managedClubs;
                $clubSignatories = auth()->user()->clubSignatories;
                $allClubs = $managedClubs->merge($clubSignatories)->unique('id');
            @endphp

            @if($allClubs->isNotEmpty())
                <flux:separator />
                
                @foreach($allClubs as $club)
                    <flux:navlist.group 
                        expandable 
                        heading="{{ $club->Abbreviation ?? $club->name }}" 
                        icon="user-group"
                    >
                        <flux:navlist.item href="{{ route('club.overview', $club->id) }}">
                            Overview
                        </flux:navlist.item>
                        
                        <flux:navlist.item href="{{ route('club.members', $club->id) }}">
                            Members
                        </flux:navlist.item>
                        
                        @if(auth()->user()->role === 'admin')
                            <flux:navlist.item href="{{ route('club.signatories', $club->id) }}">
                                Signatories
                            </flux:navlist.item>
                        @endif
                        
                        <flux:navlist.item href="{{ route('clearance.signatory', ['entityType' => 'club', 'entityId' => $club->id]) }}">
                            Clearances
                        </flux:navlist.item>
                        
                        @if($club->moderator_id === auth()->id())
                            <flux:navlist.item href="{{ route('club.edit', $club->id) }}">
                                Settings
                            </flux:navlist.item>
                        @endif
                    </flux:navlist.group>
                @endforeach
            @endif

            {{-- Office Manager Section --}}
            @php
                $managedOffices = auth()->user()->managedOffices;
                $officeSignatories = auth()->user()->officeSignatories;
                $allOffices = $managedOffices->merge($officeSignatories)->unique('id');
            @endphp

            @if($allOffices->isNotEmpty())
                <flux:separator />
                
                @foreach($allOffices as $office)
                    <flux:navlist.group 
                        expandable 
                        heading="{{ $office->name }}" 
                        icon="building-office"
                    >
                        <flux:navlist.item href="{{ route('office.overview', $office->id) }}">
                            Overview
                        </flux:navlist.item>
                        
                        <flux:navlist.item href="{{ route('clearance.signatory', ['entityType' => 'office', 'entityId' => $office->id]) }}">
                            Clearances
                        </flux:navlist.item>
                        
                        @if(auth()->user()->role === 'admin')
                            <flux:navlist.item href="{{ route('office.signatories', $office->id) }}">
                                Signatories
                            </flux:navlist.item>
                        @endif
                        
                        @if($office->manager_id === auth()->id())
                            <flux:navlist.item href="{{ route('office.edit', $office->id) }}">
                                Settings
                            </flux:navlist.item>
                        @endif
                    </flux:navlist.group>
                @endforeach
            @endif


            <flux:separator />


            <flux:spacer />


            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
