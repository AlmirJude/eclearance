<?php

namespace App\Livewire\Office;

use App\Models\Office;
use App\Models\User;
use Livewire\Component;

class EditOffice extends Component
{
    public $id;
    public $name;
    public $manager_id;
    public $is_required;
    public $clearance_order;

    public function mount()
    {
        $office = Office::findorfail($this->id);
        $this->name = $office->name;
        $this->manager_id = $office->manager_id;
        $this->is_required = $office->is_required;
        $this->clearance_order = $office->clearance_order;
    }
    

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:offices,name,' . $this->id,
            'manager_id' => 'nullable|exists:users,id',
            'is_required' => 'boolean',
            'clearance_order' => 'integer|min:0',
        ];
    }

    protected $messages = [
        'name.required' => 'Office name is required',
        'name.unique' => 'This office name already exists',
        'manager_id.exists' => 'Selected manager does not exist',
        'clearance_order.integer' => 'Clearance order must be an integer',
        'clearance_order.min' => 'Clearance order must be at least 0',
    ];


    public function render()
    {
        $availableAdmins = User::where('role', 'admin')
            ->orWhere('role', 'staff')
            ->with('staffDetail')
            ->get();


        return view('livewire.office.edit-office', compact('availableAdmins'));
    }
}
