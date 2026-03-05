<?php

namespace App\Livewire\Office;

use App\Models\Office;
use App\Models\ClearancePeriod;
use App\Models\ClearanceRequest;
use App\Models\Department;
use App\Models\StudentDetail;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfficeOverview extends Component
{
    public $office;
    public $officeId;
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
    
    // Filter
    public $departmentFilter = '';
    public $departments = [];

    public function mount($officeId)
    {
        $this->officeId = $officeId;
        $this->office = Office::with(['manager.staffDetail', 'signatories'])
            ->findOrFail($officeId);

        // Check if user has access
        $this->authorizeAccess();
        
        $this->activePeriod = ClearancePeriod::where('is_active', true)->first();
        $this->loadDepartments();
        $this->loadAnalytics();
    }
    
    protected function loadDepartments()
    {
        // Get all departments
        $this->departments = Department::orderBy('name')->get();
    }
    
    public function updatedDepartmentFilter()
    {
        $this->loadAnalytics();
    }

    protected function authorizeAccess()
    {
        $user = Auth::user();
        
        // Superadmin can access everything
        if ($user->role === 'superadmin') {
            return;
        }

        // Admin can access everything
        if ($user->role === 'admin') {
            return;
        }

        // Check if user is manager or signatory
        $isManager = $this->office->manager_id === $user->id;
        $isSignatory = DB::table('office_signatories')
            ->where('office_id', $this->office->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (!$isManager && !$isSignatory) {
            abort(403, 'You do not have access to this office.');
        }
    }
    
    protected function loadAnalytics()
    {
        if (!$this->activePeriod) {
            return;
        }
        
        // Get all students (offices handle clearance for all students)
        $studentQuery = StudentDetail::query();
        
        // Apply department filter if selected
        if ($this->departmentFilter) {
            $studentQuery->where('department_id', $this->departmentFilter);
        }
        
        $studentUserIds = $studentQuery->pluck('user_id')->toArray();
        $this->totalStudents = count($studentUserIds);
        
        if ($this->totalStudents === 0) {
            $this->completedCount = 0;
            $this->inProgressCount = 0;
            $this->pendingCount = 0;
            $this->noRequestCount = 0;
            $this->completionPercentage = 0;
            $this->yearLevelStats = [];
            return;
        }
        
        // Get clearance requests for these students in the active period
        $requests = ClearanceRequest::whereIn('student_id', $studentUserIds)
            ->where('period_id', $this->activePeriod->id)
            ->get();
        
        $this->completedCount = $requests->where('status', 'completed')->count();
        $this->inProgressCount = $requests->where('status', 'in_progress')->count();
        $this->pendingCount = $requests->where('status', 'pending')->count();
        $this->noRequestCount = $this->totalStudents - $requests->count();
        
        $this->completionPercentage = $this->totalStudents > 0 
            ? round(($this->completedCount / $this->totalStudents) * 100, 1) 
            : 0;
        
        // Year level breakdown
        $this->yearLevelStats = [];
        $studentDetails = StudentDetail::whereIn('user_id', $studentUserIds)->get();
        $yearLevels = $studentDetails->groupBy('year_level');
        
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
        return view('livewire.office.office-overview');
    }
}
