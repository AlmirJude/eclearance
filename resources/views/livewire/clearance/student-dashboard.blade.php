<div class="p-6">

{{-- Print Styles --}}
<style>
    @media print {
        @page {
            size: 8in 2in;
            margin: 0;
        }
        html, body {
            width: 8in;
            height: 2in;
            overflow: hidden;
        }
        body * { visibility: hidden !important; }
        #clearance-slip, #clearance-slip * { visibility: visible !important; }
        #clearance-slip {
            position: fixed !important;
            top: 0; left: 0;
            width: 8in;
            height: 2in;
            padding: 0.2in 0.3in;
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            box-sizing: border-box;
            overflow: hidden;
            page-break-after: avoid;
            visibility: visible !important;
        }
    }
</style>

{{-- Hidden Print Slip --}}
<div id="clearance-slip" style="display:none;">
    <p style="margin:0 0 8px 0; font-size:11pt; line-height:1.6;">
        This is to certify that <strong>{{ $studentName }}</strong> has completed
        <strong>{{ $activePeriod->name ?? '' }}</strong> clearance and is eligible now to enroll in
        <strong>{{ $activePeriod->semester ?? '' }} Semester {{ $activePeriod->academic_year ?? '' }}</strong>.
    </p>
</div>

{{-- Confetti + Completion Modal --}}
@if($clearanceRequest && $clearanceRequest->status === 'completed')
<div id="completion-overlay"
     style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);">
    <div style="background:#fff;border-radius:16px;padding:40px 48px;max-width:420px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="font-size:56px;margin-bottom:12px;">🎉</div>
        <h2 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#16a34a;">Congratulations!</h2>
        <p style="margin:0 0 24px;color:#374151;font-size:15px;">Your clearance is now <strong>finished</strong>.<br>You are cleared for enrollment.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <button onclick="document.getElementById('completion-overlay').style.display='none'"
                    style="padding:10px 24px;border-radius:8px;border:none;background:#16a34a;color:#fff;font-size:14px;font-weight:600;cursor:pointer;">
                Close
            </button>
            <button onclick="printClearanceSlip()"
                    style="padding:10px 24px;border-radius:8px;border:2px solid #16a34a;background:#fff;color:#16a34a;font-size:14px;font-weight:600;cursor:pointer;">
                Print Clearance Slip
            </button>
        </div>
    </div>
</div>
<canvas id="confetti-canvas" style="position:fixed;inset:0;z-index:9998;pointer-events:none;width:100%;height:100%;"></canvas>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
    (function() {
        var canvas = document.getElementById('confetti-canvas');
        var myConfetti = confetti.create(canvas, { resize: true, useWorker: true });
        function burst() {
            myConfetti({ particleCount: 120, spread: 80, origin: { y: 0.6 } });
        }
        burst();
        setTimeout(burst, 800);
        setTimeout(burst, 1600);
    })();

    function printClearanceSlip() {
        document.getElementById('clearance-slip').style.display = 'block';
        window.print();
        document.getElementById('clearance-slip').style.display = 'none';
    }
