<?php

namespace App\Livewire\Department;

use App\Models\Department;
use App\Models\User;
use Livewire\Component;

class AddDepartment extends Component
{
    public $name = "";
    public $abbreviation = "";
    public $description = "";
    public $manager_id = null;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:departments,name',
            'abbreviation' => 'required|string|max:20|unique:departments,abbreviation',
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

        Department::create([
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
            'description' => $this->description,
            'manager_id' => $this->manager_id,
        ]);

        session()->flash('message', 'Department added successfully.');
        return redirect()->route('department.index');
    }

    public function render()
    {
        $availableAdmins = User::where('role', 'admin')
            ->orWhere('role', 'staff')
            ->with('staffDetail')
            ->get();

        return view('livewire.department.add-department', [
            'availableAdmins' => $availableAdmins,
        ]);
    }
}
