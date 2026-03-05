<?php

namespace App\Livewire;

use App\Models\ClearanceItem;
use App\Models\ClearancePeriod;
use App\Models\ClearanceRequest;
use App\Models\Club;
use App\Models\Department;
use App\Models\Office;
use App\Models\StaffDetail;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

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
            $officeIds = $user->officeSignatories()->wherePivot('is_active', true)->pluck('offices.id');

            $this->pendingToSign = ClearanceItem::where('status', 'pending')
                ->whereHas('request', fn($q) => $q->where('period_id', $this->activePeriod->id))
                ->where(function ($q) use ($deptIds, $clubIds, $officeIds) {
                    $q->where(function ($q2) use ($deptIds) {
                        $q2->where('signable_type', 'App\\Models\\Department')
                           ->whereIn('signable_id', $deptIds);
                    })->orWhere(function ($q2) use ($clubIds) {
                        $q2->where('signable_type', 'App\\Models\\Club')
                           ->whereIn('signable_id', $clubIds);
                    })->orWhere(function ($q2) use ($officeIds) {
                        $q2->where('signable_type', 'App\\Models\\Office')
                           ->whereIn('signable_id', $officeIds);
                    });
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
