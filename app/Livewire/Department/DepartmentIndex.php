<?php

namespace App\Livewire\Department;

use App\Models\Department;
use App\Models\User;
use Livewire\Component;

class DepartmentIndex extends Component
{
    public function delete ($id)
    {
        $department = Department::find($id);
        if ($department) {
            $department->delete();
            session()->flash('message', 'Department deleted successfully.');
        } else {
            session()->flash('error', 'Department not found.');
        }
    }

    public function render()
    {
        $departments = Department::with('manager')->get();
        return view('livewire.department.department-index', compact('departments'));
    }
}
