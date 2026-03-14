<?php

namespace App\Livewire\Clearance;

use Livewire\Component;
use App\Models\ClearancePeriod;
use App\Models\ClearanceRequest;
use App\Models\ClearanceItem;
use App\Models\ClearanceRequirement;
use App\Models\RequirementSubmission;
use App\Services\ClearanceResolver;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 2. Add this attribute to force the wrapper
class StudentDashboard extends Component
{
    public $activePeriod = null;
    public $clearanceRequest = null;
    public $clearanceItems = [];
    public $progress = 0;
    public $studentName = '';
    
    public function mount()
    {
        $this->loadClearanceData();
    }
    
    public function loadClearanceData()
    {
        /** @var \App\Models\User $student */
        $student = Auth::user();
        $this->studentName = $student->GetTheUserName();
        
        // Get active clearance period
        $this->activePeriod = ClearancePeriod::where('is_active', true)->first();
        
        if (!$this->activePeriod) {
            return;
        }
        
        // Get student's clearance request for this period
        $this->clearanceRequest = ClearanceRequest::where('student_id', $student->id)
            ->where('period_id', $this->activePeriod->id)
            ->first();
        
        if (!$this->clearanceRequest) {
            return;
        }
        
        // Get clearance status with dependency info
        $resolver = new ClearanceResolver();
        $items = $resolver->getClearanceStatus($this->clearanceRequest);
        
        // Enhance with requirement info
        $this->clearanceItems = $items->map(function($item) use ($student) {
            // Map special types to model classes for requirements lookup
            $requirableType = $item['type'];
            if ($item['type'] === 'homeroom_adviser') {
                $requirableType = 'App\\Models\\HomeroomAssignment';
            }
            
            // Get requirements for this entity
            $requirements = ClearanceRequirement::where('requirable_type', $requirableType)
                ->where('requirable_id', $item['signable_id'])
                ->where('is_active', true)
                ->get()
                ->filter(fn($req) => $req->appliesToStudent($student));
            
            $totalReqs = $requirements->count();
            $requiredReqs = $requirements->where('is_required', true)->count();
            
            // Get submissions for this item
            $submissions = RequirementSubmission::where('clearance_item_id', $item['id'])
                ->where('student_id', $student->id)
                ->get()
                ->keyBy('requirement_id');
            
            $submittedCount = $submissions->count();
            $approvedCount = $submissions->where('status', 'approved')->count();
            $pendingCount = $submissions->where('status', 'pending')->count();
            $rejectedCount = $submissions->where('status', 'rejected')->count();
            
            // Calculate requirement status
            $reqStatus = 'none'; // No requirements
            if ($totalReqs > 0) {
                if ($approvedCount >= $requiredReqs) {
                    $reqStatus = 'complete';
                } elseif ($rejectedCount > 0) {
                    $reqStatus = 'rejected';
                } elseif ($pendingCount > 0) {
                    $reqStatus = 'pending';
                } else {
                    $reqStatus = 'incomplete';
                }
            }
            
            $item['requirement_count'] = $totalReqs;
            $item['required_count'] = $requiredReqs;
            $item['submitted_count'] = $submittedCount;
            $item['approved_count'] = $approvedCount;
            $item['requirement_status'] = $reqStatus;
            
            return $item;
        })->toArray();
        
        // Calculate progress
        $total = count($this->clearanceItems);
        $approved = collect($this->clearanceItems)->where('status', 'approved')->count();
        $this->progress = $total > 0 ? round(($approved / $total) * 100) : 0;
    }
    
    public function getStatusColor($status, $canSign)
    {
        if ($status === 'approved') return 'green';
        if ($status === 'rejected') return 'red';
        if ($canSign) return 'yellow'; // Ready to be signed
        return 'gray'; // Blocked
    }
    
    public function render()
    {
        return view('livewire.clearance.student-dashboard');
    }
}
