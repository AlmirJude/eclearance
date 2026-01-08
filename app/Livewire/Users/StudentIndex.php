<?php

namespace App\Livewire\Users;

use App\Models\StudentDetail;
use Livewire\Component;

class StudentIndex extends Component
{

    public $showViewModal = false;
    public $showDeleteModal = false;
    public $selectedStudent;


    public function render()
    {
        $students = StudentDetail::get();   
        return view('livewire.users.student-index', compact('students'));
    }

    public function view($id){
        $this->selectedStudent = StudentDetail::with('user')->find($id);
        $this->showViewModal = true;
        $this->dispatch('open-modal', name: 'view-student-modal');
    }

    public function closeModalViewStudent(){
        $this->showViewModal = false;
        $this->selectedStudent = null;
        $this->dispatch('close-modal', name: 'view-student-modal');
    }



    public function confirmDelete($id)
    {
        $this->selectedStudent = StudentDetail::with('user')->find($id);
        $this->showDeleteModal = true;
        $this->dispatch('open-modal', name: 'showDeleteModal');
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->selectedStudent = null;
        $this->dispatch('close-modal', name: 'showDeleteModal');
    }

    public function delete()
    {
        if ($this->selectedStudent) {
            $this->selectedStudent->user()->delete(); // This will cascade delete the student_details
            session()->flash('success', 'Student deleted successfully.');
            $this->showDeleteModal = false;
            $this->selectedStudent = null;
            $this->dispatch('close-modal', name: 'showDeleteModal');
        }
        else {
            session()->flash('error', 'Student not found.');
        }
    }
}
