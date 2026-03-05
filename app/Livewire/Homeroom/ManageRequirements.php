<?php

namespace App\Livewire\Homeroom;

use Livewire\Component;
use App\Models\HomeroomAssignment;
use App\Models\ClearanceRequirement;
use Illuminate\Support\Facades\Auth;

class ManageRequirements extends Component
{
    public $assignment;
    public $requirements = [];
    
    // Form fields
    public $showModal = false;
    public $editingId = null;
    public $name = '';
    public $description = '';
    public $type = 'document';
    public $is_required = true;
    
    public $typeOptions = ['document', 'form', 'payment', 'other'];
    
    public function mount($id)
    {
        $this->assignment = HomeroomAssignment::with(['department', 'adviser'])->findOrFail($id);
        $this->authorizeAccess();
        $this->loadRequirements();
    }
    
    protected function authorizeAccess()
    {
        $user = Auth::user();
        
        // Admins always have access
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return;
        }
        
        // Check if user is the homeroom adviser for this assignment
        if ($this->assignment->adviser_id !== $user->id) {
            abort(403, 'You are not authorized to manage requirements for this homeroom assignment.');
        }
        
        // Check if the assignment is active
        if (!$this->assignment->is_active) {
            abort(403, 'This homeroom assignment is no longer active.');
        }
    }

    public function loadRequirements()
    {
        $this->requirements = ClearanceRequirement::where('requirable_type', 'App\\Models\\HomeroomAssignment')
            ->where('requirable_id', $this->assignment->id)
            ->orderBy('name')
            ->get()
            ->toArray();
    }
    
    public function openModal($id = null)
    {
        $this->resetForm();
        
        if ($id) {
            $requirement = ClearanceRequirement::find($id);
            if ($requirement) {
                $this->editingId = $id;
                $this->name = $requirement->name;
                $this->description = $requirement->description;
                $this->type = $requirement->type;
                $this->is_required = $requirement->is_required;
            }
        }
        
        $this->showModal = true;
    }
    
    public function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->type = 'document';
        $this->is_required = true;
    }
    
    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:document,form,payment,other',
        ]);
        
        $data = [
            'requirable_type' => 'App\\Models\\HomeroomAssignment',
            'requirable_id' => $this->assignment->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'is_required' => $this->is_required,
        ];
        
        if ($this->editingId) {
            ClearanceRequirement::where('id', $this->editingId)->update($data);
            session()->flash('success', 'Requirement updated successfully.');
        } else {
            ClearanceRequirement::create($data);
            session()->flash('success', 'Requirement added successfully.');
        }
        
        $this->showModal = false;
        $this->loadRequirements();
    }
    
    public function toggleActive($id)
    {
        $requirement = ClearanceRequirement::find($id);
        if ($requirement) {
            $requirement->is_active = !$requirement->is_active;
            $requirement->save();
            $this->loadRequirements();
        }
    }
    
    public function delete($id)
    {
        ClearanceRequirement::where('id', $id)->delete();
        session()->flash('success', 'Requirement deleted successfully.');
        $this->loadRequirements();
    }
    
    public function getAssignmentLabelProperty()
    {
        $yearLevels = $this->assignment->year_levels ?? [];
        $yearLabel = !empty($yearLevels) ? 'Year ' . implode(', ', $yearLevels) : 'All Years';
        $section = $this->assignment->section ? ' - Section ' . $this->assignment->section : '';
        
        return $this->assignment->department->name . ' - ' . $yearLabel . $section;
    }
    
    public function render()
    {
        return view('livewire.homeroom.manage-requirements');
    }
}
