<?php

namespace App\Livewire\Clearance;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ClearanceItem;
use App\Models\ClearanceRequirement;
use App\Models\RequirementSubmission;
use App\Models\HomeroomAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SubmitRequirements extends Component
{
    use WithFileUploads;
    
    public $clearanceItem;
    public $requirements = [];
    public $submissions = [];
    public $entityName = '';
    public $entityType = '';
    
    // Upload modal
    public $showModal = false;
    public $selectedRequirement = null;
    public $uploadFile = null;
    public $notes = '';
    
    public function mount($itemId)
    {
        $this->clearanceItem = ClearanceItem::with('request')->findOrFail($itemId);
        
        // Verify this belongs to the current user
        if ($this->clearanceItem->request->student_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        
        $this->loadRequirements();
    }
    
    public function loadRequirements()
    {
        $student = Auth::user();
        
        // Handle special signable types
        $signableType = $this->clearanceItem->signable_type;
        $signableId = $this->clearanceItem->signable_id;
        
        // Map special types to actual model classes
        if ($signableType === 'homeroom_adviser') {
            $signable = HomeroomAssignment::with('department')->find($signableId);
            if ($signable) {
                $yearLevels = $signable->year_levels ?? [];
                $yearLabel = !empty($yearLevels) ? 'Year ' . implode(', ', $yearLevels) : 'All Years';
                $section = $signable->section ? ' - Section ' . $signable->section : '';
                $this->entityName = ($signable->department->name ?? 'Homeroom') . ' - ' . $yearLabel . $section;
            } else {
                $this->entityName = 'Homeroom';
            }
            $this->entityType = 'Homeroom';
            $requirableType = 'App\\Models\\HomeroomAssignment';
        } else {
            // Standard model types (Club, Office, Department, etc.)
            $signable = $signableType::find($signableId);
            $this->entityName = $signable->name ?? 'Unknown';
            $this->entityType = class_basename($signableType);
            $requirableType = $signableType;
        }
        
        // Get requirements for this entity
        $allRequirements = ClearanceRequirement::where('requirable_type', $requirableType)
            ->where('requirable_id', $signableId)
            ->where('is_active', true)
            ->get();
        
        // Filter by student's year level and department
        $this->requirements = $allRequirements->filter(function($req) use ($student) {
            return $req->appliesToStudent($student);
        })->values();
        
        // Load existing submissions
        $this->submissions = RequirementSubmission::where('clearance_item_id', $this->clearanceItem->id)
            ->where('student_id', Auth::id())
            ->get()
            ->keyBy('requirement_id')
            ->toArray();
    }
    
    public function openUploadModal($requirementId)
    {
        $this->selectedRequirement = ClearanceRequirement::find($requirementId);
        $this->uploadFile = null;
        $this->notes = '';
        
        // Pre-fill notes if already submitted
        if (isset($this->submissions[$requirementId])) {
            $this->notes = $this->submissions[$requirementId]['notes'] ?? '';
        }
        
        $this->showModal = true;
    }
    
    public function submit()
    {
        if (!$this->selectedRequirement) {
            return;
        }
        
        $rules = ['notes' => 'nullable|string|max:500'];
        
        if ($this->selectedRequirement->type === 'document' || $this->selectedRequirement->type === 'form') {
            $rules['uploadFile'] = 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx';
        }
        
        $this->validate($rules);
        
        $filePath = null;
        if ($this->uploadFile) {
            $filePath = $this->uploadFile->store('requirement-submissions/' . Auth::id(), 'public');
        }
        
        // Create or update submission
        RequirementSubmission::updateOrCreate(
            [
                'requirement_id' => $this->selectedRequirement->id,
                'clearance_item_id' => $this->clearanceItem->id,
                'student_id' => Auth::id(),
            ],
            [
                'file_path' => $filePath ?? ($this->submissions[$this->selectedRequirement->id]['file_path'] ?? null),
                'notes' => $this->notes,
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_remarks' => null,
            ]
        );
        
        session()->flash('success', 'Requirement submitted successfully.');
        $this->showModal = false;
        $this->loadRequirements();
    }
    
    public function getSubmissionStatus($requirementId)
    {
        if (!isset($this->submissions[$requirementId])) {
            return 'not_submitted';
        }
        return $this->submissions[$requirementId]['status'];
    }
    
    public function render()
    {
        return view('livewire.clearance.submit-requirements');
    }
}
