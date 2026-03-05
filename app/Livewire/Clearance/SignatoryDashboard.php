<?php

namespace App\Livewire\Clearance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ClearancePeriod;
use App\Models\ClearanceRequest;
use App\Models\ClearanceItem;
use App\Models\RequirementSubmission;
use App\Models\User;
use App\Services\ClearanceResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SignatoryDashboard extends Component
{
    use WithPagination;
    
    public $activePeriod = null;
    public $signatoryEntities = [];
    public $selectedEntity = null;
    public $selectedEntityType = null;
    
    // Modal
    public $showSignModal = false;
    public $signingItemId = null;
    public $signingStudent = null;
    public $remarks = '';
    public $signAction = 'approve'; // 'approve' or 'reject'
    
    // Submissions Modal
    public $showSubmissionsModal = false;
    public $viewingItemId = null;
    public $viewingStudentName = '';
    public $studentSubmissions = [];
    
    // Status filter
    public $statusFilter = 'pending'; // 'pending', 'approved', 'rejected'
    
    // Department & Year Level filters
    public $departmentFilter = '';
    public $yearLevelFilter = '';
    public $availableDepartments = [];

    // Search & requirements filters
    public $searchQuery = '';
    public $requirementsFilter = 'all'; // 'all', 'with_submissions', 'without_submissions'
    
    // Filtered items
    public $filteredItems = [];
    
    // Stats
    public $pendingCount = 0;
    public $approvedCount = 0;
    public $rejectedCount = 0;
    
    // Bulk Sign Modal
    public $showBulkSignModal = false;
    public $bulkDepartments = [];
    public $bulkYearLevels = [];
    public $bulkRemarks = '';
    public $bulkMatchCount = 0;
    public $bulkSignInProgress = false;
    
    // Query parameters for pre-selection
    #[\Livewire\Attributes\Url]
    public $entityType = null;
    
    #[\Livewire\Attributes\Url]
    public $entityId = null;
    
    public function mount()
    {
        $this->activePeriod = ClearancePeriod::where('is_active', true)->first();
        $this->loadSignatoryEntities();
    }
    
    public function loadSignatoryEntities()
    {
        $user = Auth::user();
        $entities = [];
        
        // Check club signatories
        $clubSignatories = DB::table('club_signatories')
            ->join('clubs', 'club_signatories.club_id', '=', 'clubs.id')
            ->where('club_signatories.user_id', $user->id)
            ->where('club_signatories.is_active', true)
            ->select('clubs.id', 'clubs.name', DB::raw("'App\\\\Models\\\\Club' as type"))
            ->get();
        
        foreach ($clubSignatories as $club) {
            $entities[] = ['id' => $club->id, 'name' => $club->name, 'type' => $club->type, 'label' => 'Club'];
        }
        
        // Check office signatories
        $officeSignatories = DB::table('office_signatories')
            ->join('offices', 'office_signatories.office_id', '=', 'offices.id')
            ->where('office_signatories.user_id', $user->id)
            ->where('office_signatories.is_active', true)
            ->select('offices.id', 'offices.name', DB::raw("'App\\\\Models\\\\Office' as type"))
            ->get();
        
        foreach ($officeSignatories as $office) {
            $entities[] = ['id' => $office->id, 'name' => $office->name, 'type' => $office->type, 'label' => 'Office'];
        }
        
        // Check department signatories
        $deptSignatories = DB::table('department_signatories')
            ->join('departments', 'department_signatories.department_id', '=', 'departments.id')
            ->where('department_signatories.user_id', $user->id)
            ->where('department_signatories.is_active', true)
            ->select('departments.id', 'departments.name', DB::raw("'App\\\\Models\\\\Department' as type"))
            ->get();
        
        foreach ($deptSignatories as $dept) {
            $entities[] = ['id' => $dept->id, 'name' => $dept->name, 'type' => $dept->type, 'label' => 'Department'];
        }
        
        // Check homeroom assignments
        $homeroomAssignments = DB::table('homeroom_assignments')
            ->join('departments', 'homeroom_assignments.department_id', '=', 'departments.id')
            ->where('homeroom_assignments.adviser_id', $user->id)
            ->where('homeroom_assignments.is_active', true)
            ->select('homeroom_assignments.id', 'departments.name', 'homeroom_assignments.year_levels', DB::raw("'homeroom_adviser' as type"))
            ->get();
        
        foreach ($homeroomAssignments as $homeroom) {
            $yearLevels = json_decode($homeroom->year_levels ?? '[]', true);
            $yearLabel = !empty($yearLevels) ? 'Year ' . implode(', ', $yearLevels) : 'All Years';
            $entities[] = [
                'id' => $homeroom->id, 
                'name' => $homeroom->name . ' - ' . $yearLabel, 
                'type' => $homeroom->type, 
                'label' => 'Homeroom'
            ];
        }
        
        // Check student government officers
        $sgOfficers = DB::table('student_government_officers')
            ->join('student_governments', 'student_government_officers.student_government_id', '=', 'student_governments.id')
            ->where('student_government_officers.user_id', $user->id)
            ->where('student_government_officers.can_sign', true)
            ->where('student_government_officers.is_active', true)
            ->select('student_governments.id', 'student_governments.name', DB::raw("'App\\\\Models\\\\StudentGovernment' as type"))
            ->get();
        
        foreach ($sgOfficers as $sg) {
            $entities[] = ['id' => $sg->id, 'name' => $sg->name, 'type' => $sg->type, 'label' => 'Student Gov'];
        }
        
        $this->signatoryEntities = $entities;
        
        // Check if we have query parameters for pre-selection
        if ($this->entityType && $this->entityId) {
            $targetType = match($this->entityType) {
                'department' => 'App\\Models\\Department',
                'club' => 'App\\Models\\Club',
                'office' => 'App\\Models\\Office',
                'student-government' => 'App\\Models\\StudentGovernment',
                'homeroom' => 'homeroom_adviser',
                default => null
            };
            
            if ($targetType) {
                foreach ($entities as $index => $entity) {
                    if ($entity['type'] === $targetType && $entity['id'] == $this->entityId) {
                        $this->selectEntity($index);
                        return;
                    }
                }
            }
        }
        
        // Auto-select first entity if no pre-selection
        if (count($entities) > 0 && !$this->selectedEntity) {
            $this->selectEntity(0);
        }
    }
    
    public function selectEntity($index)
    {
        if (!isset($this->signatoryEntities[$index])) {
            return;
        }
        
        $entity = $this->signatoryEntities[$index];
        $this->selectedEntityType = $entity['type'];
        $this->selectedEntity = $entity;
        $this->statusFilter       = 'pending';
        $this->departmentFilter   = '';
        $this->yearLevelFilter    = '';
        $this->searchQuery        = '';
        $this->requirementsFilter = 'all';
        $this->loadAvailableDepartments();
        $this->updateStats();
        $this->loadFilteredItems();
        $this->resetPage();
    }
    
    public function filterByStatus($status)
    {
        $this->statusFilter = $status;
        $this->loadFilteredItems();
        $this->resetPage();
    }
    
    public function updatedDepartmentFilter()
    {
        $this->loadFilteredItems();
        $this->resetPage();
    }
    
    public function updatedYearLevelFilter()
    {
        $this->loadFilteredItems();
        $this->resetPage();
    }

    public function updatedSearchQuery()
    {
        $this->loadFilteredItems();
        $this->resetPage();
    }

    public function updatedRequirementsFilter()
    {
        $this->loadFilteredItems();
        $this->resetPage();
    }
    
    public function clearFilters()
    {
        $this->departmentFilter   = '';
        $this->yearLevelFilter    = '';
        $this->searchQuery        = '';
        $this->requirementsFilter = 'all';
        $this->loadFilteredItems();
        $this->resetPage();
    }
    
    protected function loadAvailableDepartments()
    {
        if (!$this->selectedEntity || !$this->activePeriod) {
            $this->availableDepartments = [];
            return;
        }
        
        // Get all student IDs that have a clearance item for this entity in the active period
        $studentIds = ClearanceItem::whereHas('request', function($q) {
                $q->where('period_id', $this->activePeriod->id);
            })
            ->where('signable_type', $this->selectedEntityType)
            ->where('signable_id', $this->selectedEntity['id'])
            ->with('request.student.studentDetail.department')
            ->get()
            ->pluck('request.student.studentDetail.department_id')
            ->unique()
            ->filter();
        
        $this->availableDepartments = \App\Models\Department::whereIn('id', $studentIds)
            ->orderBy('name')
            ->get()
            ->toArray();
    }
    
    public function loadFilteredItems()
    {
        if (!$this->activePeriod || !$this->selectedEntity) {
            $this->filteredItems = [];
            return;
        }
        
        $resolver = new ClearanceResolver();
        
        $items = ClearanceItem::with(['request.student.studentDetail.department', 'signer.staffDetail'])
            ->whereHas('request', function($q) {
                $q->where('period_id', $this->activePeriod->id);
            })
            ->whereHas('request.student.studentDetail', function($q) {
                if ($this->departmentFilter) {
                    $q->where('department_id', $this->departmentFilter);
                }
                if ($this->yearLevelFilter !== '') {
                    $q->where('year_level', $this->yearLevelFilter);
                }
                if ($this->searchQuery) {
                    $search = '%' . $this->searchQuery . '%';
                    $q->where(function($sq) use ($search) {
                        $sq->where('first_name', 'like', $search)
                           ->orWhere('last_name', 'like', $search)
                           ->orWhere('student_id', 'like', $search)
                           ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$search]);
                    });
                }
            })
            ->where('signable_type', $this->selectedEntityType)
            ->where('signable_id', $this->selectedEntity['id'])
            ->where('status', $this->statusFilter)
            ->when($this->requirementsFilter === 'with_submissions',    fn($q) => $q->whereHas('submissions'))
            ->when($this->requirementsFilter === 'without_submissions', fn($q) => $q->whereDoesntHave('submissions'))
            ->withCount('submissions')
            ->get();
        
        // Map items with can_sign status
        $this->filteredItems = $items->map(function($item) use ($resolver) {
            $clearanceRequest = ClearanceRequest::find($item->request_id);
            $canSign = $resolver->canSign($item->signable_type, $item->signable_id, $clearanceRequest);
            
            $studentDetail = $item->request->student->studentDetail ?? null;
            
            // Get signer name for approved/rejected items
            $signerName = null;
            if ($item->signer) {
                $signerName = $item->signer->staffDetail 
                    ? $item->signer->staffDetail->first_name . ' ' . $item->signer->staffDetail->last_name
                    : $item->signer->email;
            }
            
            return [
                'id' => $item->id,
                'student_name' => $studentDetail ? $studentDetail->first_name . ' ' . $studentDetail->last_name : 'Unknown',
                'student_id' => $studentDetail->student_id ?? 'N/A',
                'department' => $studentDetail->department->name ?? 'N/A',
                'year_level' => $studentDetail->year_level ?? 'N/A',
                'can_sign' => $canSign['can_sign'],
                'blocking' => $canSign['blocking'] ?? [],
                'status' => $item->status,
                'signed_by' => $signerName,
                'signed_at'         => $item->signed_at?->format('M d, Y H:i'),
                'remarks'           => $item->remarks,
                'has_submissions'   => $item->submissions_count > 0,
                'submissions_count' => $item->submissions_count,
            ];
        })->toArray();
    }
    
    public function updateStats()
    {
        if (!$this->activePeriod || !$this->selectedEntity) {
            return;
        }
        
        $query = ClearanceItem::whereHas('request', function($q) {
                $q->where('period_id', $this->activePeriod->id);
            })
            ->where('signable_type', $this->selectedEntityType)
            ->where('signable_id', $this->selectedEntity['id']);
        
        $this->pendingCount = (clone $query)->where('status', 'pending')->count();
        $this->approvedCount = (clone $query)->where('status', 'approved')->count();
        $this->rejectedCount = (clone $query)->where('status', 'rejected')->count();
    }
    
    public function openSignModal($itemId, $action = 'approve')
    {
        $item = ClearanceItem::with('request.student.studentDetail')->find($itemId);
        
        if (!$item) {
            session()->flash('error', 'Item not found.');
            return;
        }
        
        $this->signingItemId = $itemId;
        $this->signingStudent = $item->request->student;
        $this->signAction = $action;
        $this->remarks = '';
        $this->showSignModal = true;
    }
    
    public function viewSubmissions($itemId)
    {
        $item = ClearanceItem::with('request.student.studentDetail')->find($itemId);
        
        if (!$item) {
            session()->flash('error', 'Item not found.');
            return;
        }
        
        $this->viewingItemId = $itemId;
        $studentDetail = $item->request->student->studentDetail;
        $this->viewingStudentName = $studentDetail ? $studentDetail->first_name . ' ' . $studentDetail->last_name : 'Unknown';
        
        // Get all submissions for this clearance item
        $this->studentSubmissions = RequirementSubmission::with('requirement')
            ->where('clearance_item_id', $itemId)
            ->get()
            ->map(function($submission) {
                return [
                    'id' => $submission->id,
                    'requirement_name' => $submission->requirement->name ?? 'Unknown',
                    'requirement_description' => $submission->requirement->description ?? '',
                    'file_path' => $submission->file_path,
                    'notes' => $submission->notes,
                    'status' => $submission->status,
                    'submitted_at' => $submission->created_at->format('M d, Y H:i'),
                ];
            })
            ->toArray();
        
        $this->showSubmissionsModal = true;
    }
    
    public function sign()
    {
        // Validate remarks for rejection
        if ($this->signAction === 'reject' && empty(trim($this->remarks))) {
            session()->flash('error', 'Please provide a reason for rejection.');
            return;
        }
        
        $item = ClearanceItem::find($this->signingItemId);
        
        if (!$item) {
            session()->flash('error', 'Item not found.');
            $this->showSignModal = false;
            return;
        }
        
        $status = $this->signAction === 'approve' ? 'approved' : 'rejected';
        
        // When declining an already-approved item, bypass the prerequisite check
        $skipDependencyCheck = ($item->status === 'approved' && $status === 'rejected');
        
        $resolver = new ClearanceResolver();
        $result = $resolver->signItem($item, Auth::user(), $status, $this->remarks ?: null, $skipDependencyCheck);
        
        if ($result['success']) {
            session()->flash('success', $result['message']);
        } else {
            session()->flash('error', $result['message']);
        }
        
        $this->showSignModal = false;
        $this->updateStats();
        $this->loadFilteredItems();
    }
    
    public function openBulkSignModal()
    {
        $this->bulkDepartments = [];
        $this->bulkYearLevels = [];
        $this->bulkRemarks = '';
        $this->bulkMatchCount = 0;
        $this->bulkSignInProgress = false;
        $this->showBulkSignModal = true;
    }
    
    public function updatedBulkDepartments()
    {
        $this->computeBulkMatchCount();
    }
    
    public function updatedBulkYearLevels()
    {
        $this->computeBulkMatchCount();
    }
    
    protected function computeBulkMatchCount()
    {
        if (!$this->activePeriod || !$this->selectedEntity) {
            $this->bulkMatchCount = 0;
            return;
        }
        
        if (empty($this->bulkDepartments) && empty($this->bulkYearLevels)) {
            $this->bulkMatchCount = 0;
            return;
        }
        
        $query = ClearanceItem::where('signable_type', $this->selectedEntityType)
            ->where('signable_id', $this->selectedEntity['id'])
            ->where('status', 'pending')
            ->whereHas('request', function($q) {
                $q->where('period_id', $this->activePeriod->id);
            })
            ->whereHas('request.student.studentDetail', function($q) {
                if (!empty($this->bulkDepartments)) {
                    $q->whereIn('department_id', $this->bulkDepartments);
                }
                if (!empty($this->bulkYearLevels)) {
                    $q->whereIn('year_level', $this->bulkYearLevels);
                }
            });
        
        $this->bulkMatchCount = $query->count();
    }
    
    public function bulkSign()
    {
        if (!$this->activePeriod || !$this->selectedEntity) {
            session()->flash('error', 'No active period or entity selected.');
            return;
        }
        
        if (empty($this->bulkDepartments) && empty($this->bulkYearLevels)) {
            session()->flash('error', 'Please select at least one department or year level.');
            return;
        }
        
        $this->bulkSignInProgress = true;
        
        $resolver = new ClearanceResolver();
        $user = Auth::user();
        
        // Get matching pending items
        $items = ClearanceItem::with('request')
            ->where('signable_type', $this->selectedEntityType)
            ->where('signable_id', $this->selectedEntity['id'])
            ->where('status', 'pending')
            ->whereHas('request', function($q) {
                $q->where('period_id', $this->activePeriod->id);
            })
            ->whereHas('request.student.studentDetail', function($q) {
                if (!empty($this->bulkDepartments)) {
                    $q->whereIn('department_id', $this->bulkDepartments);
                }
                if (!empty($this->bulkYearLevels)) {
                    $q->whereIn('year_level', $this->bulkYearLevels);
                }
            })
            ->get();
        
        $approved = 0;
        $skipped = 0;
        
        foreach ($items as $item) {
            $result = $resolver->signItem($item, $user, 'approved', $this->bulkRemarks ?: null);
            if ($result['success']) {
                $approved++;
            } else {
                $skipped++;
            }
        }
        
        $this->bulkSignInProgress = false;
        $this->showBulkSignModal = false;
        
        $message = "Bulk sign complete: {$approved} approved";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped (blocked by prerequisites)";
        }
        
        session()->flash('success', $message);
        $this->updateStats();
        $this->loadFilteredItems();
    }
    
    public function render()
    {
        return view('livewire.clearance.signatory-dashboard');
    }
}
