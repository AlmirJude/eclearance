<?php

namespace App\Services;

use App\Models\User;
use App\Models\Club;
use App\Models\Office;
use App\Models\Department;
use App\Models\ClearanceRequest;
use App\Models\ClearanceItem;
use App\Models\StudentGovernment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ClearanceResolver
{
    /**
     * Signatory type constants for dependency rules
     */
    const TYPE_CLUB = 'App\\Models\\Club';
    const TYPE_OFFICE = 'App\\Models\\Office';
    const TYPE_DEPARTMENT = 'App\\Models\\Department';
    const TYPE_HOMEROOM = 'homeroom_adviser';
    const TYPE_STUDENT_GOVERNMENT = 'App\\Models\\StudentGovernment';
    
    /**
     * Special rule identifiers
     */
    const RULE_ALL_CLUBS = 'all_clubs';
    const RULE_ALL_STUDENT_CLUBS = 'all_student_clubs';
    const RULE_ALL_OFFICES = 'all_offices';
    
    /**
     * Check if a signatory can sign for a student based on dependency rules
     *
     * @param string $signableType The type of signable (Club, Office, Department, etc.)
     * @param int $signableId The ID of the signable entity
     * @param ClearanceRequest $clearanceRequest The clearance request
     * @return array ['can_sign' => bool, 'blocking' => array of blocking items]
     */
    public function canSign(string $signableType, int $signableId, ClearanceRequest $clearanceRequest): array
    {
        $blocking = $this->getBlockingDependencies($signableType, $signableId, $clearanceRequest);
        
        return [
            'can_sign' => $blocking->isEmpty(),
            'blocking' => $blocking->toArray(),
        ];
    }
    
    /**
     * Get dependencies that are blocking a signatory from signing
     *
     * @param string $signableType
     * @param int $signableId
     * @param ClearanceRequest $clearanceRequest
     * @return Collection
     */
    public function getBlockingDependencies(string $signableType, int $signableId, ClearanceRequest $clearanceRequest): Collection
    {
        $blocking = collect();
        $student = User::with('studentDetail', 'clubs')->find($clearanceRequest->student_id);
        
        // Get all dependencies for this signatory type
        $dependencies = DB::table('clearance_dependencies')
            ->where('dependent_type', $signableType)
            ->where('dependent_id', $signableId)
            ->get();
        
        // Also check for special "all" rules (prerequisite_id is null)
        $specialRules = DB::table('clearance_dependencies')
            ->where('dependent_type', $signableType)
            ->where('dependent_id', $signableId)
            ->whereNull('prerequisite_id')
            ->get();
        
        foreach ($dependencies as $dependency) {
            // Handle special rules
            if ($dependency->prerequisite_id === null) {
                $blocking = $blocking->merge(
                    $this->checkSpecialRule($dependency->prerequisite_type, $clearanceRequest, $student)
                );
                continue;
            }
            
            // Check if the prerequisite clearance item is approved
            $isApproved = ClearanceItem::where('request_id', $clearanceRequest->id)
                ->where('signable_type', $dependency->prerequisite_type)
                ->where('signable_id', $dependency->prerequisite_id)
                ->where('status', 'approved')
                ->exists();
            
            if (!$isApproved) {
                $blocking->push([
                    'type' => $dependency->prerequisite_type,
                    'id' => $dependency->prerequisite_id,
                    'name' => $this->getSignableName($dependency->prerequisite_type, $dependency->prerequisite_id),
                ]);
            }
        }
        
        return $blocking;
    }
    
    /**
     * Handle special dependency rules like "all_clubs" or "homeroom_adviser"
     *
     * @param string $ruleType
     * @param ClearanceRequest $clearanceRequest
     * @param User $student
     * @return Collection
     */
    protected function checkSpecialRule(string $ruleType, ClearanceRequest $clearanceRequest, User $student): Collection
    {
        $blocking = collect();
        
        switch ($ruleType) {
            case self::RULE_ALL_CLUBS:
            case self::RULE_ALL_STUDENT_CLUBS:
                // Get all clubs the student is a member of
                $studentClubs = $student->clubs;
                
                foreach ($studentClubs as $club) {
                    $isApproved = ClearanceItem::where('request_id', $clearanceRequest->id)
                        ->where('signable_type', self::TYPE_CLUB)
                        ->where('signable_id', $club->id)
                        ->where('status', 'approved')
                        ->exists();
                    
                    if (!$isApproved) {
                        $blocking->push([
                            'type' => self::TYPE_CLUB,
                            'id' => $club->id,
                            'name' => $club->name,
                        ]);
                    }
                }
                break;
                
            case self::TYPE_HOMEROOM:
                // Check if homeroom adviser has signed
                $studentDeptId = $student->studentDetail->department_id ?? null;
                $studentYearLevel = $student->studentDetail->year_level ?? null;
                
                if ($studentDeptId && $studentYearLevel) {
                    // Find homeroom assignment for this student
                    $homeroomAssignment = DB::table('homeroom_assignments')
                        ->where('department_id', $studentDeptId)
                        ->where('is_active', true)
                        ->whereRaw('JSON_CONTAINS(year_levels, ?)', [json_encode((string) $studentYearLevel)])
                        ->first();
                    
                    if ($homeroomAssignment) {
                        $isApproved = ClearanceItem::where('request_id', $clearanceRequest->id)
                            ->where('signable_type', self::TYPE_HOMEROOM)
                            ->where('signable_id', $homeroomAssignment->id)
                            ->where('status', 'approved')
                            ->exists();
                        
                        if (!$isApproved) {
                            $adviser = User::find($homeroomAssignment->adviser_id);
                            $blocking->push([
                                'type' => self::TYPE_HOMEROOM,
                                'id' => $homeroomAssignment->id,
                                'name' => 'Homeroom Adviser: ' . ($adviser ? $adviser->fullname : 'Unknown'),
                            ]);
                        }
                    }
                }
                break;
                
            case self::TYPE_STUDENT_GOVERNMENT:
                // Check if student government has signed
                $activeGov = StudentGovernment::where('is_active', true)->first();
                
                if ($activeGov) {
                    $isApproved = ClearanceItem::where('request_id', $clearanceRequest->id)
                        ->where('signable_type', self::TYPE_STUDENT_GOVERNMENT)
                        ->where('signable_id', $activeGov->id)
                        ->where('status', 'approved')
                        ->exists();
                    
                    if (!$isApproved) {
                        $blocking->push([
                            'type' => self::TYPE_STUDENT_GOVERNMENT,
                            'id' => $activeGov->id,
                            'name' => $activeGov->name,
                        ]);
                    }
                }
                break;
                
            case self::RULE_ALL_OFFICES:
                // Check if all offices in student's clearance are approved
                $officeItems = ClearanceItem::where('request_id', $clearanceRequest->id)
                    ->where('signable_type', self::TYPE_OFFICE)
                    ->get();
                
                foreach ($officeItems as $officeItem) {
                    if ($officeItem->status !== 'approved') {
                        $office = Office::find($officeItem->signable_id);
                        $blocking->push([
                            'type' => self::TYPE_OFFICE,
                            'id' => $officeItem->signable_id,
                            'name' => $office?->name ?? 'Unknown Office',
                        ]);
                    }
                }
                break;
        }
        
        return $blocking;
    }
    
    /**
     * Get all signatories that can currently sign for a student's clearance request
     *
     * @param ClearanceRequest $clearanceRequest
     * @return Collection
     */
    public function getAvailableSignatories(ClearanceRequest $clearanceRequest): Collection
    {
        $available = collect();
        
        // Get all pending clearance items for this request
        $pendingItems = ClearanceItem::where('request_id', $clearanceRequest->id)
            ->where('status', 'pending')
            ->get();
        
        foreach ($pendingItems as $item) {
            $result = $this->canSign($item->signable_type, $item->signable_id, $clearanceRequest);
            
            if ($result['can_sign']) {
                $available->push([
                    'item_id' => $item->id,
                    'type' => $item->signable_type,
                    'id' => $item->signable_id,
                    'name' => $this->getSignableName($item->signable_type, $item->signable_id),
                ]);
            }
        }
        
        return $available;
    }
    
    /**
     * Get all clearance items with their current status and dependency info
     *
     * @param ClearanceRequest $clearanceRequest
     * @return Collection
     */
    public function getClearanceStatus(ClearanceRequest $clearanceRequest): Collection
    {
        $items = ClearanceItem::where('request_id', $clearanceRequest->id)
            ->with('signer')
            ->get();
        
        return $items->map(function ($item) use ($clearanceRequest) {
            $result = $this->canSign($item->signable_type, $item->signable_id, $clearanceRequest);
            
            return [
                'id' => $item->id,
                'type' => $item->signable_type,
                'signable_id' => $item->signable_id,
                'name' => $this->getSignableName($item->signable_type, $item->signable_id),
                'status' => $item->status,
                'can_sign' => $result['can_sign'],
                'blocking' => $result['blocking'],
                'signed_by' => $item->signer?->fullname,
                'signed_at' => $item->signed_at,
                'remarks' => $item->remarks,
            ];
        });
    }
    
    /**
     * Generate clearance items for a student based on their memberships
     *
     * @param ClearanceRequest $clearanceRequest
     * @return void
     */
    public function generateClearanceItems(ClearanceRequest $clearanceRequest): void
    {
        $student = User::with(['studentDetail', 'clubs'])->find($clearanceRequest->student_id);
        $items = [];
        
        // 1. Add clearance items for all clubs the student is a member of
        foreach ($student->clubs as $club) {
            $items[] = [
                'request_id' => $clearanceRequest->id,
                'signable_type' => self::TYPE_CLUB,
                'signable_id' => $club->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // 2. Add homeroom adviser clearance item
        $studentDeptId = $student->studentDetail->department_id ?? null;
        $studentYearLevel = $student->studentDetail->year_level ?? null;
        
        if ($studentDeptId && $studentYearLevel) {
            $homeroomAssignment = DB::table('homeroom_assignments')
                ->where('department_id', $studentDeptId)
                ->where('is_active', true)
                ->whereRaw('JSON_CONTAINS(year_levels, ?)', [json_encode((string) $studentYearLevel)])
                ->first();
            
            if ($homeroomAssignment) {
                $items[] = [
                    'request_id' => $clearanceRequest->id,
                    'signable_type' => self::TYPE_HOMEROOM,
                    'signable_id' => $homeroomAssignment->id,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        // 3. Add student government clearance item
        $studentGov = StudentGovernment::where('is_active', true)->first();
        if ($studentGov) {
            $items[] = [
                'request_id' => $clearanceRequest->id,
                'signable_type' => self::TYPE_STUDENT_GOVERNMENT,
                'signable_id' => $studentGov->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // 4. Add department clearance item (Dean/Department Head)
        if ($studentDeptId) {
            $items[] = [
                'request_id' => $clearanceRequest->id,
                'signable_type' => self::TYPE_DEPARTMENT,
                'signable_id' => $studentDeptId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // 5. Add office clearance items (SDO, Registrar, Library, etc.)
        $offices = Office::where('is_required', true)->get();
        foreach ($offices as $office) {
            // Check if this office applies to the student's department/year level
            $officeSignatories = DB::table('office_signatories')
                ->where('office_id', $office->id)
                ->where('is_active', true)
                ->get();
            
            $appliesToStudent = false;
            foreach ($officeSignatories as $signatory) {
                $depts = json_decode($signatory->departments ?? '[]', true);
                $yearLevels = json_decode($signatory->year_levels ?? '[]', true);
                
                // If no restrictions, applies to all
                if (empty($depts) && empty($yearLevels)) {
                    $appliesToStudent = true;
                    break;
                }
                
                // Check department match
                $deptMatch = empty($depts) || in_array($studentDeptId, $depts);
                
                // Check year level match
                $yearMatch = empty($yearLevels) || in_array($studentYearLevel, $yearLevels);
                
                if ($deptMatch && $yearMatch) {
                    $appliesToStudent = true;
                    break;
                }
            }
            
            if ($appliesToStudent) {
                $items[] = [
                    'request_id' => $clearanceRequest->id,
                    'signable_type' => self::TYPE_OFFICE,
                    'signable_id' => $office->id,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        // Insert all items
        if (!empty($items)) {
            ClearanceItem::insert($items);
        }
    }
    
    /**
     * Sign a clearance item
     *
     * @param ClearanceItem $item
     * @param User $signatory
     * @param string $status 'approved' or 'rejected'
     * @param string|null $remarks
     * @return array ['success' => bool, 'message' => string]
     */
    public function signItem(ClearanceItem $item, User $signatory, string $status, ?string $remarks = null, bool $skipDependencyCheck = false): array
    {
        $clearanceRequest = ClearanceRequest::find($item->request_id);
        
        // Check if signatory can sign (skip when overriding an already-approved item)
        if (!$skipDependencyCheck) {
            $result = $this->canSign($item->signable_type, $item->signable_id, $clearanceRequest);
            
            if (!$result['can_sign']) {
                $blockingNames = collect($result['blocking'])->pluck('name')->implode(', ');
                return [
                    'success' => false,
                    'message' => "Cannot sign. Waiting for: {$blockingNames}",
                ];
            }
        }
        
        // Verify signatory has permission to sign for this entity
        if (!$this->isAuthorizedSignatory($signatory, $item->signable_type, $item->signable_id)) {
            return [
                'success' => false,
                'message' => 'You are not authorized to sign for this entity.',
            ];
        }
        
        // Update the clearance item
        $item->update([
            'status' => $status,
            'signed_by' => $signatory->id,
            'signed_at' => now(),
            'remarks' => $remarks,
        ]);
        
        // Check if all items are approved, update request status
        $this->updateRequestStatus($clearanceRequest);
        
        return [
            'success' => true,
            'message' => $status === 'approved' ? 'Clearance signed successfully.' : 'Clearance rejected.',
        ];
    }
    
    /**
     * Check if a user is authorized to sign for a specific entity
     *
     * @param User $user
     * @param string $signableType
     * @param int $signableId
     * @return bool
     */
    public function isAuthorizedSignatory(User $user, string $signableType, int $signableId): bool
    {
        switch ($signableType) {
            case self::TYPE_CLUB:
                return DB::table('club_signatories')
                    ->where('user_id', $user->id)
                    ->where('club_id', $signableId)
                    ->where('is_active', true)
                    ->exists();
                    
            case self::TYPE_OFFICE:
                return DB::table('office_signatories')
                    ->where('user_id', $user->id)
                    ->where('office_id', $signableId)
                    ->where('is_active', true)
                    ->exists();
                    
            case self::TYPE_DEPARTMENT:
                return DB::table('department_signatories')
                    ->where('user_id', $user->id)
                    ->where('department_id', $signableId)
                    ->where('is_active', true)
                    ->exists();
                    
            case self::TYPE_HOMEROOM:
                return DB::table('homeroom_assignments')
                    ->where('id', $signableId)
                    ->where('adviser_id', $user->id)
                    ->where('is_active', true)
                    ->exists();
                    
            case self::TYPE_STUDENT_GOVERNMENT:
                return DB::table('student_government_officers')
                    ->where('student_government_id', $signableId)
                    ->where('user_id', $user->id)
                    ->where('can_sign', true)
                    ->where('is_active', true)
                    ->exists();
        }
        
        return false;
    }
    
    /**
     * Update clearance request status based on item statuses
     *
     * @param ClearanceRequest $request
     * @return void
     */
    protected function updateRequestStatus(ClearanceRequest $request): void
    {
        $items = ClearanceItem::where('request_id', $request->id)->get();
        
        $allApproved = $items->every(fn ($item) => $item->status === 'approved');
        $anyRejected = $items->contains(fn ($item) => $item->status === 'rejected');
        $anyApproved = $items->contains(fn ($item) => $item->status === 'approved');
        
        if ($allApproved) {
            $request->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } elseif ($anyApproved || $anyRejected) {
            $request->update(['status' => 'in_progress']);
        }
    }
    
    /**
     * Get the name of a signable entity
     *
     * @param string $type
     * @param int $id
     * @return string
     */
    protected function getSignableName(string $type, int $id): string
    {
        switch ($type) {
            case self::TYPE_CLUB:
                return Club::find($id)?->name ?? 'Unknown Club';
            case self::TYPE_OFFICE:
                return Office::find($id)?->name ?? 'Unknown Office';
            case self::TYPE_DEPARTMENT:
                return Department::find($id)?->name ?? 'Unknown Department';
            case self::TYPE_HOMEROOM:
                $assignment = DB::table('homeroom_assignments')
                    ->join('users', 'homeroom_assignments.adviser_id', '=', 'users.id')
                    ->leftJoin('staff_details', 'users.id', '=', 'staff_details.user_id')
                    ->where('homeroom_assignments.id', $id)
                    ->select('staff_details.first_name', 'staff_details.last_name')
                    ->first();
                return $assignment 
                    ? 'Homeroom: ' . $assignment->first_name . ' ' . $assignment->last_name
                    : 'Homeroom Adviser';
            case self::TYPE_STUDENT_GOVERNMENT:
                return StudentGovernment::find($id)?->name ?? 'Student Government';
            default:
                return 'Unknown';
        }
    }
    
    /**
     * Setup default dependency rules
     * Call this once during initial setup
     *
     * @return void
     */
    public function setupDefaultDependencies(): void
    {
        // Clear existing dependencies
        DB::table('clearance_dependencies')->truncate();
        
        // Get SDO office (Student Development Office)
        $sdo = Office::where('name', 'like', '%Student Development Office%')
            ->orWhere('name', 'like', '%SDO%')
            ->first();
        
        if ($sdo) {
            // SDO requires all student clubs
            DB::table('clearance_dependencies')->insert([
                'dependent_type' => self::TYPE_OFFICE,
                'dependent_id' => $sdo->id,
                'prerequisite_type' => self::RULE_ALL_STUDENT_CLUBS,
                'prerequisite_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // SDO requires homeroom adviser
            DB::table('clearance_dependencies')->insert([
                'dependent_type' => self::TYPE_OFFICE,
                'dependent_id' => $sdo->id,
                'prerequisite_type' => self::TYPE_HOMEROOM,
                'prerequisite_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Get all departments
        $departments = Department::all();
        
        foreach ($departments as $dept) {
            // Dean/Department requires all clubs
            DB::table('clearance_dependencies')->insert([
                'dependent_type' => self::TYPE_DEPARTMENT,
                'dependent_id' => $dept->id,
                'prerequisite_type' => self::RULE_ALL_STUDENT_CLUBS,
                'prerequisite_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Dean requires homeroom adviser
            DB::table('clearance_dependencies')->insert([
                'dependent_type' => self::TYPE_DEPARTMENT,
                'dependent_id' => $dept->id,
                'prerequisite_type' => self::TYPE_HOMEROOM,
                'prerequisite_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Dean requires student government
            DB::table('clearance_dependencies')->insert([
                'dependent_type' => self::TYPE_DEPARTMENT,
                'dependent_id' => $dept->id,
                'prerequisite_type' => self::TYPE_STUDENT_GOVERNMENT,
                'prerequisite_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Dean requires all offices
            DB::table('clearance_dependencies')->insert([
                'dependent_type' => self::TYPE_DEPARTMENT,
                'dependent_id' => $dept->id,
                'prerequisite_type' => self::RULE_ALL_OFFICES,
                'prerequisite_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Student Government requires all student clubs
        $studentGov = StudentGovernment::where('is_active', true)->first();
        if ($studentGov) {
            DB::table('clearance_dependencies')->insert([
                'dependent_type' => self::TYPE_STUDENT_GOVERNMENT,
                'dependent_id' => $studentGov->id,
                'prerequisite_type' => self::RULE_ALL_STUDENT_CLUBS,
                'prerequisite_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Homeroom Advisers require all student clubs
        $homeroomAssignments = DB::table('homeroom_assignments')
            ->where('is_active', true)
            ->get();
        
        foreach ($homeroomAssignments as $assignment) {
            DB::table('clearance_dependencies')->insert([
                'dependent_type' => self::TYPE_HOMEROOM,
                'dependent_id' => $assignment->id,
                'prerequisite_type' => self::RULE_ALL_STUDENT_CLUBS,
                'prerequisite_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
