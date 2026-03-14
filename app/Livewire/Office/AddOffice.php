<?php

namespace App\Livewire\Office;

use App\Models\Office;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class AddOffice extends Component
{
    public $name = "";
    public $manager_id = null;
    public $is_required = true;
    public $clearance_order = 0;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:offices,name',
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

    public function save()
    {
        $this->validate();

        Office::create([
            'name' => $this->name,
            'manager_id' => $this->manager_id,
            'is_required' => $this->is_required,
            'clearance_order' => $this->clearance_order,
        ]);

        session()->flash('message', 'Office added successfully.');
        return redirect()->route('office.index');
    }

    public function render()
    {
        $availableAdmins = User::where('role', 'admin')
            ->orWhere('role', 'staff')
            ->with('staffDetail')
            ->get();


        return view('livewire.office.add-office', compact('availableAdmins'));
    }
}
