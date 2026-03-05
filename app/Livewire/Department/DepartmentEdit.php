<?php

namespace App\Livewire\Department;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DepartmentEdit extends Component
{
    public $departmentId;
    public $name, $abbreviation, $description, $manager_id;


    public function mount($id)
    {
        $this->departmentId = $id;
        $department = Department::findOrFail($id);
        $this->authorizeAccess($department);

        $this->name = $department->name;
        $this->abbreviation = $department->Abbreviation;
        $this->description = $department->description;
        $this->manager_id = $department->manager_id;
    }

    protected function authorizeAccess(Department $department)
    {
        $user = Auth::user();
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return;
        }
        if ($department->manager_id !== $user->id) {
            abort(403, 'You are not the manager of this department.');
        }
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:departments,name,' . $this->departmentId,
            'abbreviation' => 'required|string|max:20|unique:departments,abbreviation,' . $this->departmentId,
            'description' => 'nullable|string|max:500',
            'manager_id' => 'nullable|exists:users,id',
        ];
    }

    protected $messages = [
        'name.required' => 'Department name is required',
        'name.unique' => 'This department name already exists',
        'abbreviation.required' => 'Abbreviation is required',
        'abbreviation.unique' => 'This abbreviation already exists',
        'manager_id.exists' => 'Selected manager does not exist',
    ];

    public function save()
    {
        $this->validate();

        $department = Department::findOrFail($this->departmentId);
        $department->name = $this->name;
        $department->Abbreviation = $this->abbreviation;
        $department->description = $this->description;
        $department->manager_id = $this->manager_id;
        $department->save();

        session()->flash('message', 'Department updated successfully.');
        $user = Auth::user();
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return redirect()->route('department.index');
        }
        return redirect()->route('department.overview', ['departmentId' => $this->departmentId]);
    }
    
    public function render()
    {
        $availableAdmins = User::where('role', 'admin')
            ->orWhere('role', 'staff')
            ->with('staffDetail')
            ->get();

        return view('livewire.department.department-edit', [
            'availableAdmins' => $availableAdmins,
        ]);
    }
}
