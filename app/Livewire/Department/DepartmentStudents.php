<?php

namespace App\Livewire\Department;

use App\Models\Department;
use App\Models\StudentDetail;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class DepartmentStudents extends Component
{
    public $department;
    public $departmentId;
    public $selectedStudent;
    public $showViewModal = false;

    public function mount($departmentId)
    {
        $this->departmentId = $departmentId;
        $this->department = Department::findOrFail($departmentId);

        // Check access
        $this->authorizeAccess();
    }

    protected function authorizeAccess()
    {
        $user = Auth::user();
        
        if ($user->role === 'superadmin') {
            return;
        }

        $isManager = $this->department->manager_id === $user->id;
        $isSignatory = $user->departmentSignatories->contains($this->department->id);

        if (!$isManager && !$isSignatory) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function view($id){
        $this->selectedStudent = StudentDetail::with('user')->find($id);
        $this->showViewModal = true;
        $this->dispatch('open-modal', name: 'view-student-modal');
    }

    public function closeModalViewStudent(){
        $this->showViewModal = false;
        $this->selectedStudent = null;
        $this->dispatch('close-modal', name: 'view-student-modal');
    }

    public function render()
    {
        $students = StudentDetail::where('department_id', $this->departmentId)
            ->with('user')
            ->orderBy('year_level', 'ASC')
            ->get();

        return view('livewire.department.department-students', compact('students'));
    }
}