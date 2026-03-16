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
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 2. Add this attribute to force the wrapper
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

    // Submission Review Modal
    public $showReviewModal = false;
    public $reviewingSubmissionId = null;
    public $reviewingRequirementName = '';
    public $reviewAction = 'approve'; // 'approve' or 'reject'
    public $reviewRemarks = '';
    
    // Status filter
    public $statusFilter = 'pending'; // 'pending', 'approved', 'rejected'
    
    // Department & Year Level filters
    public $departmentFilter = '';
    public $yearLevelFilter = '';
    public $availableDepartments = [];

    // Signatory restrictions (for office signatories assigned to specific dept/year)
    public $signatoryDeptRestrictions = [];
    public $signatoryYearRestrictions = [];
    public $hasSignatoryRestrictions = false;
    public $availableYearLevels = [1, 2, 3, 4];

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
    
    protected function loadSignatoryRestrictions()
    {
        $this->signatoryDeptRestrictions = [];
        $this->signatoryYearRestrictions = [];
        $this->hasSignatoryRestrictions  = false;
        $this->availableYearLevels       = [1, 2, 3, 4];

        // Only restrict for office signatories
        if ($this->selectedEntityType !== 'App\\Models\\Office') {
            return;
        }

        $user = Auth::user();

        // Admins and superadmins see everything
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return;
        }

        // Office managers see everything
        $office = \App\Models\Office::find($this->selectedEntity['id']);
        if ($office && $office->manager_id === $user->id) {
            return;
        }

        // Fetch this user's signatory record for the office
        $signatory = DB::table('office_signatories')
            ->where('office_id', $this->selectedEntity['id'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$signatory) {
            return;
        }

        $departments = json_decode($signatory->departments, true) ?? [];
        $yearLevels  = json_decode($signatory->year_levels, true)  ?? [];

        if (!empty($departments) || !empty($yearLevels)) {
            $this->signatoryDeptRestrictions = array_map('intval', $departments);
            $this->signatoryYearRestrictions = array_map('intval', $yearLevels);
            $this->hasSignatoryRestrictions  = true;

            if (!empty($this->signatoryYearRestrictions)) {
                $this->availableYearLevels = $this->signatoryYearRestrictions;
            }
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
        $this->loadSignatoryRestrictions();
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
        $departmentIds = ClearanceItem::whereHas('request', function($q) {
                $q->where('period_id', $this->activePeriod->id);
            })
            ->where('signable_type', $this->selectedEntityType)
            ->where('signable_id', $this->selectedEntity['id'])
            ->whereHas('request.student.studentDetail', function($q) {
                if ($this->hasSignatoryRestrictions) {
                    if (!empty($this->signatoryDeptRestrictions)) {
                        $q->whereIn('department_id', $this->signatoryDeptRestrictions);
                    }
                    if (!empty($this->signatoryYearRestrictions)) {
                        $q->whereIn('year_level', $this->signatoryYearRestrictions);
                    }
                }
            })
            ->with('request.student.studentDetail.department')
            ->get()
            ->pluck('request.student.studentDetail.department_id')
            ->unique()
            ->filter();
        
        $this->availableDepartments = \App\Models\Department::whereIn('id', $departmentIds)
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
                // Apply signatory scope restrictions first
                if ($this->hasSignatoryRestrictions) {
                    if (!empty($this->signatoryDeptRestrictions)) {
                        $q->whereIn('department_id', $this->signatoryDeptRestrictions);
                    }
                    if (!empty($this->signatoryYearRestrictions)) {
                        $q->whereIn('year_level', $this->signatoryYearRestrictions);
                    }
                }
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
        })->sortByDesc('can_sign')->values()->toArray();
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

        // Apply signatory scope restrictions
        if ($this->hasSignatoryRestrictions) {
            $query->whereHas('request.student.studentDetail', function($q) {
                if (!empty($this->signatoryDeptRestrictions)) {
                    $q->whereIn('department_id', $this->signatoryDeptRestrictions);
                }
                if (!empty($this->signatoryYearRestrictions)) {
                    $q->whereIn('year_level', $this->signatoryYearRestrictions);
                }
            });
        }
        
        $this->pendingCount  = (clone $query)->where('status', 'pending')->count();
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
        $this->studentSubmissions = RequirementSubmission::with(['requirement', 'reviewer.staffDetail'])
            ->where('clearance_item_id', $itemId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function($submission) {
                $reviewerName = null;
                if ($submission->reviewer) {
                    $reviewerName = $submission->reviewer->staffDetail
                        ? trim(($submission->reviewer->staffDetail->first_name ?? '') . ' ' . ($submission->reviewer->staffDetail->last_name ?? ''))
                        : $submission->reviewer->email;
                }

                return [
                    'id' => $submission->id,
                    'requirement_name' => $submission->requirement->name ?? 'Unknown',
                    'requirement_description' => $submission->requirement->description ?? '',
                    'file_path' => $submission->file_path,
                    'notes' => $submission->notes,
                    'status' => $submission->status,
                    'submitted_at' => $submission->created_at->format('M d, Y H:i'),
                    'reviewed_at' => $submission->reviewed_at?->format('M d, Y H:i'),
                    'reviewed_by' => $reviewerName,
                    'review_remarks' => $submission->review_remarks,
                ];
            })
            ->toArray();
        
        $this->showSubmissionsModal = true;
    }

    protected function canReviewSubmission(RequirementSubmission $submission): bool
    {
        $item = $submission->clearanceItem;

        if (!$item || !$this->selectedEntity) {
            return false;
        }

        // Prevent cross-entity/tampered review attempts
        if ($item->signable_type !== $this->selectedEntityType || (int) $item->signable_id !== (int) $this->selectedEntity['id']) {
            return false;
        }

        $resolver = new ClearanceResolver();
        if (!$resolver->isAuthorizedSignatory(Auth::user(), $item->signable_type, (int) $item->signable_id)) {
            return false;
        }

        if (!$this->hasSignatoryRestrictions) {
            return true;
        }

        $studentDetail = $item->request->student->studentDetail ?? null;
        if (!$studentDetail) {
            return false;
        }

        if (!empty($this->signatoryDeptRestrictions) && !in_array((int) $studentDetail->department_id, $this->signatoryDeptRestrictions, true)) {
            return false;
        }

        if (!empty($this->signatoryYearRestrictions) && !in_array((int) $studentDetail->year_level, $this->signatoryYearRestrictions, true)) {
            return false;
        }

        return true;
    }

    public function openReviewModal($submissionId, $action = 'approve')
    {
        $submission = RequirementSubmission::with(['requirement', 'clearanceItem.request.student.studentDetail'])->find($submissionId);

        if (!$submission) {
            session()->flash('error', 'Submission not found.');
            return;
        }

        if (!$this->canReviewSubmission($submission)) {
            session()->flash('error', 'You are not authorized to review this submission.');
            return;
        }

        $this->reviewingSubmissionId = $submission->id;
        $this->reviewingRequirementName = $submission->requirement->name ?? 'Requirement';
        $this->reviewAction = $action;
        $this->reviewRemarks = '';
        $this->showReviewModal = true;
    }

    public function reviewSubmission()
    {
        if (!$this->reviewingSubmissionId) {
            return;
        }

        if ($this->reviewAction === 'reject' && empty(trim($this->reviewRemarks))) {
            session()->flash('error', 'Please provide a reason for rejection.');
            return;
        }

        $submission = RequirementSubmission::with(['requirement', 'clearanceItem.request.student.studentDetail'])->find($this->reviewingSubmissionId);

        if (!$submission) {
            session()->flash('error', 'Submission not found.');
            $this->showReviewModal = false;
            return;
        }

        if (!$this->canReviewSubmission($submission)) {
            session()->flash('error', 'You are not authorized to review this submission.');
            $this->showReviewModal = false;
            return;
        }

        $status = $this->reviewAction === 'approve' ? 'approved' : 'rejected';

        $submission->update([
            'status' => $status,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_remarks' => $this->reviewRemarks ?: null,
        ]);

        $this->showReviewModal = false;
        $this->reviewingSubmissionId = null;
        $this->reviewingRequirementName = '';
        $this->reviewRemarks = '';

        if ($this->viewingItemId) {
            $this->viewSubmissions($this->viewingItemId);
        }

        $this->loadFilteredItems();

        session()->flash('success', $status === 'approved' ? 'Requirement approved successfully.' : 'Requirement rejected.');
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
                // Apply signatory scope restrictions first
                if ($this->hasSignatoryRestrictions) {
                    if (!empty($this->signatoryDeptRestrictions)) {
                        $q->whereIn('department_id', $this->signatoryDeptRestrictions);
                    }
                    if (!empty($this->signatoryYearRestrictions)) {
                        $q->whereIn('year_level', $this->signatoryYearRestrictions);
                    }
                }
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
                // Apply signatory scope restrictions first
                if ($this->hasSignatoryRestrictions) {
                    if (!empty($this->signatoryDeptRestrictions)) {
                        $q->whereIn('department_id', $this->signatoryDeptRestrictions);
                    }
                    if (!empty($this->signatoryYearRestrictions)) {
                        $q->whereIn('year_level', $this->signatoryYearRestrictions);
                    }
                }
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
