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
    public $searchQuery = '';
    public $yearLevelFilter = '';
    public $availableYearLevels = [1, 2, 3, 4];

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

    public function clearFilters()
    {
        $this->searchQuery = '';
        $this->yearLevelFilter = '';
    }

    public function render()
    {
        $students = StudentDetail::where('department_id', $this->departmentId)
            ->with('user')
            ->when($this->yearLevelFilter !== '', function ($query) {
                $query->where('year_level', $this->yearLevelFilter);
            })
            ->when($this->searchQuery, function ($query) {
                $search = '%' . $this->searchQuery . '%';

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('student_id', 'like', $search)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$search]);
                });
            })
            ->orderBy('year_level', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->orderBy('first_name', 'ASC')
            ->get();

        return view('livewire.department.department-students', compact('students'));
    }
}