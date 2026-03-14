<?php

namespace App\Livewire\Club;

use App\Models\Club;
use App\Models\ClearancePeriod;
use App\Models\ClearanceRequest;
use App\Models\Department;
use App\Models\StudentDetail;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class ClubOverview extends Component
{
    public $club;
    public $clubId;
    public $activePeriod;
    
    // Analytics
    public $totalMembers = 0;
    public $completedCount = 0;
    public $inProgressCount = 0;
    public $pendingCount = 0;
    public $noRequestCount = 0;
    public $completionPercentage = 0;
    
    // Year level breakdown (members can be from different year levels)
    public $yearLevelStats = [];
    
    // Filter
    public $departmentFilter = '';
    public $departments = [];

    public function mount($clubId)
    {
        $this->clubId = $clubId;
        $this->club = Club::with(['moderator.staffDetail', 'members', 'signatories'])
            ->findOrFail($clubId);

        // Check if user has access
        $this->authorizeAccess();
        
        $this->activePeriod = ClearancePeriod::where('is_active', true)->first();
        $this->loadDepartments();
        $this->loadAnalytics();
    }
    
    protected function loadDepartments()
    {
        // Get departments that have members in this club
        $memberUserIds = $this->club->members->pluck('id')->toArray();
        $departmentIds = StudentDetail::whereIn('user_id', $memberUserIds)
            ->pluck('department_id')
            ->unique()
            ->filter();
        
        $this->departments = Department::whereIn('id', $departmentIds)
            ->orderBy('name')
            ->get();
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

        // Check if user is moderator or signatory
        $isModerator = $this->club->moderator_id === $user->id;
        $isSignatory = DB::table('club_signatories')
            ->where('club_id', $this->club->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (!$isModerator && !$isSignatory) {
            abort(403, 'You do not have access to this club.');
        }
    }
    
    protected function loadAnalytics()
    {
        if (!$this->activePeriod) {
            return;
        }
        
        // Get all members of this club
        $memberUserIds = $this->club->members->pluck('id')->toArray();
        
        // Apply department filter if selected
        if ($this->departmentFilter) {
            $memberUserIds = StudentDetail::whereIn('user_id', $memberUserIds)
                ->where('department_id', $this->departmentFilter)
                ->pluck('user_id')
                ->toArray();
        }
        
        $this->totalMembers = count($memberUserIds);
        
        if ($this->totalMembers === 0) {
            $this->completedCount = 0;
            $this->inProgressCount = 0;
            $this->pendingCount = 0;
            $this->noRequestCount = 0;
            $this->completionPercentage = 0;
            $this->yearLevelStats = [];
            return;
        }
        
        // Get clearance requests for these members in the active period
        $requests = ClearanceRequest::whereIn('student_id', $memberUserIds)
            ->where('period_id', $this->activePeriod->id)
            ->get();
        
        $this->completedCount = $requests->where('status', 'completed')->count();
        $this->inProgressCount = $requests->where('status', 'in_progress')->count();
        $this->pendingCount = $requests->where('status', 'pending')->count();
        $this->noRequestCount = $this->totalMembers - $requests->count();
        
        $this->completionPercentage = round(($this->completedCount / $this->totalMembers) * 100, 1);
        
        // Year level breakdown - get student details for members
        $this->yearLevelStats = [];
        $memberDetails = StudentDetail::whereIn('user_id', $memberUserIds)->get();
        $yearLevels = $memberDetails->groupBy('year_level');
        
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
        return view('livewire.club.club-overview');
    }
}
