<div class="flex h-full w-full flex-1 flex-col gap-6">

    @php $user = auth()->user(); @endphp

    {{-- ─── PAGE HEADER ───────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            <flux:heading size="xl" level="1">Welcome Back, {{ $user->GetTheUserName() ?: $user->email }} 👋</flux:heading>
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            @if($activePeriod)
                Active period: <span class="font-medium text-indigo-600">{{ $activePeriod->name }}</span>
                &mdash; {{ $activePeriod->academic_year }}, {{ $activePeriod->semester }}
                ({{ $activePeriod->start_date->format('M d') }} – {{ $activePeriod->end_date->format('M d, Y') }})
            @else
                <span class="text-amber-600 font-medium">No active clearance period.</span>
            @endif
        </p>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         ADMIN / SUPERADMIN VIEW
    ══════════════════════════════════════════════════════════════════════ --}}
    @if(in_array($user->role, ['superadmin', 'admin']))

        {{-- Stat cards --}}
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-neutral-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Students</p>
                <p class="mt-2 text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $totalStudents }}</p>
                <a href="{{ route('student.index') }}" class="mt-3 inline-block text-xs text-indigo-500 dark:text-indigo-400 hover:underline">Manage →</a>
            </div>
            <div class="rounded-xl border border-neutral-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Staff</p>
                <p class="mt-2 text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $totalStaff }}</p>
                <a href="{{ route('staff.index') }}" class="mt-3 inline-block text-xs text-teal-500 dark:text-teal-400 hover:underline">Manage →</a>
            </div>
            <div class="rounded-xl border border-neutral-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Departments</p>
                <p class="mt-2 text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $totalDepartments }}</p>
                <a href="{{ route('department.index') }}" class="mt-3 inline-block text-xs text-purple-500 dark:text-purple-400 hover:underline">Manage →</a>
            </div>
            <div class="rounded-xl border border-neutral-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Clubs</p>
                <p class="mt-2 text-3xl font-bold text-orange-500 dark:text-orange-400">{{ $totalClubs }}</p>
                <a href="{{ route('club.index') }}" class="mt-3 inline-block text-xs text-orange-400 dark:text-orange-300 hover:underline">Manage →</a>
            </div>
        </div>

        {{-- Clearance summary + recent requests --}}
        @if($activePeriod)
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-neutral-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Requests Filed</p>
                    <p class="mt-2 text-3xl font-bold text-gray-700 dark:text-gray-200">{{ $clearanceTotal }}</p>
                </div>
                <div class="rounded-xl border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/30 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-yellow-700 dark:text-yellow-400">Pending</p>
                    <p class="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $clearancePending }}</p>
                </div>
                <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-green-700 dark:text-green-400">Completed</p>
                    <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ $clearanceCompleted }}</p>
                </div>
            </div>

            {{-- Recent requests table --}}
            <div class="rounded-xl border border-neutral-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-neutral-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Clearance Requests</h2>
                    <a href="{{ route('clearance.periods') }}" class="text-xs text-indigo-500 dark:text-indigo-400 hover:underline">Manage Periods →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-5 py-3">Student</th>
                                <th class="px-5 py-3">Student ID</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Requested</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRequests as $req)
                                <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <td class="px-5 py-2">{{ $req->student?->fullname ?? '—' }}</td>
                                    <td class="px-5 py-2 text-gray-500 dark:text-gray-400">{{ $req->student?->studentDetail?->student_id ?? '—' }}</td>
                                    <td class="px-5 py-2">
                                        @if($req->status === 'completed')
                                            <span class="inline-block px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300">Completed</span>
                                        @elseif($req->status === 'pending')
                                            <span class="inline-block px-2 py-0.5 text-xs rounded-full bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300">Pending</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ ucfirst($req->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-2 text-gray-400 dark:text-gray-500 text-xs">{{ $req->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-4 text-center text-gray-400 dark:text-gray-500">No requests yet for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 p-6 text-sm text-amber-700 dark:text-amber-300">
                No active clearance period is set.
                <a href="{{ route('clearance.periods') }}" class="ml-2 font-semibold underline dark:text-amber-300 dark:hover:text-amber-200">Create one →</a>
            </div>
        @endif

    {{-- ═══════════════════════════════════════════════════════════════════
         STAFF VIEW
    ══════════════════════════════════════════════════════════════════════ --}}
    @elseif($user->role === 'staff')

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/30 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-yellow-700 dark:text-yellow-400">Pending to Sign</p>
                <p class="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $pendingToSign }}</p>
                <a href="{{ route('clearance.signatory') }}" class="mt-3 inline-block text-xs text-yellow-600 dark:text-yellow-400 hover:underline">Go to Signatory Dashboard →</a>
            </div>
            <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-green-700 dark:text-green-400">Approved This Period</p>
                <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ $approvedSigned }}</p>
            </div>
            <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-400">Rejected This Period</p>
                <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">{{ $rejectedSigned }}</p>
            </div>
        </div>

        {{-- Managed entities --}}
        <div class="grid gap-4 md:grid-cols-3">
            @if($managedDepartments->count())
                <div class="rounded-xl border border-neutral-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">My Departments</p>
                    <ul class="space-y-1">
                        @foreach($managedDepartments as $dept)
                            <li>
                                <a href="{{ route('department.overview', $dept->id) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $dept->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($managedClubs->count())
                <div class="rounded-xl border border-neutral-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">My Clubs</p>
                    <ul class="space-y-1">
                        @foreach($managedClubs as $club)
                            <li>
                                <a href="{{ route('club.overview', $club->id) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $club->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($managedOffices->count())
                <div class="rounded-xl border border-neutral-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">My Offices</p>
                    <ul class="space-y-1">
                        @foreach($managedOffices as $office)
                            <li>
                                <a href="{{ route('office.overview', $office->id) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $office->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!$managedDepartments->count() && !$managedClubs->count() && !$managedOffices->count())
                <div class="md:col-span-3 rounded-xl border border-neutral-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 text-sm text-gray-400 dark:text-gray-500 text-center">
                    You are not currently managing any departments, clubs, or offices.
                </div>
            @endif
        </div>

    {{-- ═══════════════════════════════════════════════════════════════════
         STUDENT VIEW
    ══════════════════════════════════════════════════════════════════════ --}}
    @elseif($user->role === 'student')

        @if(!$activePeriod)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-700">
                There is no active clearance period at the moment. Check back later.
            </div>
        @elseif(!$clearanceRequest)
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-6 text-sm text-blue-700">
                You have no clearance request for the current period yet.
                <a href="{{ route('clearance.student') }}" class="ml-2 font-semibold underline">Go to My Clearance →</a>
            </div>
        @else
            {{-- Stat cards --}}
            <div class="grid gap-4 md:grid-cols-3">
                {{-- Progress card --}}
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Clearance Progress</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $clearanceProgress }}%</p>
                    <div class="mt-3 h-2 rounded-full bg-gray-100">
                        <div class="h-2 rounded-full bg-indigo-500 transition-all duration-500"
                             style="width: {{ $clearanceProgress }}%"></div>
                    </div>
                    <a href="{{ route('clearance.student') }}" class="mt-3 inline-block text-xs text-indigo-500 hover:underline">View Details →</a>
                </div>

                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-yellow-700">Pending Items</p>
                    <p class="mt-2 text-3xl font-bold text-yellow-600">{{ $studentPending }}</p>
                    <p class="mt-1 text-xs text-yellow-600">Awaiting signature</p>
                </div>

                <div class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-green-700">Approved Items</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">{{ $studentApproved }}</p>
                    @if($studentRejected > 0)
                        <p class="mt-1 text-xs text-red-500">{{ $studentRejected }} rejected</p>
                    @endif
                </div>
            </div>

            {{-- Clearance status banner --}}
            <div class="rounded-xl border p-5
                @if($clearanceRequest->status === 'completed') border-green-300 bg-green-50
                @else border-yellow-200 bg-yellow-50 @endif">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold
                            @if($clearanceRequest->status === 'completed') text-green-700
                            @else text-yellow-700 @endif">
                            Clearance Status: <span class="capitalize">{{ $clearanceRequest->status }}</span>
                        </p>
                        <p class="text-xs mt-1 text-gray-500">
                            Period: {{ $activePeriod->name }} &mdash; {{ $activePeriod->academic_year }}, {{ $activePeriod->semester }}
                        </p>
                    </div>
                    <a href="{{ route('clearance.student') }}"
                       class="px-4 py-2 text-xs text-white bg-indigo-600 rounded hover:bg-indigo-700">
                        View My Clearance
                    </a>
                </div>
            </div>
        @endif

        {{-- Clubs --}}
        @if(count($studentClubs))
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">My Clubs</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($studentClubs as $club)
                        <span class="inline-block px-3 py-1 text-xs rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                            {{ $club->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

    @endif

</div>
