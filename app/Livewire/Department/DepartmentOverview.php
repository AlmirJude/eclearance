<?php

namespace App\Livewire\Department;

use App\Models\Department;
use App\Models\ClearancePeriod;
use App\Models\ClearanceRequest;
use App\Models\StudentDetail;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepartmentOverview extends Component
{
    public $department;
    public $departmentId;
    public $activePeriod;
    
    // Analytics
    public $totalStudents = 0;
    public $completedCount = 0;
    public $inProgressCount = 0;
    public $pendingCount = 0;
    public $noRequestCount = 0;
    public $completionPercentage = 0;
    
    // Year level breakdown
    public $yearLevelStats = [];

    public function mount($departmentId)
    {
        $this->departmentId = $departmentId;
        $this->department = Department::with(['manager.staffDetail', 'students'])
            ->findOrFail($departmentId);

        // Check if user has access
        $this->authorizeAccess();
        
        $this->activePeriod = ClearancePeriod::where('is_active', true)->first();
        $this->loadAnalytics();
    }

    protected function authorizeAccess()
    {
        $user = Auth::user();
        
        // Superadmin can access everything
        if ($user->role === 'superadmin') {
            return;
        }

        // Check if user is manager or signatory
        $isManager = $this->department->manager_id === $user->id;
        $isSignatory = $user->departmentSignatories->contains($this->department->id);

        if (!$isManager && !$isSignatory) {
            abort(403, 'You do not have access to this department.');
        }
    }
    
    protected function loadAnalytics()
    {
        if (!$this->activePeriod) {
            return;
        }
        
        // Get all students in this department
        $departmentStudents = StudentDetail::where('department_id', $this->departmentId)->get();
        $this->totalStudents = $departmentStudents->count();
        
        if ($this->totalStudents === 0) {
            return;
        }
        
        $studentUserIds = $departmentStudents->pluck('user_id')->toArray();
        
        // Get clearance requests for these students in the active period
        $requests = ClearanceRequest::whereIn('student_id', $studentUserIds)
            ->where('period_id', $this->activePeriod->id)
            ->get();
        
        $this->completedCount = $requests->where('status', 'completed')->count();
        $this->inProgressCount = $requests->where('status', 'in_progress')->count();
        $this->pendingCount = $requests->where('status', 'pending')->count();
        $this->noRequestCount = $this->totalStudents - $requests->count();
        
        $this->completionPercentage = round(($this->completedCount / $this->totalStudents) * 100, 1);
        
        // Year level breakdown
        $this->yearLevelStats = [];
        $yearLevels = $departmentStudents->groupBy('year_level');
        
        foreach ($yearLevels as $yearLevel => $students) {
            $yearStudentIds = $students->pluck('user_id')->toArray();
            $yearRequests = ClearanceRequest::whereIn('student_id', $yearStudentIds)
                ->where('period_id', $this->activePeriod->id)
                ->get();
            
            $yearCompleted = $yearRequests->where('status', 'completed')->count();
            $yearTotal = $students->count();
            
            $this->yearLevelStats[] = [
                'year_level' => $yearLevel,
                'total' => $yearTotal,
                'completed' => $yearCompleted,
                'in_progress' => $yearRequests->where('status', 'in_progress')->count(),
                'pending' => $yearRequests->where('status', 'pending')->count(),
                'no_request' => $yearTotal - $yearRequests->count(),
                'percentage' => $yearTotal > 0 ? round(($yearCompleted / $yearTotal) * 100, 1) : 0,
            ];
        }
        
        // Sort by year level
        usort($this->yearLevelStats, fn($a, $b) => $a['year_level'] <=> $b['year_level']);
    }

    public function render()
    {
        return view('livewire.department.department-overview');
    }
}