</script>
@endif
    <div class="mb-6">
        <flux:heading size="xl">My Clearance</flux:heading>
        <p class="text-gray-600 mt-2">Track your clearance progress</p>
    </div>

    <flux:separator class="my-6" />

    @if(!$activePeriod)
        <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-yellow-400 dark:text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-yellow-800 dark:text-yellow-300">No Active Clearance Period</h3>
            <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">There is currently no active clearance period. Please check back later.</p>
        </div>
    @elseif(!$clearanceRequest)
        <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-blue-400 dark:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-blue-800 dark:text-blue-300">Clearance Not Yet Generated</h3>
            <p class="mt-2 text-sm text-blue-600 dark:text-blue-400">Your clearance request for <strong>{{ $activePeriod->name }}</strong> has not been created yet. Please contact the admin.</p>
        </div>
    @else
        {{-- Period Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $activePeriod->name }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $activePeriod->academic_year }} - {{ $activePeriod->semester }} Semester</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                    @if($clearanceRequest->status === 'completed')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                            ✓ Completed
                        </span>
                        <button onclick="printClearanceSlip()"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-600 text-white hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-800 transition-colors ml-2">
                            🖨 Print Slip
                        </button>
                    @elseif($clearanceRequest->status === 'in_progress')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300">
                            In Progress
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                            Pending
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Overall Progress</h4>
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                <div class="h-4 rounded-full transition-all duration-500 {{ $progress == 100 ? 'bg-green-500 dark:bg-green-400' : 'bg-blue-500 dark:bg-blue-400' }}" 
                    style="width: {{ $progress }}%"></div>
            </div>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ collect($clearanceItems)->where('status', 'approved')->count() }} of {{ count($clearanceItems) }} signatures completed
            </p>
        </div>

        {{-- Clearance Items --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Required Signatures</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Complete requirements and get signatures from each office/organization below</p>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($clearanceItems as $item)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                {{-- Status Icon --}}
                                @if($item['status'] === 'approved')
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                @elseif($item['status'] === 'rejected')
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                @elseif($item['can_sign'])
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/50 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                @else
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                @endif

                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h5 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['name'] }}</h5>
                                        {{-- Entity Type Badge --}}
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                            @php
                                                $typeLabel = match($item['type']) {
                                                    'App\\Models\\Club' => 'Club',
                                                    'App\\Models\\Office' => 'Office',
                                                    'App\\Models\\Department' => 'Department',
                                                    'homeroom_adviser' => 'Homeroom',
                                                    'App\\Models\\StudentGovernment' => 'Student Gov',
                                                    default => 'Other'
                                                };
                                            @endphp
                                            {{ $typeLabel }}
                                        </span>
                                    </div>
                                    
                                    {{-- Status Message --}}
                                    @if($item['status'] === 'approved')
                                        <p class="text-xs text-green-600 dark:text-green-400">
                                            ✓ Signed by {{ $item['signed_by'] }} on {{ \Carbon\Carbon::parse($item['signed_at'])->format('M d, Y') }}
                                        </p>
                                    @elseif($item['status'] === 'rejected')
                                        <p class="text-xs text-red-600 dark:text-red-400">
                                            ✗ Rejected: {{ $item['remarks'] ?? 'No reason provided' }}
                                        </p>
                                    @elseif($item['can_sign'])
                                        <p class="text-xs text-yellow-600 dark:text-yellow-400">Awaiting signature from signatory</p>
                                    @else
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Waiting for: 
                                            @foreach($item['blocking'] as $block)
                                                <span class="font-medium">{{ $block['name'] }}</span>@if(!$loop->last), @endif
                                            @endforeach
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                {{-- Signature Status Badge --}}
                                @if($item['status'] === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                        Signed
                                    </span>
                                @elseif($item['status'] === 'rejected')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300">
                                        Rejected
                                    </span>
                                @elseif($item['can_sign'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300">
                                        Ready
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                        Blocked
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Requirements Section --}}
                        <div class="mt-3 ml-14 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                @if($item['requirement_count'] === 0)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 italic">No documents required</span>
                                @else
                                    {{-- Requirement Status --}}
                                    @if($item['requirement_status'] === 'complete')
                                        <span class="inline-flex items-center text-xs text-green-600 dark:text-green-400">
                                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            All requirements complete ({{ $item['approved_count'] }}/{{ $item['required_count'] }})
                                        </span>
                                    @elseif($item['requirement_status'] === 'pending')
                                        <span class="inline-flex items-center text-xs text-yellow-600 dark:text-yellow-400">
                                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            @if(($item['approved_count'] ?? 0) > 0)
                                                {{ $item['approved_count'] }} approved, {{ $item['pending_count'] ?? 0 }} awaiting review
                                            @else
                                                {{ $item['pending_count'] ?? $item['submitted_count'] }} submitted, awaiting review
                                            @endif
                                        </span>
                                    @elseif($item['requirement_status'] === 'rejected')
                                        <span class="inline-flex items-center text-xs text-red-600 dark:text-red-400">
                                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Submission rejected - resubmit required
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-xs text-orange-600 dark:text-orange-400">
                                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            {{ $item['required_count'] }} required document(s) - action needed
                                        </span>
                                    @endif
                                @endif
                            </div>
                            
                            {{-- Requirements Button --}}
                            @if($item['status'] === 'rejected')
                                <a href="{{ route('clearance.submit-requirements', $item['id']) }}" 
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors text-white bg-orange-600 hover:bg-orange-700 dark:bg-orange-700 dark:hover:bg-orange-800">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Resubmit Requirements
                                </a>
                            @elseif($item['status'] !== 'approved')
                                <a href="{{ route('clearance.submit-requirements', $item['id']) }}" 
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md transition-colors
                                        {{ $item['requirement_count'] > 0 && $item['requirement_status'] === 'incomplete' 
                                            ? 'text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800' 
                                            : 'text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30 dark:hover:bg-blue-800/30' }}">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {{ $item['requirement_count'] > 0 ? 'Submit Requirements' : 'View Details' }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-6 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Legend</h5>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-green-500 dark:bg-green-400"></span>
                    <span class="text-gray-600 dark:text-gray-400">Approved</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-yellow-500 dark:bg-yellow-400"></span>
                    <span class="text-gray-600 dark:text-gray-400">Ready for Signature</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">Blocked (Waiting)</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-red-500 dark:bg-red-400"></span>
                    <span class="text-gray-600 dark:text-gray-400">Rejected</span>
                </div>
            </div>
        </div>
    @endif

</div>{{-- end single root --}}
