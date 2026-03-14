<?php

namespace App\Livewire\Users;

use App\Models\StaffDetail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class EditStaff extends Component
{
    public $employee_id, $email, $password, $confirmPassword, $role;
    public $first_name, $last_name, $department, $position, $employee_type;

    public function mount ($id)
    {
        $user = User::findorFail($id);
        $staff = StaffDetail::where('user_id', $user->id)->firstorFail();

        $this->employee_id   = $staff->employee_id;
        $this->email         = $user->email;
        $this->first_name    = $staff->first_name;
        $this->last_name     = $staff->last_name;
        $this->department    = $staff->department;
        $this->position      = $staff->position;
        $this->employee_type = $staff->employee_type;
        $this->role          = $user->role;
    }

    protected function rules()
    {
        return [
            // User validation
            'email' => 'required|email|max:255|unique:users,email,'.$this->employee_id.',user_id',
            'password' => 'nullable|min:8|same:confirmPassword',
            'confirmPassword' => 'nullable',

            // Staff details validation
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'department'    => 'nullable|string|max:255',
            'position'      => 'nullable|string|max:255',
            'employee_type' => 'nullable|in:teaching,non-teaching',
        ];
    }

    public function save()
    {
        $this->validate();

        $staff = StaffDetail::where('employee_id', $this->employee_id)->firstOrFail();
        $user = $staff->user;

        $user->email = $this->email;
        $user->role = $this->role;
        if (!empty($this->password)) {
            $user->password = Hash::make($this->password);
        }
        $user->save();

        $staff->first_name    = $this->first_name;
        $staff->last_name     = $this->last_name;
        $staff->department    = $this->department;
        $staff->position      = $this->position;
        $staff->employee_type = $this->employee_type;
        $staff->save();

        return redirect()->route('staff.index')->with('success', 'Staff updated successfully.');
    }

    public function render()
    {
        return view('livewire.users.edit-staff');
    }
}
