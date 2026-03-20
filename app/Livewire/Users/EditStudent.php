<?php

namespace App\Livewire\Users;

use App\Models\Department;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\Attributes\Layout; // 1. Add this import

#[Layout('components.layouts.app')] // 
class EditStudent extends Component
{
    public $student_id, $email, $password, $confirmPassword;
    public $first_name, $last_name, $department_id, $year_level;

    public function updatedStudentId($value)
    {
        $sanitized = preg_replace('/[^0-9-]/', '', (string) $value);
        $this->student_id = substr($sanitized, 0, 6);
    }

    public function mount ($id){
        $user = User::findOrFail($id);
        $student = StudentDetail::where('user_id', $user->id)->firstOrFail();

        $this->student_id = $student->student_id;
        $this->email = $user->email;
        $this->first_name = $student->first_name;
        $this->last_name = $student->last_name;
        $this->department_id = $student->department_id;
        $this->year_level = $student->year_level;
    }

    protected function rules()
    {
        return [
            // User validation
            'email' => 'required|email|max:255|unique:users,email,'.$this->student_id.',user_id',
            'password' => 'nullable|min:8|same:confirmPassword',
            'confirmPassword' => 'nullable',
            
            // Student details validation
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'department_id' => 'required',
            'year_level' => 'required|integer|min:1|max:6',
        ];
    }

    public function save() {
        $this->validate();

        $student = StudentDetail::where('student_id', $this->student_id)->firstOrFail();
        $user = $student->user;

        $user->email = $this->email;
        if (!empty($this->password)) {
            $user->password = Hash::make($this->password);
        }
        $user->save();

        $student->first_name = $this->first_name;
        $student->last_name = $this->last_name;
        $student->department_id = $this->department_id;
        $student->year_level = $this->year_level;
        $student->save();

        return redirect()->route('student.index')->with('success', 'Student updated successfully.');
    }

    public function render()
    {
        return view('livewire.users.edit-student', [
            'departments' => Department::orderBy('name', 'ASC')->get(),
        ]);
    }
}
