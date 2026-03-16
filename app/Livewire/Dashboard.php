<?php

namespace App\Livewire;

use App\Models\ClearanceItem;
use App\Models\ClearancePeriod;
use App\Models\ClearanceRequest;
use App\Models\Club;
use App\Models\Department;
use App\Models\HomeroomAssignment;
use App\Models\Office;
use App\Models\StaffDetail;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class Dashboard extends Component
{
    // Shared
    public $activePeriod = null;

    // Admin / Superadmin stats
    public $totalStudents   = 0;
    public $totalStaff      = 0;
    public $totalDepartments = 0;
    public $totalClubs      = 0;
    public $clearancePending   = 0;
    public $clearanceCompleted = 0;
    public $clearanceTotal     = 0;
    public $recentRequests     = [];

    // Staff / signatory stats
    public $pendingToSign  = 0;
    public $approvedSigned = 0;
    public $rejectedSigned = 0;
    public $managedDepartments = [];
    public $managedClubs       = [];
    public $managedOffices     = [];

    // Student stats
    public $clearanceRequest  = null;
    public $clearanceProgress = 0;
    public $studentPending    = 0;
    public $studentApproved   = 0;
    public $studentRejected   = 0;
    public $studentClubs      = [];

    public function mount()
    {
        $user = Auth::user();
        $this->activePeriod = ClearancePeriod::where('is_active', true)->first();

        if (in_array($user->role, ['superadmin', 'admin'])) {
            $this->loadAdminStats();
        } elseif ($user->role === 'staff') {
            $this->loadStaffStats($user);
        } elseif ($user->role === 'student') {
            $this->loadStudentStats($user);
        }
    }

    // ─── Admin ────────────────────────────────────────────────────────────────

    private function loadAdminStats(): void
    {
        $this->totalStudents    = StudentDetail::count();
        $this->totalStaff       = StaffDetail::count();
        $this->totalDepartments = Department::count();
        $this->totalClubs       = Club::count();

        if ($this->activePeriod) {
            $this->clearanceTotal     = ClearanceRequest::where('period_id', $this->activePeriod->id)->count();
            $this->clearancePending   = ClearanceRequest::where('period_id', $this->activePeriod->id)
                                            ->where('status', 'pending')->count();
            $this->clearanceCompleted = ClearanceRequest::where('period_id', $this->activePeriod->id)
                                            ->where('status', 'completed')->count();

            $this->recentRequests = ClearanceRequest::with('student.studentDetail')
                ->where('period_id', $this->activePeriod->id)
                ->latest()
                ->take(8)
                ->get();
        }
    }

    // ─── Staff ────────────────────────────────────────────────────────────────

    private function loadStaffStats(User $user): void
    {
        $this->managedDepartments = Department::where('manager_id', $user->id)->get();
        $this->managedClubs       = Club::where('moderator_id', $user->id)->get();
        $this->managedOffices     = Office::where('manager_id', $user->id)->get();

        if ($this->activePeriod) {
            // Items the user has already signed this period
            $this->approvedSigned = ClearanceItem::where('signed_by', $user->id)
                ->where('status', 'approved')
                ->whereHas('request', fn($q) => $q->where('period_id', $this->activePeriod->id))
                ->count();

            $this->rejectedSigned = ClearanceItem::where('signed_by', $user->id)
                ->where('status', 'rejected')
                ->whereHas('request', fn($q) => $q->where('period_id', $this->activePeriod->id))
                ->count();

            // Pending items this user still needs to sign (via signatory pivot tables only)
            $deptIds   = $user->departmentSignatories()->wherePivot('is_active', true)->pluck('departments.id');
            $clubIds   = $user->clubSignatories()->wherePivot('is_active', true)->pluck('clubs.id');
            $managerOfficeIds = collect($this->managedOffices)->pluck('id')->map(fn($id) => (int) $id)->all();
            $officeSignatoryScopes = DB::table('office_signatories')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->select('office_id', 'departments', 'year_levels')
                ->get()
                ->map(function ($row) use ($managerOfficeIds) {
                    $departmentIds = array_values(array_filter(array_map('intval', json_decode($row->departments ?? '[]', true) ?? [])));
                    $yearLevels = array_values(array_filter(array_map('intval', json_decode($row->year_levels ?? '[]', true) ?? [])));

                    // Office managers are unrestricted for their own office
                    if (in_array((int) $row->office_id, $managerOfficeIds, true)) {
                        $departmentIds = [];
                        $yearLevels = [];
                    }

                    return (object) [
                        'office_id' => (int) $row->office_id,
                        'department_ids' => $departmentIds,
                        'year_levels' => $yearLevels,
                    ];
                });
            $homeroomIds = HomeroomAssignment::query()
                ->where('adviser_id', $user->id)
                ->where('is_active', true)
                ->pluck('id');
            $studentGovernmentIds = $user->studentGovernmentOfficerships()
                ->where('can_sign', true)
                ->where('is_active', true)
                ->pluck('student_government_id');

            $hasSigningScope = $deptIds->isNotEmpty()
                || $clubIds->isNotEmpty()
                || $officeSignatoryScopes->isNotEmpty()
                || $homeroomIds->isNotEmpty()
                || $studentGovernmentIds->isNotEmpty();

            if (! $hasSigningScope) {
                $this->pendingToSign = 0;
                return;
            }

            $this->pendingToSign = ClearanceItem::where('status', 'pending')
                ->whereHas('request', fn($q) => $q->where('period_id', $this->activePeriod->id))
                ->where(function ($q) use ($deptIds, $clubIds, $officeSignatoryScopes, $homeroomIds, $studentGovernmentIds) {
                    if ($deptIds->isNotEmpty()) {
                        $q->orWhere(function ($q2) use ($deptIds) {
                            $q2->where('signable_type', 'App\\Models\\Department')
                                ->whereIn('signable_id', $deptIds);
                        });
                    }

                    if ($clubIds->isNotEmpty()) {
                        $q->orWhere(function ($q2) use ($clubIds) {
                            $q2->where('signable_type', 'App\\Models\\Club')
                                ->whereIn('signable_id', $clubIds);
                        });
                    }

                    if ($officeSignatoryScopes->isNotEmpty()) {
                        foreach ($officeSignatoryScopes as $scope) {
                            $q->orWhere(function ($q2) use ($scope) {
                                $q2->where('signable_type', 'App\\Models\\Office')
                                    ->where('signable_id', $scope->office_id);

                                if (!empty($scope->department_ids) || !empty($scope->year_levels)) {
                                    $q2->whereHas('request.student.studentDetail', function ($sq) use ($scope) {
                                        if (!empty($scope->department_ids)) {
                                            $sq->whereIn('department_id', $scope->department_ids);
                                        }

                                        if (!empty($scope->year_levels)) {
                                            $sq->whereIn('year_level', $scope->year_levels);
                                        }
                                    });
                                }
                            });
                        }
                    }

                    if ($homeroomIds->isNotEmpty()) {
                        $q->orWhere(function ($q2) use ($homeroomIds) {
                            $q2->whereIn('signable_type', ['homeroom_adviser', 'App\\Models\\HomeroomAssignment'])
                                ->whereIn('signable_id', $homeroomIds);
                        });
                    }

                    if ($studentGovernmentIds->isNotEmpty()) {
                        $q->orWhere(function ($q2) use ($studentGovernmentIds) {
                            $q2->where('signable_type', 'App\\Models\\StudentGovernment')
                                ->whereIn('signable_id', $studentGovernmentIds);
                        });
                    }
                })
                ->count();
        }
    }

    // ─── Student ──────────────────────────────────────────────────────────────

    private function loadStudentStats(User $user): void
    {
        if (!$this->activePeriod) {
            return;
        }

        $this->clearanceRequest = ClearanceRequest::where('student_id', $user->id)
            ->where('period_id', $this->activePeriod->id)
            ->first();

        $this->studentClubs = $user->clubs()->get();

        if (!$this->clearanceRequest) {
            return;
        }

        $items = $this->clearanceRequest->items;

        $total    = $items->count();
        $approved = $items->where('status', 'approved')->count();
        $pending  = $items->where('status', 'pending')->count();
        $rejected = $items->where('status', 'rejected')->count();

        $this->clearanceProgress = $total > 0 ? round(($approved / $total) * 100) : 0;
        $this->studentPending    = $pending;
        $this->studentApproved   = $approved;
        $this->studentRejected   = $rejected;
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
