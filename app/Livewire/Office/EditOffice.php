<?php

namespace App\Livewire\Office;

use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
        $this->authorizeAccess($office);
        $this->name = $office->name;
        $this->manager_id = $office->manager_id;
        $this->is_required = $office->is_required;
        $this->clearance_order = $office->clearance_order;
    }

    protected function authorizeAccess(Office $office)
    {
        $user = Auth::user();
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return;
        }
        if ($office->manager_id !== $user->id) {
            abort(403, 'You are not the manager of this office.');
        }
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

    public function save()
    {
        $this->validate();

        $office = Office::findOrFail($this->id);
        $office->name = $this->name;
        $office->manager_id = $this->manager_id;
        $office->is_required = $this->is_required;
        $office->clearance_order = $this->clearance_order;
        $office->save();

        session()->flash('message', 'Office updated successfully.');
        $user = Auth::user();
        if (in_array($user->role, ['superadmin', 'admin'])) {
            return redirect()->route('office.index');
        }
        return redirect()->route('office.overview', ['officeId' => $this->id]);
    }

    public function render()
    {
        $availableAdmins = User::where('role', 'admin')
            ->orWhere('role', 'staff')
            ->with('staffDetail')
            ->get();


        return view('livewire.office.edit-office', compact('availableAdmins'));
    }
}
