<?php

namespace App\Livewire\StudentGovernment;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\StudentGovernment;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class Index extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editingId = null;
    public $name = '';
    public $abbreviation = '';
    public $description = '';
    public $departmentId = null;
    public $academicYear = '';
    public $adviserId = null;
    public $isActive = true;
    
    public $staffSearch = '';
    public $availableStaff = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'abbreviation' => 'nullable|string|max:20',
        'description' => 'nullable|string',
        'departmentId' => 'nullable|exists:departments,id',
        'academicYear' => 'required|string|max:20',
        'adviserId' => 'nullable|exists:users,id',
        'isActive' => 'boolean',
    ];

    public function updatedStaffSearch()
    {
        if (strlen($this->staffSearch) < 2) {
            $this->availableStaff = [];
            return;
        }

        $this->availableStaff = DB::table('users')
            ->leftJoin('staff_details', 'users.id', '=', 'staff_details.user_id')
            ->where('users.role', 'staff')
            ->where(function($query) {
                $query->where('staff_details.first_name', 'like', '%' . $this->staffSearch . '%')
                    ->orWhere('staff_details.last_name', 'like', '%' . $this->staffSearch . '%')
                    ->orWhere('staff_details.employee_id', 'like', '%' . $this->staffSearch . '%');
            })
            ->select(
                'users.id',
                'staff_details.first_name',
                'staff_details.last_name',
                'users.email',
                'staff_details.employee_id',
                'staff_details.position'
            )
            ->limit(10)
            ->get();
    }

    public function openModal($id = null)
    {
        $this->resetForm();
        $this->editingId = $id;

        if ($id) {
            $sg = StudentGovernment::find($id);
            $this->name = $sg->name;
            $this->abbreviation = $sg->abbreviation;
            $this->description = $sg->description;
            $this->departmentId = $sg->department_id;
            $this->academicYear = $sg->academic_year;
            $this->adviserId = $sg->adviser_id;
            $this->isActive = $sg->is_active;
        } else {
            // Default to current academic year
            $currentYear = date('Y');
            $this->academicYear = $currentYear . '-' . ($currentYear + 1);
        }

        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->name = '';
        $this->abbreviation = '';
        $this->description = '';
        $this->departmentId = null;
        $this->academicYear = '';
        $this->adviserId = null;
        $this->isActive = true;
        $this->staffSearch = '';
        $this->availableStaff = [];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
            'description' => $this->description,
            'department_id' => $this->departmentId,
            'academic_year' => $this->academicYear,
            'adviser_id' => $this->adviserId,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            StudentGovernment::find($this->editingId)->update($data);
            session()->flash('success', 'Student Government updated successfully.');
        } else {
            StudentGovernment::create($data);
            session()->flash('success', 'Student Government created successfully.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        StudentGovernment::find($id)->delete();
        session()->flash('success', 'Student Government deleted successfully.');
    }

    public function toggleActive($id)
    {
        $sg = StudentGovernment::find($id);
        $sg->update(['is_active' => !$sg->is_active]);
        session()->flash('success', 'Status updated successfully.');
    }

    public function render()
    {
        $studentGovernments = StudentGovernment::with(['department', 'adviser'])
            ->orderBy('academic_year', 'desc')
            ->paginate(10);

        $departments = Department::orderBy('name')->get();

        return view('livewire.student-government.index', [
            'studentGovernments' => $studentGovernments,
            'departments' => $departments,
        ]);
    }
}
