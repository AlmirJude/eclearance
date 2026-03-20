<?php

namespace App\Livewire\Users;

use App\Models\Department;
use App\Models\User;
use App\Models\StudentDetail;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class AddStudent extends Component
{
    // User table fields (user_id will be same as student_id)
    public $student_id = ''; // This will be used for BOTH users.user_id AND student_details.student_id
    public $email = '';
    public $password = '';
    public $confirmPassword = '';
    
    // Student details table fields
    public $first_name = '';
    public $last_name = '';
    public $department_id;
    public $year_level = 1;

    protected function rules()
    {
        return [
            // User validation
            'student_id' => 'required|string|max:6|regex:/^[0-9-]+$/|unique:users,user_id|unique:student_details,student_id',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|same:confirmPassword',
            'confirmPassword' => 'required',
            
            // Student details validation
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'department_id' => 'required',
            'year_level' => 'required|integer|min:1|max:6',
        ];
    }

    protected $messages = [
        'student_id.required' => 'Student ID is required',
        'student_id.max' => 'Student ID must not exceed 6 characters',
        'student_id.regex' => 'Student ID may only contain numbers and dashes',
        'student_id.unique' => 'This Student ID already exists',
        'email.required' => 'Email is required',
        'email.email' => 'Please enter a valid email',
        'email.unique' => 'This email is already registered',
        'password.required' => 'Password is required',
        'password.min' => 'Password must be at least 8 characters',
        'password.same' => 'Passwords do not match',
        'first_name.required' => 'First name is required',
        'last_name.required' => 'Last name is required',
        'year_level.required' => 'Year level is required',
    ];

    public function updatedStudentId($value)
    {
        $sanitized = preg_replace('/[^0-9-]/', '', (string) $value);
        $this->student_id = substr($sanitized, 0, 6);
    }

    public function submit()
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $user = User::create([
                    'user_id' => $this->student_id, // student_id goes into user_id column
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                    'role' => 'student',
                ]);

                StudentDetail::create([
                    'user_id' => $user->id, // Links to users.id (auto-increment primary key)
                    'student_id' => $this->student_id, // Same as users.user_id
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'department_id' => $this->department_id,
                    'year_level' => $this->year_level,
                ]);
            });

            // Success message
            session()->flash('success', 'Student added successfully!');
            
            // Reset form
            $this->reset();
            
            
        } catch (\Exception $e) {
            // Error handling
            session()->flash('error', 'Failed to add student: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.users.add-student', [
            'departments' => Department::orderBy('name', 'ASC')->get(),
        ]);
    }
}