<?php

namespace App\Livewire\Users;

use App\Models\StaffDetail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class AddStaff extends Component
{
    //User table Fields
    public $employee_id = '';
    public $email = '';
    public $password = '';
    public $confirmPassword = '';

    //Staff Details table
    public $first_name = '';
    public $last_name = '';
    public $department = '';
    public $position = '';

    protected function rules()
    {
        return [
            //User validation
            'employee_id' => [
                'required',
                'string',
                'max:6',
                'regex:/^(?:NT|BED|HED)-?[0-9]+$/i',
                'unique:users,user_id',
                'unique:staff_details,employee_id',
            ],
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|same:confirmPassword',
            'confirmPassword' => 'required',

            //Staff details validation
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ];
    }

    protected $messages = [
        'employee_id.required' => 'Employee ID is required',
        'employee_id.max' => 'Employee ID must not exceed 6 characters',
        'employee_id.regex' => 'Employee ID must start with NT, BED, or HED and contain only numbers after that (optional dash allowed)',
        'employee_id.unique' => 'This Employee ID already exists',
    ];

    public function updatedEmployeeId($value)
    {
        $sanitized = strtoupper(preg_replace('/[^A-Z0-9-]/i', '', (string) $value));
        $this->employee_id = substr($sanitized, 0, 6);
    }

    public function submit() {
        $this->updatedEmployeeId($this->employee_id);
        $this->validate();

        try {
            // Code to create user and staff detail records goes here
            //Code to insert in users table
            $user = User::create([
                'user_id' => $this->employee_id,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => 'staff'
            ]);

            //Code to insert in staff_details table
            StaffDetail::create([
                'user_id' => $user->id,
                'employee_id' => $this->employee_id,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'department' => $this->department,
                'position' => $this->position,
            ]);
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while adding the staff member: ' . $e->getMessage());
            return;
            
        }
        session()->flash('success', 'Staff member added successfully.', redirect()->route('staff.index'));
    }


    public function render()
    {
        return view('livewire.users.add-staff');
    }
}
