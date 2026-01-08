<?php

namespace App\Livewire\Department;

use App\Models\Department;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class DepartmentOverview extends Component
{
    public $department;
    public $departmentId;

    public function mount($departmentId)
    {
        $this->departmentId = $departmentId;
        $this->department = Department::with(['manager.staffDetail', 'students'])
            ->findOrFail($departmentId);

        // Check if user has access
        $this->authorizeAccess();
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

    public function render()
    {
        return view('livewire.department.department-overview');
    }
}