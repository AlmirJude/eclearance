<?php

namespace App\Livewire\Club;

use Livewire\Component;
use App\Models\Club;
use App\Models\ClearanceRequirement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageRequirements extends Component
{
    public $club;
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
        $this->club = Club::findOrFail($id);
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
        
        // Check if user is a signatory for this club
        $isSignatory = DB::table('club_signatories')
            ->where('club_id', $this->club->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
        
        if (!$isSignatory) {
            abort(403, 'You are not authorized to manage requirements for this club.');
        }
    }

    public function loadRequirements()
    {
        $this->requirements = ClearanceRequirement::where('requirable_type', 'App\\Models\\Club')
            ->where('requirable_id', $this->club->id)
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
            'requirable_type' => 'App\\Models\\Club',
            'requirable_id' => $this->club->id,
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
    
    public function render()
    {
        return view('livewire.club.manage-requirements');
    }
}
